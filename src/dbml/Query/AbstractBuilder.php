<?php

namespace ryunosuke\dbml\Query;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use ryunosuke\dbml\Database;
use ryunosuke\dbml\Mixin\OptionTrait;
use ryunosuke\utility\attribute\ClassTrait\DebugInfoTrait;
use function ryunosuke\dbml\array_convert;
use function ryunosuke\dbml\array_depth;
use function ryunosuke\dbml\array_each;
use function ryunosuke\dbml\array_maps;
use function ryunosuke\dbml\arrayize;
use function ryunosuke\dbml\first_key;
use function ryunosuke\dbml\preg_splice;

abstract class AbstractBuilder implements Queryable, \Stringable
{
    use DebugInfoTrait;
    use OptionTrait;

    protected Database $database;

    protected string $sql;
    protected array  $params;

    protected Statement $statement;

    public function __construct(Database $database)
    {
        $this->database = $database;
        $this->setDefault($database->getOptions());
    }

    /**
     * @ignore
     */
    public function __call(string $name, array $arguments): mixed
    {
        // OptionTrait へ移譲
        $result = $this->OptionTrait__callGetSet($name, $arguments, $called);
        if ($called) {
            return $result;
        }

        throw new \BadMethodCallException("'$name' is undefined.");
    }

