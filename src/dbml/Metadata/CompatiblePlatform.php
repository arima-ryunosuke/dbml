<?php

namespace ryunosuke\dbml\Metadata;

use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Types;
use ryunosuke\dbml\Mixin\FactoryTrait;
use ryunosuke\dbml\Query\Expression\Expression;
use ryunosuke\dbml\Query\Queryable;
use ryunosuke\dbml\Utility\Adhoc;
use function ryunosuke\dbml\array_each;
use function ryunosuke\dbml\array_sprintf;
use function ryunosuke\dbml\array_strpad;
use function ryunosuke\dbml\arrayize;
use function ryunosuke\dbml\class_shorten;
use function ryunosuke\dbml\concat;
use function ryunosuke\dbml\first_keyvalue;
use function ryunosuke\dbml\starts_with;

/**
 * 各 Platform では賄いきれない RDBMS の差異を吸収するクラス
 *
 * ライブラリ内部で $platform instanceof したくないのでそういうのはこのクラスが吸収する。
 * あと sqlite だけでできるだけカバレッジを埋めたい裏事情もある。
 * （コイツのテストは接続を必要としないのであらゆる環境でカバーできるため）。
 *
 * 本当は AbstractPlatform を継承したいんだけどそれだと本家の変更を自動追従できないのでコンポジットパターンになっている。
 */
class CompatiblePlatform /*extends AbstractPlatform*/
{
    use FactoryTrait;

    protected AbstractPlatform $platform;

    protected ?string $version;

    /**
     * コンストラクタ
     */
    public function __construct(AbstractPlatform $platform, ?string $version = null)
    {
        $this->platform = $platform;
        $this->version = $version;
    }

    /**
     * 元 platform を取得する
     */
    public function getWrappedPlatform(): AbstractPlatform
    {
        return $this->platform;
    }

    /**
     * バージョン文字列を取得する
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * platform 名を取得する
     */
    public function getName(): string
    {
        if ($this->platform instanceof SqlitePlatform) {
            return 'sqlite';
        }
        if ($this->platform instanceof MySQLPlatform) {
            return 'mysql';
        }
        if ($this->platform instanceof PostgreSQLPlatform) {
            return 'postgresql';
        }
        if ($this->platform instanceof SQLServerPlatform) {
            return 'mssql';
        }

        return strtolower(preg_replace('#Platform$#', '', class_shorten($this->platform)));
    }

    /**
     * トランザクション内でエラーがあったときに abort 状態になるか
     */
    public function supportsAbortTransaction(): bool
    {
        if ($this->platform instanceof SqlitePlatform) {
            return true; // for test
        }
        if ($this->platform instanceof MySQLPlatform) {
            return false;
        }
        if ($this->platform instanceof PostgreSQLPlatform) {
            return true;
        }
        if ($this->platform instanceof SQLServerPlatform) {
            return true;
        }
        return false;
    }

    /**
     * AUTO_INCREMENT な列に null を与えると自動採番が働くかどうか
     */
    public function supportsIdentityNullable(): bool
    {
        if ($this->platform instanceof SqlitePlatform) {
            return true;
        }
        if ($this->platform instanceof MySQLPlatform) {
            return true;
        }
        if ($this->platform instanceof PostgreSQLPlatform) {
            return false;
        }
        if ($this->platform instanceof SQLServerPlatform) {
            return false;
        }
        return false;
    }

    /**
     * AUTO_INCREMENT な列を明示指定して UPDATE できるか否かを返す
     */
    public function supportsIdentityUpdate(): bool
    {
        if ($this->platform instanceof SQLServerPlatform) {
            return false;
        }
        return true;
    }

    /**
     * AUTO_INCREMENT な列を明示指定したあと、自動でシーケンスが更新されるか否かを返す
     */
    public function supportsIdentityAutoUpdate(): bool
    {
        if ($this->platform instanceof PostgreSQLPlatform || $this->platform instanceof OraclePlatform) {
            return false;
        }
        return true;
    }

    /**
     * INSERT SET 拡張構文が使えるか否かを返す
     */
    public function supportsInsertSet(): bool
    {
        if ($this->platform instanceof MySQLPlatform) {
            return true;
        }

        return false;
    }

