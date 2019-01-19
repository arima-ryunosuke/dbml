<?php

namespace ryunosuke\dbml\Metadata;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use ryunosuke\dbml\Query\Expression\Expression;
use ryunosuke\dbml\Query\Expression\SelectOption;
use ryunosuke\dbml\Query\Queryable;
use ryunosuke\dbml\Query\QueryBuilder;
use function ryunosuke\dbml\array_each;
use function ryunosuke\dbml\array_sprintf;
use function ryunosuke\dbml\arrayize;
use function ryunosuke\dbml\concat;
use function ryunosuke\dbml\first_keyvalue;

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
    /** @var AbstractPlatform 元 platform */
    private $platform;

    /**
     * コンストラクタ
     *
     * @param AbstractPlatform $platform 元 platform
     */
    public function __construct(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * 元 platform を取得する
     *
     * @return AbstractPlatform 元 platform
     */
    public function getWrappedPlatform()
    {
        return $this->platform;
    }

    /**
     * AUTO_INCREMENT な列を明示指定して UPDATE できるか否かを返す
     *
     * @return bool AUTO_INCREMENT な列を明示指定して UPDATE できるなら true
     */
    public function supportsIdentityUpdate()
    {
        if ($this->platform instanceof SQLServerPlatform) {
            return false;
        }
        return true;
    }

    /**
     * REPLACE が使えるか否かを返す
     *
     * @return bool REPLACE が使えるなら true
     */
    public function supportsReplace()
    {
        if ($this->platform instanceof SqlitePlatform) {
            return true;
        }
        if ($this->platform instanceof MySqlPlatform) {
            return true;
        }
        return false;
    }

    /**
     * MERGE が使えるか否かを返す
     *
     * @return bool MERGE が使えるなら true
     */
    public function supportsMerge()
    {
        if ($this->platform instanceof MySqlPlatform) {
            return true;
        }
        if ($this->platform instanceof PostgreSqlPlatform) {
            return true;
        }
        return false;
    }

    /**
     * IGNORE が使えるか否かを返す
     *
     * @return bool IGNORE が使えるなら true
     */
    public function supportsIgnore()
    {
        if ($this->platform instanceof SqlitePlatform) {
            return true;
        }
        if ($this->platform instanceof MySqlPlatform) {
            return true;
        }
        return false;
    }

    /**
     * UPDATE で更新されなかった時(条件一致しなかった時ではなく更新行に変化がなかった時) 0 を返すか否かを返す
     *
     * PDO::MYSQL_ATTR_FOUND_ROWS にも依存するけどここでは見ない（ほぼテスト用なので）
     *
     * @return bool UPDATE で更新されなかった時(条件一致しなかった時ではなく更新行に変化がなかった時) 0 を返すなら true
     */
    public function supportsZeroAffectedUpdate()
    {
        if ($this->platform instanceof MySqlPlatform) {
            return true;
        }
        return false;
    }

    /**
     * UPDATE + JOIN をサポートするか否かを返す
     *
     * @return bool UPDATE + JOIN をサポートするなら true
     */
    public function supportsUpdateJoin()
    {
        if ($this->platform instanceof MySqlPlatform || $this->platform instanceof SQLServerPlatform) {
            return true;
        }
        return false;
    }

    /**
     * DELETE + JOIN をサポートするか否かを返す
     *
     * @return bool DELETE + JOIN をサポートするなら true
     */
    public function supportsDeleteJoin()
    {
        if ($this->platform instanceof MySqlPlatform || $this->platform instanceof SQLServerPlatform) {
            return true;
        }
        return false;
    }

    /**
     * UPDATE + ORDER BY,LIMIT をサポートするか否かを返す
     *
     * @return bool UPDATE + ORDER BY,LIMIT をサポートするなら true
     */
    public function supportsUpdateLimit()
    {
        if ($this->platform instanceof MySqlPlatform || $this->platform instanceof SQLServerPlatform) {
            return true;
        }
        return false;
    }

    /**
     * DELETE + ORDER BY,LIMIT をサポートするか否かを返す
     *
     * @return bool DELETE + ORDER BY,LIMIT をサポートするなら true
     */
    public function supportsDeleteLimit()
    {
        if ($this->platform instanceof MySqlPlatform || $this->platform instanceof SQLServerPlatform) {
            return true;
        }
        return false;
    }

    /**
     * TRUNCATE 文で自動採番列がリセットされるか否かを返す
     *
     * @return bool TRUNCATE で自動採番列がリセットされるなら true
     */
    public function supportsResetAutoIncrementOnTruncate()
    {
        // Sqlite のみリセットされない
        if ($this->platform instanceof SqlitePlatform) {
            return false;
        }

        return true;
    }

    /**
     * \PDO::ATTR_FETCH_TABLE_NAMES が有効か否かを返す
     *
     * @return bool \PDO::ATTR_FETCH_TABLE_NAMES が有効なら true
     */
    public function supportsTableNameAttribute()
    {
        // エラーは出ないが MySql しか対応していないっぽい？
        if ($this->platform instanceof MySqlPlatform) {
            return true;
        }
        return false;
    }

    /**
     * 必要に応じて識別子をエスケープする
     *
     * @param string $word エスケープする文字列
     * @return string エスケープされた文字列
     */
    public function quoteIdentifierIfNeeded($word)
    {
        if (!is_string($word) || strlen($word) === 0) {
            return $word;
        }

        if (!preg_match('#^[_a-z0-9]+$#ui', $word) || $this->platform->getReservedKeywordsList()->isKeyword($word)) {
            return $this->platform->quoteSingleIdentifier($word);
        }

        return $word;
    }

    /**
     * LIKE エスケープする
     *
     * @param string|Expression|array $word エスケープする文字列
     * @param string $escaper LIKE のエスケープ文字
     * @return string LIKE エスケープされた文字列
     */
    public function escapeLike($word, $escaper = '\\')
    {
        if (is_array($word)) {
            return implode('%', array_map([$this, 'escapeLike'], $word));
        }

        if ($word instanceof Expression) {
            return "$word";
        }

        // SQLServer は [] でエスケープ可能
        if (func_num_args() === 1 && $this->platform instanceof SQLServerPlatform) {
            $map = [
                '_' => '[_]',
                '%' => '[%]',
                '[' => '[[]',
            ];
            return strtr($word, $map);
        }

        $map = [
            $escaper => $escaper . $escaper,
            '_'      => $escaper . '_',
            '%'      => $escaper . '%',
        ];
        // SQLServer は [] で文字範囲検索もできるのでそれもエスケープ
        if ($this->platform instanceof SQLServerPlatform) {
            $map['['] = $escaper . '[';
        }
        return strtr($word, $map);
    }

    /**
     * CALC_FOUND_ROWS が使える場合にその SelectOption を返す
     *
     * @return SelectOption CALC_FOUND_ROWS が使えるなら SelectOption::SQL_CALC_FOUND_ROWS
     */
    public function getFoundRowsOption()
    {
        if ($this->platform instanceof MySqlPlatform) {
            return SelectOption::SQL_CALC_FOUND_ROWS();
        }
        return null;
    }

    /**
     * CALC_FOUND_ROWS が使える場合にその関数名を返す
     *
     * @return string CALC_FOUND_ROWS が使えるなら FOUND_ROWS
     */
    public function getFoundRowsQuery()
    {
        if ($this->platform instanceof MySqlPlatform) {
            return 'SELECT FOUND_ROWS()';
        }
        return '';
    }

    /**
     * MERGE 構文を返す
     *
     * @param string $tableName テーブル名
     * @param array $insertData INSERT 時の配列
     * @param array $updateData UPDATE 時の配列
     * @param string|array $constraint 一意制約
     * @return string|bool MERGE 構文に対応してるなら文字列、対応していないなら false
     */
    public function getMergeSQL($tableName, $insertData, $updateData, $constraint = null)
    {
        if ($this->platform instanceof MySqlPlatform) {
            $insertSql = array_sprintf($insertData, '%2$s = %1$s', ', ');
            $updateSql = array_sprintf($updateData, '%2$s = %1$s', ', ');
            return "INSERT INTO $tableName SET $insertSql ON DUPLICATE KEY UPDATE $updateSql";
        }
        if ($this->platform instanceof PostgreSqlPlatform) {
            $insertKeys = implode(', ', array_keys($insertData));
            $insertVals = implode(', ', $insertData);
            $updateSql = array_sprintf($updateData, '%2$s = %1$s', ', ');
            $constraint = is_array($constraint) ? '(' . implode(',', $constraint) . ')' : $constraint;
            return "INSERT INTO $tableName ($insertKeys) VALUES ($insertVals) ON CONFLICT ON CONSTRAINT $constraint DO UPDATE SET $updateSql";
        }
        return false;
    }

    /**
     * 自動採番を使うか切り替えるための SQL を返す
     *
     * @param string $tableName テーブル名
     * @param bool $onoffflag スイッチフラグ
     * @return string 自動採番を使うか切り替えるための SQL. SQLServer 以外は例外
     */
    public function getIdentityInsertSQL($tableName, $onoffflag)
    {
        // SQLServer のみ。自動カラムに能動的に値を指定できるか否かを設定
        if ($this->platform instanceof SQLServerPlatform) {
            return 'SET IDENTITY_INSERT ' . $tableName . ($onoffflag ? ' ON' : ' OFF');
        }

        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * TRUNCATE 文を返す
     *
     * @param string $tableName テーブル名
     * @param bool|false $cascade PostgreSql の場合に RESTART IDENTITY を付与するか
     * @return string TRUNCATE 文
     */
    public function getTruncateTableSQL($tableName, $cascade = false)
    {
        // PostgreSql は他に合わせるため RESTART IDENTITY を付加する
        if ($this->platform instanceof PostgreSqlPlatform) {
            $tableName .= ' RESTART IDENTITY';
        }

        return $this->platform->getTruncateTableSQL($tableName, $cascade);
    }

    /**
     * ID シーケンス名を返す
     *
     * @param string $tableName テーブル名
     * @param string $columnName カラム名
     * @return string|null シーケンス名。自動シーケンスに対応していない場合は null
     */
    public function getIdentitySequenceName($tableName, $columnName)
    {
        // PostgreSql は table_id_seq で自動シーケンスされる
        if ($this->platform->usesSequenceEmulatedIdentityColumns()) {
            return $this->platform->getIdentitySequenceName($tableName, $columnName);
        }

        return null;
    }

    /**
     * インデックスヒント構文を返す
     *
     * @param string|array $index_name インデックス名
     * @param string $mode HINT/FORCE などのモード名
     * @return string インデックスヒント構文
     */
    public function getIndexHintSQL($index_name, $mode = 'FORCE')
    {
        $index_name = implode(', ', arrayize($index_name));

        if ($this->platform instanceof MySqlPlatform) {
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
     *
     * @param string $query クエリ
     * @param int $lockmode ロック構文
     * @param string $lockoption ロックオプション
     * @return string クエリにロック構文を付加した文字列
     */
    public function appendLockSuffix($query, $lockmode, $lockoption)
    {
        // SQLServer はクエリ自体ではなく from 句に紐づくので不要
        if ($this->platform instanceof SQLServerPlatform) {
            return $query;
        }

        if ($lockmode === null) {
            return $query;
        }

        switch (true) {
            case $lockmode === LockMode::PESSIMISTIC_READ:
                $query .= ' ' . $this->platform->getReadLockSQL();
                break;
            case $lockmode === LockMode::PESSIMISTIC_WRITE:
                $query .= ' ' . $this->platform->getWriteLockSQL();
                break;
        }

        return $query . concat(' ', $lockoption);
    }

    /**
     * 条件配列を結合した Expression を返す
     *
     * OR の方は行値式で書いても良いが、インデックスをサポートしない DBMS があるので OR のままとする。
     *
     * @param array $wheres WHERE 配列
     * @param string $prefix 修飾子
     * @return Expression WHERE 条件をバインドパラメータに持つ Expression インスタンス
     */
    public function getPrimaryCondition($wheres, $prefix = '')
    {
        if (!$wheres) {
            return new Expression('');
        }

        $first = reset($wheres);
        $params = [];

        // カラムが1つなら IN で事足りるので場合分け
        if (count($first) === 1) {
            list($key) = first_keyvalue($first);
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
            $condition = concat($prefix, '.') . (count($andconds) === 1 ? "$key = $binds" : "$key IN ($binds)");
        }
        // カラムが2つ以上なら ((c1 = v11 AND c2 = v12) OR (c1 = v21 AND c2 = v22))
        else {
            $andconds = [];
            foreach ($wheres as $where) {
                $orconds = [];
                foreach ($where as $c => $v) {
                    if ($v instanceof Queryable) {
                        $orconds[] = concat($prefix, '.') . "$c = " . $v->merge($params);
                    }
                    else {
                        $orconds[] = concat($prefix, '.') . "$c = " . '?';
                        $params[] = $v;
                    }
                }
                $andconds[] = '(' . implode(' AND ', $orconds) . ')';
            }
            $condition = implode(' OR ', $andconds);
        }
        return new Expression($condition, $params);
    }

    /**
     * CASE ～ END 構文
     *
     * @param string|Queryable $expr 対象カラム。null 指定時は CASE WHEN 構文になる
     * @param array $whens [条件 => 値]の配列
     * @param mixed $else else 句。未指定時は else 句なし
     * @return Expression CASE ～ END 構文の Expression インスタンス
     */
    public function getCaseWhenSyntax($expr, array $whens, $else = null)
    {
        $params = [];
        $entry = function ($expr, $raw = false) use (&$params) {
            if ($expr instanceof Queryable) {
                return $expr->merge($params);
            }
            elseif ($raw) {
                return $expr;
            }
            else {
                $params[] = $expr;
                return '?';
            }
        };

        $query = '(CASE ';
        $query .= $expr === null ? '' : "{$entry($expr, true)} ";
        $query .= array_reduce(array_keys($whens), function ($carry, $cond) use ($whens, $expr, $entry) {
            return "{$carry}WHEN " . ($expr === null ? $cond : $entry($cond)) . " THEN {$entry($whens[$cond])} ";
        });
        $query .= $else === null ? '' : "ELSE {$entry($else)} ";
        $query .= 'END)';

        return new Expression($query, $params);
    }

    /**
     * GROUP_CONCAT 構文を返す
     *
     * @param string|array $expr 結合式
     * @param string $separator セパレータ文字
     * @param string|array order 句。これが活きるのは mysql のみ
     * @return string GROUP_CONCAT 構文
     */
    public function getGroupConcatSyntax($expr, $separator = null, $order = null)
    {
        $qseparator = $this->platform->quoteStringLiteral($separator);

        if ($this->platform instanceof SqlitePlatform) {
            if ($separator === null) {
                return "GROUP_CONCAT($expr)";
            }
            return "GROUP_CONCAT($expr, $qseparator)";
        }
        if ($this->platform instanceof MySqlPlatform) {
            $query = "GROUP_CONCAT(" . implode(', ', arrayize($expr));

            if ($order !== null) {
                $by = [];
                foreach (arrayize($order) as $col => $ord) {
                    $by[] = is_int($col) ? "$ord ASC" : "$col $ord";
                }
                $query .= " ORDER BY " . implode(', ', $by);
            }

            if ($separator !== null) {
                $query .= " SEPARATOR $qseparator";
            }

            $query .= ")";

            return $query;
        }
        if ($this->platform instanceof PostgreSqlPlatform) {
            $query = "ARRAY_AGG($expr)";
            if ($separator !== null) {
                $query = "ARRAY_TO_STRING($query, $qseparator)";
            }
            return $query;
        }

        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * null 許容演算子（<=>）構文を返す
     *
     * $column はカラム名を想定しており、エスケープされないので注意すること。
     *
     * @param string $column 左辺値
     * @return string null 許容演算子
     */
    public function getSpaceshipSyntax($column)
    {
        if ($this->platform instanceof MySqlPlatform) {
            return "$column <=> ?";
        }
        return "$column IS NULL OR $column = ?";
    }

    /**
     * count 表現を返す
     *
     * @param string $column カラム名
     * @return Expression COUNT Expression
     */
    public function getCountExpression($column)
    {
        // avg 以外は移譲
        return new Expression($this->platform->getCountExpression($column));
    }

    /**
     * min 表現を返す
     *
     * @param string $column カラム名
     * @return Expression MIN Expression
     */
    public function getMinExpression($column)
    {
        // avg 以外は移譲
        return new Expression($this->platform->getMinExpression($column));
    }

    /**
     * max 表現を返す
     *
     * @param string $column カラム名
     * @return Expression MAX Expression
     */
    public function getMaxExpression($column)
    {
        // avg 以外は移譲
        return new Expression($this->platform->getMaxExpression($column));
    }

    /**
     * sum 表現を返す
     *
     * @param string $column カラム名
     * @return Expression SUM Expression
     */
    public function getSumExpression($column)
    {
        // avg 以外は移譲
        return new Expression($this->platform->getSumExpression($column));
    }

    /**
     * avg 表現を返す
     *
     * @param string $column カラム名
     * @return Expression AVG Expression
     */
    public function getAvgExpression($column)
    {
        // SQLServer は元の型が生きるのでキャストを加える
        if ($this->platform instanceof SQLServerPlatform) {
            $column = "CAST($column AS float)";
        }
        return new Expression($this->platform->getAvgExpression($column));
    }

    /**
     * 文字列結合句を返す
     *
     * @param string|array $args CONCAT の引数となる配列
     * @return Expression CONCAT Expression
     */
    public function getConcatExpression($args)
    {
        $args = is_array($args) ? $args : func_get_args();
        $count = count($args);

        if ($count === 0) {
            throw new \InvalidArgumentException('$args must be greater than 0.');
        }
        if ($count === 1) {
            return reset($args);
        }

        // SQLServer は数値を文字列として結合できないのでキャストを加える
        if ($this->platform instanceof SQLServerPlatform) {
            $args = array_map(function ($arg) {
                return "CAST($arg as varchar)";
            }, $args);
        }

        return new Expression($this->platform->getConcatExpression(...$args));
    }

    /**
     * AUTO_INCREMENT のセット構文を返す
     *
     * @param string $tableName テーブル名
     * @param string $columnName カラム名
     * @param int $seq セットしたい AUTO_INCREMENT 番号
     * @return array AUTO_INCREMENT をセットする一連のクエリ文字列配列
     */
    public function getResetSequenceExpression($tableName, $columnName, $seq)
    {
        $seq = intval($seq);

        if ($this->platform instanceof SqlitePlatform) {
            $seq--;
            return [
                "DELETE FROM sqlite_sequence WHERE name = '$tableName'",
                "INSERT INTO sqlite_sequence (name, seq) VALUES ('$tableName', $seq)",
            ];
        }
        if ($this->platform instanceof MySqlPlatform) {
            return ["ALTER TABLE $tableName AUTO_INCREMENT = $seq"];
        }
        if ($this->platform instanceof PostgreSqlPlatform) {
            $sequenceName = $this->platform->getIdentitySequenceName($tableName, $columnName);
            return ["SELECT setval('$sequenceName', $seq, false)"];
        }
        if ($this->platform instanceof SQLServerPlatform) {
            $seq--;
            return ["DBCC CHECKIDENT($tableName, RESEED, $seq)"];
        }

        throw new \DomainException($this->platform->getName() . ' is not supported');
    }

    /**
     * IGNORE 構文を返す
     *
     * @return string IGNORE 構文
     */
    public function getIgnoreSyntax()
    {
        if ($this->platform instanceof SqlitePlatform) {
            return 'OR IGNORE';
        }
        if ($this->platform instanceof MySqlPlatform) {
            return 'IGNORE';
        }

        throw new \DomainException($this->platform->getName() . ' is not supported');
    }

    /**
     * 与えられた文をコメント化する
     *
     * @param string $comment コメント文字列
     * @param bool $cstyle Cスタイルフラグ。 true を与えると / * * / 形式になる
     * @return string
     */
    public function commentize($comment, $cstyle = false)
    {
        // Cスタイルは全 DBMS でサポートしてるっぽい
        if ($cstyle) {
            $s = '/*';
            $e = '*/';
        }
        else {
            $s = $this->platform->getSqlCommentStartString();
            $e = $this->platform->getSqlCommentEndString();
        }
        $comment = str_replace($e, ' ', $comment);
        return "$s $comment $e";
    }

    /**
     * 挿入データと更新データで更新用カラム列を生成する
     *
     * mysql の VALUES 構文のために存在している
     *
     * @param array $insertData 挿入データ
     * @param array $updateData 更新データ
     * @return array VALUES で使用できる更新データ
     */
    public function convertMergeData($insertData, $updateData)
    {
        // 指定されているならそのまま返せば良い
        if ($updateData) {
            return $updateData;
        }

        // 指定されていない場合は $insertData を返す。ただし、データが長大な場合、2重に bind されることになり無駄なので mysql は VALUES を使う
        if ($this->platform instanceof MySqlPlatform) {
            return array_each($insertData, function (&$carry, $v, $k) {
                $carry[$k] = new Expression("VALUES($k)");
            }, []);
        }
        return $insertData;
    }

    /**
     * EXISTS 構文を SELECT で使用できるようにする
     *
     * @param string|Queryable $exists EXISTS 構文
     * @return Expression SELECT で使用できるようにした EXISTS 構文
     */
    public function convertSelectExistsQuery($exists)
    {
        $params = [];
        if ($exists instanceof Queryable) {
            $params = $exists->getParams();
        }

        // SQLServer は述語部でしか EXISTS が使えないので CASE で対応
        if ($this->platform instanceof SQLServerPlatform) {
            $exists = "CASE WHEN ($exists) THEN 1 ELSE 0 END";
        }

        return new Expression($exists, $params);
    }

    /**
     * リトライ可能例外か判定する
     *
     * @param \Exception $ex 判定する例外
     * @return bool リトライ可能例外なら true
     */
    public function isRetryableException($ex)
    {
        // \Doctrine\DBAL\Exception\DriverException は元例外として \Doctrine\DBAL\Driver\DriverException を保持している
        if ($ex instanceof \Doctrine\DBAL\Exception\DriverException) {
            $ex = $ex->getPrevious();
        }

        if ($this->platform instanceof MySqlPlatform) {
            // \Doctrine\DBAL\Driver\DriverException は実質的に PDOException なのでこいつで判定
            if ($ex instanceof \Doctrine\DBAL\Driver\DriverException) {
                switch ($ex->getErrorCode()) {
                    case 1205:// ER_LOCK_WAIT_TIMEOUT
                    case 1213:// ER_LOCK_DEADLOCK
                    case 2006:// CR_SERVER_GONE_ERROR
                        return true;
                }
            }
        }
        // @fixme mysql 以外はさっぱりわからない
        else {
            // \Doctrine\DBAL\Driver\DriverException は実質的に PDOException なのでこいつで判定
            if ($ex instanceof \Doctrine\DBAL\Driver\DriverException) {
                return true;
            }
        }

        return false;
    }

    /**
     * SELECT 文を UPDATE 文に変換する
     *
     * @param QueryBuilder $builder 変換するクエリビルダ
     * @param string $sets SET 句
     * @return string クエリビルダ を UPDATE に変換した文字列
     */
    public function convertUpdateQuery(QueryBuilder $builder, $sets)
    {
        $froms = $builder->getFromPart();
        $from = reset($froms);

        // JOIN がなければ変換はできる
        if (count($froms) === 1 || $this->platform instanceof \ryunosuke\Test\Platforms\SqlitePlatform || $this->platform instanceof MySqlPlatform) {
            // SQLServerPlatform はエイリアス指定の update をサポートしていない
            if ($from['alias'] !== $from['table'] && $this->platform instanceof SQLServerPlatform) {
                throw new \DomainException($this->platform->getName() . ' is not supported');
            }
            // select 化してクエリを取得して戻す
            $builder->select('__dbml_from_maker');
            $builder->innerJoinOn('__dbml_join_maker', 'TRUE', null);

            $sql = preg_replace('#^SELECT __dbml_from_maker FROM#ui', 'UPDATE', (string) $builder);
            return preg_replace('#INNER JOIN __dbml_join_maker ON TRUE#ui', "SET $sets", $sql);
        }
        if ($this->platform instanceof SQLServerPlatform) {
            // select 化してクエリを取得して戻す
            $builder->select('__dbml_from_maker');

            return preg_replace('#^SELECT __dbml_from_maker#ui', "UPDATE {$from['alias']} SET $sets", (string) $builder);
        }

        // 上記以外は join update をサポートしていない
        // 正確に言えば PostgreSql は using 構文をサポートしているが、select クエリから単純に変換できるものではない
        throw new \DomainException($this->platform->getName() . ' is not supported');
    }

    /**
     * SELECT 文を DELETE 文に変換する
     *
     * @param QueryBuilder $builder 変換するクエリビルダ
     * @param array $targets 対象テーブル
     * @return string クエリビルダ を DELETE に変換した文字列
     */
    public function convertDeleteQuery(QueryBuilder $builder, $targets)
    {
        $froms = $builder->getFromPart();
        $from = reset($froms);

        // JOIN がなければ変換はできる。 MySql と SQLServer は共通でOK（\ryunosuke\dbml\Test\Platforms\SqlitePlatform はテスト用で実際には無理）
        if (count($froms) === 1 || $this->platform instanceof \ryunosuke\Test\Platforms\SqlitePlatform || $this->platform instanceof MySqlPlatform || $this->platform instanceof SQLServerPlatform) {
            $builder->select('__dbml_from_maker');

            if ($targets) {
                // SQLServerPlatform は複数指定 delete をサポートしていない
                if (count($targets) > 1 && $this->platform instanceof SQLServerPlatform) {
                    throw new \DomainException($this->platform->getName() . ' is not supported');
                }
                $alias = implode(', ', $targets);
            }
            else {
                $alias = '';
                if (count($froms) > 1) {
                    $alias = $from['alias'];
                }
                elseif ($from['alias'] !== $from['table'] && ($this->platform instanceof MySqlPlatform || $this->platform instanceof SQLServerPlatform)) {
                    $alias = $from['alias'];
                }
            }

            $alias = concat(' ', $alias);
            return preg_replace('#^SELECT __dbml_from_maker FROM#ui', "DELETE{$alias} FROM", (string) $builder);
        }

        // 上記以外は join delete をサポートしていない
        // 正確に言えば PostgreSql は using 構文をサポートしているが、select クエリから単純に変換できるものではない
        throw new \DomainException($this->platform->getName() . ' is not supported');
    }
}