    /**
     * WHERE/HAVING 条件を正規化する
     *
     * where だけは select,affect の両方に出現し得る（厳密に言えば order や limit もだけど）のでここに定義してある。
     */
    protected function _precondition(array $tables, array $predicates): array
    {
        return array_convert($predicates, function ($cond, &$param, $keys) use ($tables) {
            $is_int = is_int($cond);
            $is_toplevel = array_filter($keys, 'is_int') === $keys; // flipflop の仕様がある

            // 主キー
            if ($cond === '') {
                if (!$is_toplevel) {
                    return false;
                }
                $alias = array_key_first($tables) ?? throw new \UnexpectedValueException('base table not found.');
                $pcols = $this->database->getSchema()->getTablePrimaryKey($tables[$alias])->getColumns();
                $params = (array) $param;
                if (count($pcols) !== 1 && count($params) !== 0 && array_depth($params) === 1) {
                    $params = [$params];
                }
                $pvals = array_each($params, function (&$carry, $pval) use ($pcols) {
                    $pvals = (array) $pval;
                    if (count($pcols) !== count($pvals)) {
                        throw new \InvalidArgumentException('argument\'s length is not match primary columns.');
                    }
                    $carry[] = array_combine($pcols, $pvals);
                }, []);
                return [$this->database->getCompatiblePlatform()->getPrimaryCondition($pvals, $alias)];
            }
            // エニーカラム（*.column_name）
            if (is_string($cond) && strpos($cond, '*.') === 0) {
                if (!$is_toplevel) {
                    return false;
                }
                [, $column] = explode('.', $cond, 2);
                $subcond = [];
                foreach ($tables as $alias => $table) {
                    $columns = $this->database->getSchema()->getTableColumns($table);
                    if (array_key_exists($column, $columns)) {
                        $subcond[$alias . '.' . $column] = $param;
                    }
                }
                return $subcond;
            }
            // マルチカラム（table.prefix*）
            if (is_string($cond) && preg_match('#[_a-z0-9][?*{}[\]]#i', $cond)) {
                if (!$is_toplevel) {
                    return false;
                }
                [$modifier, $column] = array_pad(explode('.', $cond, 2), -2, '');
                $subcond = [];
                foreach ($tables as $alias => $table) {
                    if (in_array($modifier, ['', $alias, $table], true) && $this->database->getSchema()->hasTable($table)) {
                        $allcolumns = $this->database->getSchema()->getTableColumns($table);
                        foreach ($allcolumns as $name => $col) {
                            if (fnmatch($column, $name)) {
                                $subcond[$alias . '.' . $name] = $param;
                            }
                        }
                    }
                }
                if (!$subcond) {
                    throw new \InvalidArgumentException("$cond is not match any columns");
                }
                return [$subcond];
            }
            // 仮想カラム（tablename.virtualname）@todo 何をしているか分からない
            $cond2 = $is_int ? $param : $cond;
            if (is_string($cond2) && preg_match('#([a-z_][a-z0-9_]*)\.([a-z_][a-z0-9_]*)#ui', $cond2, $matches) && $is_toplevel) {
                $modifier = $matches[1];
                $tablename = $tables[$modifier] ?? $modifier;
                if ($this->database->getSchema()->hasTable($tablename)) {
                    $vcolumns = array_filter($this->database->getSchema()->getTableColumns($tablename), function (Column $col) {
                        return $col->hasPlatformOption('select');
                    });
                    if ($vcolumns) {
                        $newparam = $is_int ? [] : $param;
                        $cols = arrayize($newparam);
                        $params = [];
                        $vnames = implode('|', array_map(function ($v) { return preg_quote($v, '#'); }, array_keys($vcolumns)));
                        $expr = preg_replace_callback("#(\\?)|$modifier\\.($vnames)(?![_a-zA-Z0-9])#u", function ($m) use ($cond, $tables, $modifier, $tablename, &$cols, &$params) {
                            $vname = $m[2] ?? null;
                            if ($vname === null) {
                                if ($cols) {
                                    $params[] = array_shift($cols);
                                }
                                return '?';
                            }

                            $prefix = "/* vcolumn $vname-" . (is_int($cond) ? $cond : 'k') . " */";
                            $vcol = $this->database->getSchema()->getTableColumnExpression($tablename, $vname, 'select', $this->database);
                            if (is_string($vcol)) {
                                return "$prefix " . sprintf($vcol, $modifier);
                            }
                            elseif ($vcol instanceof Queryable) {
                                $vcolq = clone $vcol;
                                if ($vcolq instanceof SelectBuilder && ($vcolq->getSubmethod() !== null || $vcolq->getLazyMode() !== null)) {
                                    foreach ($tables as $alias => $table) {
                                        if ($vcolq->setSubwhere($table, $alias)) {
                                            break;
                                        }
                                    }
                                }
                                if ($vcolq instanceof SelectBuilder && $vcolq->getLazyMode() !== null) {
                                    $vcolq->andWhere($cols)->exists();
                                    $cols = [true];
                                }

                                $params[] = $vcolq;
                                return "$prefix ?";
                            }
                        }, $cond2, -1, $count);

                        if (!$count || $cond2 === $expr) {
                            return;
                        }

                        if ($params) {
                            $params = array_merge($params, $cols);
                            if ($params !== arrayize($param)) {
                                $param = $params;
                            }
                        }
                        elseif ($is_int) {
                            $param = $expr;
                            return null;
                        }
                        return $expr;
                    }
                }
            }
            // サブクエリビルダ(subexists, submax, sub...)
            if ($param instanceof SelectBuilder && ($submethod = $param->getSubmethod()) !== null) {
                // "P" or "P:fkey" or "colname|P" or "colname|P:fkey"
                $conds = is_bool($submethod) ? "|$cond" : $cond;
                $colname = preg_splice('#\|([a-z0-9_]+)(:([a-z0-9_]+))?#ui', '', $conds, $matches);
                $falias = $matches[1] ?? null;
                $fkname = $matches[3] ?? null;
                if (array_key_exists($falias, $tables)) {
                    if ($param->setSubwhere($tables[$falias], $falias, $fkname)) {
                        return is_string($submethod) ? $colname : true;
                    }
                }
                else {
                    foreach ($tables as $alias => $table) {
                        if ($param->setSubwhere($table, $alias)) {
                            return null;
                        }
                    }
                }
            }
        }, true);
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }

    /**
     * ? 込みのキー名を正規化する
     *
     * 具体的には引数 $params に bind 値を格納して返り値として（? を含んだ）クエリ文字列を返す。
     *
     * ```php
     * # 単純に文字列で渡す（あまり用途はない）
     * $qb->bindInto('col', $params);
     * // results: "?", $params: ['col']
     *
     * # Queryable も渡せる
     * $qb->bindInto(Expression::new('col', [1]), $params);
     * // results: ['col1'], $params: [1]
     *
     * # 配列で渡す（混在可能。メイン用途）
     * $qb->bindInto(['col1' => Expression::new('UPPER(?)', [1]), 'col2' => 2], $params);
     * // results: ['col1' => 'UPPER(?)', 'col2' => '?'], $params: [1, 2]
     * ```
     */
    public function bindInto($data, ?array &$params): string|array
    {
        $params = $params ?? [];

        // 配列は再帰
        if (is_array($data)) {
            return array_each($data, function (&$carry, $value, $columnName) use (&$params) {
                $carry[$columnName] = $this->bindInto($value, $params);
            }, []);
        }

        // Queryable なら文字列化して params を bind
        if ($data instanceof Queryable) {
            return $data->merge($params);
        }
        // それ以外は $value を bind
        else {
            $params[] = $data;
            return '?';
        }
    }