    /**
     * REPLACE が使えるか否かを返す
     */
    public function supportsReplace(): bool
    {
        if ($this->platform instanceof SqlitePlatform) {
            return true;
        }
        if ($this->platform instanceof MySQLPlatform) {
            return true;
        }
        return false;
    }

    /**
     * MERGE が使えるか否かを返す
     */
    public function supportsMerge(): bool
    {
        if ($this->platform instanceof SqlitePlatform) {
            return true;
        }
        if ($this->platform instanceof MySQLPlatform) {
            return true;
        }
        if ($this->platform instanceof PostgreSQLPlatform) {
            return true;
        }
        return false;
    }

    /**
     * BULK MERGE が使えるか否かを返す
     */
    public function supportsBulkMerge(): bool
    {
        if ($this->platform instanceof SqlitePlatform) {
            return true;
        }
        if ($this->platform instanceof MySQLPlatform) {
            return true;
        }
        if ($this->platform instanceof PostgreSQLPlatform) {
            return true;
        }
        return false;
    }

    /**
     * IGNORE が使えるか否かを返す
     */
    public function supportsIgnore(): bool
    {
        if ($this->platform instanceof SqlitePlatform) {
            return true;
        }
        if ($this->platform instanceof MySQLPlatform) {
            return true;
        }
        return false;
    }

    /**
     * UPDATE + ORDER BY,LIMIT をサポートするか否かを返す
     */
    public function supportsUpdateLimit(): bool
    {
        if ($this->platform instanceof MySQLPlatform || $this->platform instanceof SQLServerPlatform) {
            return true;
        }
        return false;
    }

    /**
     * DELETE + ORDER BY,LIMIT をサポートするか否かを返す
     */
    public function supportsDeleteLimit(): bool
    {
        if ($this->platform instanceof MySQLPlatform || $this->platform instanceof SQLServerPlatform) {
            return true;
        }
        return false;
    }

    /**
     * UNION が括弧をサポートするか否かを返す
     */
    public function supportsUnionParentheses(): bool
    {
        if ($this->platform instanceof SqlitePlatform) {
            return false;
        }
        return true;
    }

    /**
     * TRUNCATE 文で自動採番列がリセットされるか否かを返す
     */
    public function supportsResetAutoIncrementOnTruncate(): bool
    {
        // Sqlite のみリセットされない
        if ($this->platform instanceof SqlitePlatform) {
            return false;
        }

        return true;
    }

    /**
     * 行値式が有効か否かを返す
     */
    public function supportsRowConstructor(): bool
    {
        if ($this->platform instanceof SqlitePlatform) {
            // どうも中途半端な対応のようで文脈によってはエラーが出るため false にする
            //return version_compare($this->version, '3.15.0') >= 0;
            return false;
        }
        if ($this->platform instanceof SQLServerPlatform) {
            return false;
        }
        return true;
    }

    /**
     * char と binary に互換性があるかを返す
     */
    public function supportsCompatibleCharAndBinary(): bool
    {
        if ($this->platform instanceof SQLServerPlatform) {
            return false;
        }
        return true;
    }

    /**
     * id asc,id desc のような冗長な ORDER BY を許すか
     */
    public function supportsRedundantOrderBy(): bool
    {
        if ($this->platform instanceof SQLServerPlatform) {
            return false;
        }
        return true;
    }

    /**
     * 必要に応じて識別子をエスケープする
     */
    public function quoteIdentifierIfNeeded(string $word): string
    {
        if (strlen($word) === 0) {
            return $word;
        }

        if (!preg_match('#^[_a-z0-9]+$#ui', $word) || $this->platform->getReservedKeywordsList()->isKeyword($word)) {
            return $this->platform->quoteSingleIdentifier($word);
        }

        // PostgreSql は識別子が小文字に正規化されるのでエイリアスがブレる
        if ($this->platform instanceof PostgreSQLPlatform && strtolower($word) !== $word) {
            return $this->platform->quoteSingleIdentifier($word);
        }

        return $word;
    }