    public function foreignWheres(string $table, ?string $alias, array $events, bool $affirmation): array
    {
        $default = [
            'restrict' => false,
            'cascade'  => false,
            'setnull'  => false,
        ];

        $where = [];

        $schema = $this->database->getSchema();
        $fkeys = $schema->getForeignKeys($table, null);
        foreach ($fkeys as $fkey) {
            $fkopt = $fkey->getOptions();
            if (($fkopt['enable'] ?? true)) {
                foreach ($events as $event => $actions) {
                    $actions += $default;
                    $action = strtolower($fkey->{"on$event"}() ?? 'RESTRICT');
                    if ($actions[$action]) {
                        $ltable = first_key($schema->getForeignTable($fkey));
                        $select = $this->database->select($ltable);
                        $select->setSubwhere($table, $alias, $fkey->getName());
                        $where[] = $affirmation ? $select->exists() : $select->notExists();
                        continue 2;
                    }
                }
            }
        }
        return $where;
    }

    public function cascadeValues(ForeignKeyConstraint $fkey, array $values): array
    {
        $subdata = [];
        foreach (array_combine($fkey->getLocalColumns(), $fkey->getForeignColumns()) as $lcol => $fcol) {
            if (array_key_exists($fcol, $values)) {
                $subdata[$lcol] = $values[$fcol];
            }
        }
        return $subdata;
    }

    public function cascadeWheres(ForeignKeyConstraint $fkey, array $wheres): array
    {
        $pselect = $this->database->select([$fkey->getForeignTableName() => $fkey->getForeignColumns()], $wheres);
        $subwhere = [];
        if (!$this->database->getCompatiblePlatform()->supportsRowConstructor() && count($fkey->getLocalColumns()) > 1) {
            $pvals = array_maps($pselect->array(), fn($pval) => array_combine($fkey->getLocalColumns(), $pval));
            $ltable = first_key($this->database->getSchema()->getForeignTable($fkey));
            $pcond = $this->database->getCompatiblePlatform()->getPrimaryCondition($pvals, $ltable);
            $subwhere[] = $this->database->queryInto($pcond) ?: 'FALSE';
        }
        else {
            $ckey = implode(',', $fkey->getLocalColumns());
            $subwhere["($ckey)"] = $pselect;
        }
        return $subwhere;
    }

    /**
     * 現在のビルダの状態で固定して prepare する
     *
     * 「preparedStatement を返す」のではなく「prepare 状態にするだけ」なのに注意。
     * preparedStatement は {@link getPreparedStatement()} で取得する。
     *
     * ```php
     * $qb->column('t_article', ['state' => 'active', 'id = :id']);
     * # 現在の状態で prepare する
     * $qb->prepare();
     * // この段階では state: active は固定されているが、:id は未確定
     * $stmt = $qb->getPreparedStatement();
     * // ここで実行することで id: 1 がプリペアで実行される
     * $stmt->executeSelect(['id' => 1]); // SELECT t_article.* FROM t_article WHERE (id = 1) AND (state = "active")
     * // さらに続けてプリペアで id: 2 を実行できる
     * $stmt->executeSelect(['id' => 2]); // SELECT t_article.* FROM t_article WHERE (id = 2) AND (state = "active")
     * ```
     */
    public function prepare(): static
    {
        $this->statement = new Statement((string) $this, $this->getParams(), $this->database);
        return $this;
    }

    /**
     * prepare したステートメントを返す
     *
     * {@link prepare()} の例も参照。
     */
    public function getPreparedStatement(): ?Statement
    {
        return $this->statement ?? null;
    }

    /**
     * パラメータを利用してクエリ化
     */
    public function queryInto(): string
    {
        return $this->database->queryInto($this->__toString(), $this->getParams());
    }

    /**
     * すべてを無に帰す
     */
    public function reset(): static
    {
        unset($this->sql);
        unset($this->params);
        unset($this->statement);

        return $this;
    }

    public function getQuery(): string
    {
        return $this->sql;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function merge(?array &$params): string
    {
        $params = $params ?? [];
        foreach ($this->getParams() as $param) {
            $params[] = $param;
        }
        return $this->getQuery();
    }
}