    /**
     * LIKE エスケープする
     */
    public function escapeLike(string|Queryable $word, string $escaper = '\\'): string
    {
        if ($word instanceof Queryable) {
            assert(!$word->getParams());
            return "$word";
        }

        return $this->platform->escapeStringForLike($word, $escaper);
    }

    /**
     * 文字列を指定長で切る
     */
    public function truncateString(string $string, Column $column): string
    {
        // @todo mysql 以外は詳しくないため未実装
        if (!$this->platform instanceof MySQLPlatform) {
            return $string;
        }

        $name = Adhoc::typeName($column->getType());
        if (in_array($name, [Types::STRING], true)) {
            if (!$column->getLength() || !$column->hasPlatformOption('charset')) {
                return $string;
            }
            $charset = $column->getPlatformOption('charset');
            $charset = starts_with($charset, 'utf8') ? 'utf-8' : $charset;
            return mb_substr($string, 0, $column->getLength(), $charset);
        }
        if (in_array($name, [Types::BINARY, Types::TEXT, Types::BLOB], true)) {
            if (!$column->getLength()) {
                return $string;
            }
            return substr($string, 0, $column->getLength());
        }

        throw new \InvalidArgumentException($name . ' is not supported');
    }

    /**
     * MERGE 構文を返す
     */
    public function getMergeSyntax(array $columns): ?string
    {
        if ($this->platform instanceof SqlitePlatform) {
            $constraint = implode(',', $columns);
            return "ON CONFLICT($constraint) DO UPDATE SET";
        }
        if ($this->platform instanceof MySQLPlatform) {
            if (version_compare($this->version, '8.0.19') >= 0) {
                return "AS excluded ON DUPLICATE KEY UPDATE";
            }
            else {
                return "ON DUPLICATE KEY UPDATE";
            }
        }
        if ($this->platform instanceof PostgreSQLPlatform) {
            $constraint = implode(',', $columns);
            return "ON CONFLICT($constraint) DO UPDATE SET";
        }
        return null;
    }

    /**
     * 参照構文（mysql における VALUES）を返す
     */
    public function getReferenceSyntax(string $column): ?string
    {
        if ($this->platform instanceof SqlitePlatform) {
            return "excluded.$column";
        }
        if ($this->platform instanceof MySQLPlatform) {
            if (version_compare($this->version, '8.0.19') >= 0) {
                return "excluded.$column";
            }
            else {
                return "VALUES($column)";
            }
        }
        if ($this->platform instanceof PostgreSQLPlatform) {
            return "EXCLUDED.$column";
        }
        return null;
    }

    /**
     * INSERT で使う SELECT を返す
     */
    public function getInsertSelectSyntax(array $column, string $condition): string
    {
        if ($this->platform instanceof MySQLPlatform) {
            if (version_compare($this->version, '8.0.19') >= 0) {
                return sprintf("SELECT * FROM (SELECT %s WHERE $condition)", array_sprintf($column, '%s AS %s', ', '));
            }
            else {
                return sprintf("SELECT %s WHERE $condition", implode(', ', $column));
            }
        }
        else {
            return sprintf("SELECT %s WHERE $condition", implode(', ', $column));
        }
    }

    /**
     * 自動採番を使うか切り替えるための SQL を返す
     */
    public function getIdentityInsertSQL(string $tableName, bool $onoffflag): string
    {
        // SQLServer のみ。自動カラムに能動的に値を指定できるか否かを設定
        if ($this->platform instanceof SQLServerPlatform) {
            return 'SET IDENTITY_INSERT ' . $tableName . ($onoffflag ? ' ON' : ' OFF');
        }

        throw new \LogicException(__METHOD__ . ' is not supported.');
    }

    /**
     * TRUNCATE 文を返す
     */
    public function getTruncateTableSQL(string $tableName, bool $cascade = false): string
    {
        // PostgreSql は他に合わせるため RESTART IDENTITY を付加する
        if ($this->platform instanceof PostgreSQLPlatform) {
            $tableName .= ' RESTART IDENTITY';
        }

        return $this->platform->getTruncateTableSQL($tableName, $cascade);
    }

    /**
     * ID シーケンス名を返す
     */
    public function getIdentitySequenceName(?string $tableName, ?string $columnName): ?string
    {
        if ($this->platform instanceof PostgreSQLPlatform) {
            return $tableName . '_' . $columnName . '_seq';
        }
        if ($this->platform instanceof OraclePlatform) {
            return strtoupper($tableName) . '_SEQ';
        }
        return null;
    }

    /**
     * インデックスヒント構文を返す
     */
    public function getIndexHintSQL(string|array $index_name, string $mode = 'FORCE'): string
    {
        $index_name = implode(', ', arrayize($index_name));

        if ($this->platform instanceof MySQLPlatform) {
            return "$mode INDEX ($index_name)";
        }
        if ($this->platform instanceof SQLServerPlatform) {
            return "WITH (INDEX($index_name))";
        }

        // Sqlite はヒントに対応していないし、 PostgreSql は特殊なプラグインが必要

        return '';
    }

    /**
     * クエリにロック構文を付加して返す
     */
    public function appendLockSuffix(string $query, int|LockMode $lockmode, string $lockoption): string
    {
        // SQLServer はクエリ自体ではなく from 句に紐づくので不要
        if ($this->platform instanceof SQLServerPlatform) {
            return $query;
        }

        if ($lockmode === LockMode::NONE) {
            return $query;
        }

        switch (true) {
            case $lockmode === LockMode::PESSIMISTIC_READ:
                $query .= match (true) {
                    $this->platform instanceof SqlitePlatform     => ' /* lock for read */',
                    $this->platform instanceof MySQLPlatform      => strlen($lockoption) ? ' FOR SHARE' : ' LOCK IN SHARE MODE', // for compatible
                    $this->platform instanceof PostgreSQLPlatform => ' FOR SHARE',
                    default                                       => ' FOR UPDATE'
                };
                break;
            case $lockmode === LockMode::PESSIMISTIC_WRITE:
                $query .= match (true) {
                    $this->platform instanceof SqlitePlatform => ' /* lock for write */',
                    default                                   => ' FOR UPDATE'
                };
                break;
        }

        return $query . concat(' ', $lockoption);
    }

    public function getDateTimeTzFormats(): array
    {
        if ($this->platform instanceof SqlitePlatform) {
            return ['Y-m-d', 'H:i:s', ''];
        }
        if ($this->platform instanceof MySQLPlatform) {
            return ['Y-m-d', 'H:i:s.u', ''];
        }
        if ($this->platform instanceof PostgreSQLPlatform) {
            return ['Y-m-d', 'H:i:s.u', 'O'];
        }
        if ($this->platform instanceof SQLServerPlatform) {
            return ['Y-m-d', 'H:i:s.u', 'P'];
        }

        return ['Y-m-d', 'H:i:s.u', 'P'];
    }

    /**
     * 条件配列を結合した Expression を返す
     */
    public function getPrimaryCondition(array $wheres, string $prefix = ''): Expression
    {
        if (!$wheres) {
            return Expression::new('');
        }

        $prefix = concat($prefix, '.');
        $first = reset($wheres);
        $params = [];

        // カラムが1つなら IN で事足りるので場合分け
        if (count($first) === 1) {
            [$key] = first_keyvalue($first);
            $andconds = [];
            foreach ($wheres as $where) {
                $v = $where[$key];
                if ($v instanceof Queryable) {
                    $andconds[] = $v->merge($params);
                }
                else {
                    $andconds[] = '?';
                    $params[] = $v;
                }
            }
            $binds = implode(', ', $andconds);
            $condition = $prefix . (count($andconds) === 1 ? "$key = $binds" : "$key IN ($binds)");
        }
        // カラムが2つ以上なら ((c1 = v11 AND c2 = v12) OR (c1 = v21 AND c2 = v22))
        else {
            if (count($wheres) > 1 && $this->supportsRowConstructor()) {
                $andconds = [];
                foreach ($wheres as $where) {
                    $orconds = [];
                    foreach ($where as $v) {
                        if ($v instanceof Queryable) {
                            $orconds[] = $v->merge($params);
                        }
                        else {
                            $orconds[] = '?';
                            $params[] = $v;
                        }
                    }
                    $andconds[] = '(' . implode(', ', $orconds) . ')';
                }
                $binds = implode(', ', $andconds);
                $key = implode(', ', array_keys(array_strpad($first, $prefix)));
                $condition = "($key) IN ($binds)";
            }
            else {
                $andconds = [];
                foreach ($wheres as $where) {
                    $orconds = [];
                    foreach ($where as $c => $v) {
                        if ($v instanceof Queryable) {
                            $orconds[] = $prefix . "$c = " . $v->merge($params);
                        }
                        else {
                            $orconds[] = $prefix . "$c = " . '?';
                            $params[] = $v;
                        }
                    }
                    $andconds[] = '(' . implode(' AND ', $orconds) . ')';
                }
                $condition = implode(' OR ', $andconds);
            }
        }
        return Expression::new($condition, $params);
    }

    /**
     * GROUP_CONCAT 構文を返す
     *
     * @param string|array $expr 結合式
     * @param ?string $separator セパレータ文字
     * @param string|array|null order 句。これが活きるのは mysql のみ
     * @return string GROUP_CONCAT 構文
     */
    public function getGroupConcatSyntax(string|array $expr, ?string $separator = null, $order = null): string
    {
        $separator = (string) $separator;
        $qseparator = $this->platform->quoteStringLiteral($separator);

        if ($this->platform instanceof SqlitePlatform) {
            if ($separator === "") {
                return "GROUP_CONCAT($expr)";
            }
            return "GROUP_CONCAT($expr, $qseparator)";
        }
        if ($this->platform instanceof MySQLPlatform) {
            $query = "GROUP_CONCAT(" . implode(', ', arrayize($expr));

            if ($order !== null) {
                $by = [];
                foreach (arrayize($order) as $col => $ord) {
                    $by[] = is_int($col) ? "$ord ASC" : "$col $ord";
                }
                $query .= " ORDER BY " . implode(', ', $by);
            }

            if ($separator !== "") {
                $query .= " SEPARATOR $qseparator";
            }

            $query .= ")";

            return $query;
        }
        if ($this->platform instanceof PostgreSQLPlatform) {
            $query = "ARRAY_AGG($expr)";
            if ($separator !== "") {
                $query = "ARRAY_TO_STRING($query, $qseparator)";
            }
            return $query;
        }

        throw new \LogicException(__METHOD__ . ' is not supported.');
    }

    /**
     * @deprecated use getSpaceshipExpression
     * @codeCoverageIgnore
     */
    public function getSpaceshipSyntax(string $column): string
    {
        return (string) $this->getSpaceshipExpression($column, null);
    }

    /**
     * 再帰 WITH 句を返す
     */
    public function getWithRecursiveSyntax(): string
    {
        // 他 DBMS は無条件で RECURSIVE をつけても大丈夫だが Sqlserver はだめっぽい
        if ($this->platform instanceof SQLServerPlatform) {
            return "WITH";
        }
        return "WITH RECURSIVE";
    }

    /**
     * null 許容演算子（<=>）表現を返す
     *
     * $column はカラム名を想定しており、エスケープされないので注意すること。
     */
    public function getSpaceshipExpression(string $column, $value): Expression
    {
        if ($this->platform instanceof SqlitePlatform) {
            return Expression::new("$column IS ?", [$value]);
        }
        if ($this->platform instanceof MySQLPlatform) {
            return Expression::new("$column <=> ?", [$value]);
        }
        if ($this->platform instanceof PostgreSQLPlatform) {
            return Expression::new("$column IS NOT DISTINCT FROM ?", [$value]);
        }
        if ($this->platform instanceof SQLServerPlatform && version_compare($this->version, '16') >= 0) {
            return Expression::new("$column IS NOT DISTINCT FROM ?", [$value]);
        }
        return Expression::new("($column IS NULL AND ? IS NULL) OR $column = ?", [$value, $value]);
    }

    /**
     * count 表現を返す
     */
    public function getCountExpression(string $column): Expression
    {
        // avg 以外は移譲
        return Expression::new("COUNT($column)");
    }

    /**
     * min 表現を返す
     */
    public function getMinExpression(string $column): Expression
    {
        // avg 以外は移譲
        return Expression::new("MIN({$column})");
    }

    /**
     * max 表現を返す
     */
    public function getMaxExpression(string $column): Expression
    {
        // avg 以外は移譲
        return Expression::new("MAX({$column})");
    }

    /**
     * sum 表現を返す
     */
    public function getSumExpression(string $column): Expression
    {
        // avg 以外は移譲
        return Expression::new("SUM({$column})");
    }

    /**
     * avg 表現を返す
     */
    public function getAvgExpression(string $column): Expression
    {
        // SQLServer は元の型が生きるのでキャストを加える
        if ($this->platform instanceof SQLServerPlatform) {
            $column = "CAST($column AS float)";
        }
        return Expression::new("AVG({$column})");
    }

    /**
     * JSON 表現を返す
     *
     * 大抵の RDBMS は (key1, value1, key2, value2) のような構文になっており、php レイヤーで使うのに少々不便。
     * このメソッドを使えば (key1 => value1, key2 => value) 形式でオブジェクト化できる。
     */
    public function getJsonObjectExpression(array $keyvalues): Expression
    {
        [$json_object, $delimiter] = match (true) {
            $this->platform instanceof SqlitePlatform     => ['JSON_OBJECT', ','],
            $this->platform instanceof MySQLPlatform      => ['JSON_OBJECT', ','],
            $this->platform instanceof PostgreSQLPlatform => ['JSON_BUILD_OBJECT', ','],
            $this->platform instanceof SQLServerPlatform  => ['JSON_OBJECT', ':'],
            default                                       => throw new \LogicException(__METHOD__ . ' is not supported.'),
        };

        $params = [];
        $kvpairs = [];
        foreach ($keyvalues as $k => $v) {
            $params[] = $k;
            if ($v instanceof Queryable) {
                $v = $v->merge($params);
            }
            $placeholder = '?';
            // なぜか PostgreSQL で BindType が効かないのでキャスト
            if ($this->platform instanceof PostgreSQLPlatform) {
                $placeholder = "CAST($placeholder AS TEXT)";
            }
            $kvpairs[] = "$placeholder$delimiter $v";
        }
        return new Expression("$json_object(" . implode(', ', $kvpairs) . ")", $params);
    }

    /**
     * JSON 集約表現を返す
     *
     * $key を指定すると配列ではなくオブジェクトになる。
     */
    public function getJsonAggExpression(array $keyvalues, $key = null): Expression
    {
        [$json_array_agg, $json_object_agg] = match (true) {
            $this->platform instanceof SqlitePlatform     => ['JSON_GROUP_ARRAY', 'JSON_GROUP_OBJECT'],
            $this->platform instanceof MySQLPlatform      => ['JSON_ARRAYAGG', 'JSON_OBJECTAGG'],
            $this->platform instanceof PostgreSQLPlatform => ['JSON_AGG', 'JSON_OBJECT_AGG'],
            $this->platform instanceof SQLServerPlatform  => ['JSON_ARRAYAGG', 'JSON_OBJECTAGG'],
            default                                       => throw new \LogicException(__METHOD__ . ' is not supported.'),
        };

        $params = [];
        if ($key instanceof Queryable) {
            $key = $key->merge($params);
        }
        $kvpairs = $this->getJsonObjectExpression($keyvalues)->merge($params);
        if ($key === null) {
            return new Expression("$json_array_agg($kvpairs)", $params);
        }
        else {
            // なぜか PostgreSQL で BindType が効かないのでキャスト
            if ($this->platform instanceof PostgreSQLPlatform) {
                $key = "CAST($key AS TEXT)";
            }
            return new Expression("$json_object_agg($key, $kvpairs)", $params);
        }
    }

    /**
     * 文字列結合句を返す
     */
    public function getConcatExpression(string|Queryable ...$args): Queryable
    {
        $count = count($args);

        if ($count === 0) {
            throw new \InvalidArgumentException('$args must be greater than 0.');
        }
        if ($count === 1) {
            return is_string($args[0]) ? Expression::new($args[0]) : $args[0];
        }

        $params = [];
        $args = array_map(function ($arg) use (&$params) {
            if ($arg instanceof Queryable) {
                $arg = $arg->merge($params);
            }
            // SQLServer は数値を文字列として結合できないのでキャストを加える
            if ($this->platform instanceof SQLServerPlatform) {
                $arg = "CAST($arg as varchar)";
            }
            return $arg;
        }, $args);

        return Expression::new($this->platform->getConcatExpression(...$args), $params);
    }

    /**
     * 正規表現を返す
     */
    public function getRegexpExpression(string $column, string $pattern): Expression
    {
        if ($this->platform instanceof SqlitePlatform) {
            return Expression::new("$column REGEXP ?", [$pattern]);
        }
        if ($this->platform instanceof MySQLPlatform) {
            return Expression::new("REGEXP_LIKE($column, ?, 'i')", [$pattern]);
        }
        if ($this->platform instanceof PostgreSQLPlatform) {
            return Expression::new("$column ~* ?", [$pattern]);
        }

        throw new \LogicException(__METHOD__ . ' is not supported.');
    }

    /**
     * binary 表現を返す
     */
    public function getBinaryExpression(string $data): Expression
    {
        // SQLServer はキャストしなければ binary として扱えない
        if ($this->platform instanceof SQLServerPlatform) {
            return Expression::new('CAST(? as VARBINARY(MAX))', [$data]);
        }
        return Expression::new($data);
    }

    /**
     * now 表現を返す
     *
     * dbal では非推奨だがたまに使うことがある。
     * ローカルタイム限定で形式も Y-m-d H:i:s.v のみ。
     */
    public function getNowExpression(int $precision = 0): Expression
    {
        if ($this->platform instanceof SqlitePlatform) {
            assert($precision === 0 || $precision === 3);
            $f = $precision === 0 ? '%S' : '%f';
            return Expression::new("strftime('%Y-%m-%d %H:%M:$f', datetime('now', 'localtime'))");
        }
        if ($this->platform instanceof MySQLPlatform) {
            return Expression::new("NOW($precision)"); // パラメータで渡せない？
        }
        if ($this->platform instanceof PostgreSQLPlatform) {
            return Expression::new("LOCALTIMESTAMP($precision)"); // パラメータで渡せない？
        }
        if ($this->platform instanceof SQLServerPlatform) {
            $f = $precision ? '.' . str_repeat('f', $precision) : "";
            return Expression::new("CAST(FORMAT(GETDATE(), 'yyyy-MM-dd HH:mm:ss$f') as DATETIME)"); // キャストしないと日付型にならない
        }

        return Expression::new("NOW()");
    }

    /**
     * sleep 表現を返す
     */
    public function getSleepExpression(float $second): Expression
    {
        if ($this->platform instanceof MySQLPlatform) {
            return Expression::new("SLEEP(?)", $second);
        }
        if ($this->platform instanceof PostgreSQLPlatform) {
            return Expression::new("pg_sleep(?)", $second);
        }

        throw new \LogicException(__METHOD__ . ' is not supported.');
    }

    /**
     * random 表現（[0~1.0)）を返す
     */
    public function getRandomExpression(?int $seed): Expression
    {
        // Sqlite にシード設定方法は存在しない
        if ($this->platform instanceof SqlitePlatform) {
            return Expression::new("(0.5 - RANDOM() / CAST(-9223372036854775808 AS REAL) / 2)");
        }
        // MySQL のみ単体クエリで setseed+random が実現できる
        if ($this->platform instanceof MySQLPlatform) {
            return $seed === null ? Expression::new("RAND()") : Expression::new("RAND(?)", $seed);
        }
        // PostgreSQL は setseed があるが、SELECT 句で呼んでも毎回 setseed され random が同じ値になってしまう
        if ($this->platform instanceof PostgreSQLPlatform) {
            return Expression::new("random()");
        }
        // SQLServer の RAND はシードを与えなければ同じ値を返してしまう
        if ($this->platform instanceof SQLServerPlatform) {
            return Expression::new("RAND(CHECKSUM(NEWID()))");
        }

        throw new \LogicException(__METHOD__ . ' is not supported.');
    }

    /**
     * AUTO_INCREMENT のセット構文を返す
     */
    public function getResetSequenceExpression(string $tableName, string $columnName, int $seq): array
    {
        if ($this->platform instanceof SqlitePlatform) {
            $seq--;
            return [
                "DELETE FROM sqlite_sequence WHERE name = '$tableName'",
                "INSERT INTO sqlite_sequence (name, seq) VALUES ('$tableName', $seq)",
            ];
        }
        if ($this->platform instanceof MySQLPlatform) {
            return ["ALTER TABLE $tableName AUTO_INCREMENT = $seq"];
        }
        if ($this->platform instanceof PostgreSQLPlatform) {
            $sequenceName = $this->getIdentitySequenceName($tableName, $columnName);
            return ["SELECT setval('$sequenceName', $seq, false)"];
        }
        if ($this->platform instanceof SQLServerPlatform) {
            $seq--;
            return ["DBCC CHECKIDENT($tableName, RESEED, $seq)"];
        }

        throw new \DomainException($this->getName() . ' is not supported');
    }

    /**
     * 外部キー有効無効切替構文を返す
     */
    public function getSwitchForeignKeyExpression(bool $enabled, ?string $table_name = null, ?string $fkname = null): array
    {
        if ($this->platform instanceof SqlitePlatform) {
            return ['PRAGMA foreign_keys = ' . ($enabled ? 'true' : 'false')];
        }
        if ($this->platform instanceof MySQLPlatform) {
            return ["SET SESSION foreign_key_checks = " . ($enabled ? '1' : '0')];
        }
        if ($this->platform instanceof PostgreSQLPlatform) {
            assert($fkname !== null);
            return ["SET CONSTRAINTS $fkname " . ($enabled ? 'IMMEDIATE' : 'DEFERRED')];
        }
        if ($this->platform instanceof SQLServerPlatform) {
            assert($table_name !== null);
            $fkname ??= 'ALL';
            return ["ALTER TABLE $table_name " . ($enabled ? 'WITH CHECK CHECK' : 'NOCHECK') . " CONSTRAINT $fkname"];
        }

        return [];
    }

    /**
     * IGNORE 構文を返す
     */
    public function getIgnoreSyntax(): string
    {
        if ($this->platform instanceof SqlitePlatform) {
            return 'OR IGNORE';
        }
        if ($this->platform instanceof MySQLPlatform) {
            return 'IGNORE';
        }

        throw new \DomainException($this->getName() . ' is not supported');
    }

    /**
     * 与えられた文をコメント化する
     */
    public function commentize(string $comment, bool $cstyle = false): string
    {
        // Cスタイルは全 DBMS でサポートしてるっぽい
        if ($cstyle) {
            $s = '/*';
            $e = '*/';
        }
        else {
            $s = "--";
            $e = "\n";
        }
        $comment = str_replace($e, ' ', $comment);
        return "$s $comment $e";
    }

    /**
     * 挿入データと更新データで更新用カラム列を生成する
     */
    public function convertMergeData(array $insertData, array $updateData): array
    {
        // 指定されているならそのまま返せば良い
        if ($updateData) {
            return $updateData;
        }

        // 指定されていない場合は $insertData を返す。ただし、データが長大な場合、2重に bind されることになり無駄なので参照構文を使う
        return array_each($insertData, function (&$carry, $v, $k) {
            $reference = $this->getReferenceSyntax($k);
            $carry[$k] = $reference === null ? $v : Expression::new($reference);
        }, []);
    }

    /**
     * EXISTS 構文を SELECT で使用できるようにする
     */
    public function convertSelectExistsQuery(string|Queryable $exists): Expression
    {
        $params = [];
        if ($exists instanceof Queryable) {
            $params = $exists->getParams();
        }

        // SQLServer は述語部でしか EXISTS が使えないので CASE で対応
        if ($this->platform instanceof SQLServerPlatform) {
            $exists = "CASE WHEN ($exists) THEN 1 ELSE 0 END";
        }

        return Expression::new($exists, $params);
    }
}
