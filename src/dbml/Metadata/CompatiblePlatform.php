<?php

namespace ryunosuke\dbml\Metadata;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Types;
use ryunosuke\dbml\Query\Expression\Expression;
use ryunosuke\dbml\Query\Expression\SelectOption;
use ryunosuke\dbml\Query\Queryable;
use ryunosuke\dbml\Query\QueryBuilder;
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
    /** @var AbstractPlatform 元 platform */
    private $platform;

    /** @var ?string */
    private $version;

    /**
     * コンストラクタ
     *
     * @param AbstractPlatform $platform 元 platform
     * @param ?string $version バージョン文字列
     */
    public function __construct(AbstractPlatform $platform, $version = null)
    {
        $this->platform = $platform;
        $this->version = $version;
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
     * バージョン文字列を取得する
     *
     * @return ?string バージョン文字列
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * platform 名を取得する
     *
     * @return string
     */
    public function getName()
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
     * AUTO_INCREMENT な列に null を与えると自動採番が働くかどうか
     *
     * @return bool AUTO_INCREMENT な列に null を与えると自動採番が働くなら true
     */
    public function supportsIdentityNullable()
    {
        if ($this->platform instanceof \ryunosuke\Test\Platforms\SqlitePlatform) {
            return false;
        }
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
     * AUTO_INCREMENT な列を明示指定したあと、自動でシーケンスが更新されるか否かを返す
     *
     * @return bool AUTO_INCREMENT な列を明示指定したあと、自動でシーケンスが更新されるなら true
     */
    public function supportsIdentityAutoUpdate()
    {
        if ($this->platform instanceof PostgreSQLPlatform || $this->platform instanceof OraclePlatform) {
            return false;
        }
        return true;
    }

    /**
     * INSERT SET 拡張構文が使えるか否かを返す
     *
     * @return bool INSERT SET 拡張構文が使えるなら true
     */
    public function supportsInsertSet()
    {
        if ($this->platform instanceof MySQLPlatform || $this->platform instanceof \ryunosuke\Test\Platforms\SqlitePlatform) {
            return true;
        }

        return false;
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
        if ($this->platform instanceof MySQLPlatform) {
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
     *
     * @return bool BULK MERGE が使えるなら true
     */
    public function supportsBulkMerge()
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
     *
     * @return bool IGNORE が使えるなら true
     */
    public function supportsIgnore()
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
     * UPDATE + JOIN をサポートするか否かを返す
     *
     * @return bool UPDATE + JOIN をサポートするなら true
     */
    public function supportsUpdateJoin()
    {
        if ($this->platform instanceof MySQLPlatform || $this->platform instanceof SQLServerPlatform) {
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
        if ($this->platform instanceof MySQLPlatform || $this->platform instanceof SQLServerPlatform) {
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
        if ($this->platform instanceof MySQLPlatform || $this->platform instanceof SQLServerPlatform) {
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
        if ($this->platform instanceof MySQLPlatform || $this->platform instanceof SQLServerPlatform) {
            return true;
        }
        return false;
    }

    /**
     * TRUNCATE CASCADE をサポートするか否かを返す
     *
     * @return bool TRUNCATE CASCADE をサポートするなら true
     */
    public function supportsTruncateCascade()
    {
        if ($this->platform instanceof PostgreSQLPlatform) {
            return true;
        }
        return false;
    }

    /**
     * UNION が括弧をサポートするか否かを返す
     *
     * @return bool UNION が括弧をサポートするなら true
     */
    public function supportsUnionParentheses()
    {
        if ($this->platform instanceof SqlitePlatform) {
            return false;
        }
        return true;
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
     * 行値式が有効か否かを返す
     *
     * @return bool 行値式が有効なら true
     */
    public function supportsRowConstructor()
    {
        if ($this->platform instanceof SQLServerPlatform) {
            return false;
        }
        if ($this->platform instanceof \ryunosuke\Test\Platforms\SqlitePlatform) {
            return false;
        }
        if ($this->platform instanceof SqlitePlatform) {
            return version_compare($this->version, '3.15.0') >= 0;
        }
        return true;
    }

    /**
     * char と binary に互換性があるかを返す
     *
     * @return bool char と binary に互換性があるなら true
     */
    public function supportsCompatibleCharAndBinary()
    {
        if ($this->platform instanceof SQLServerPlatform) {
            return false;
        }
        if ($this->platform instanceof \ryunosuke\Test\Platforms\SqlitePlatform) {
            return false;
        }
        return true;
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

        // PostgreSql は識別子が小文字に正規化されるのでエイリアスがブレる
        if ($this->platform instanceof PostgreSQLPlatform && strtolower($word) !== $word) {
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

        return $this->platform->escapeStringForLike($word, $escaper);
    }

    /**
     * 文字列を指定長で切る
     *
     * @param ?string $string 切る文字列
     * @param Column $column カラム
     * @return string 切られた文字列
     */
    public function truncateString($string, $column)
    {
        // @todo mysql 以外は詳しくないため未実装
        if (!$this->platform instanceof MySQLPlatform) {
            return $string;
        }

        if (in_array($column->getType()->getName(), [Types::STRING], true)) {
            if (!$column->getLength() || !$column->hasPlatformOption('charset')) {
                return $string;
            }
            $charset = $column->getPlatformOption('charset');
            $charset = starts_with($charset, 'utf8') ? 'utf-8' : $charset;
            return mb_substr($string, 0, $column->getLength(), $charset);
        }
        if (in_array($column->getType()->getName(), [Types::BINARY, Types::TEXT, Types::BLOB], true)) {
            if (!$column->getLength()) {
                return $string;
            }
            return substr($string, 0, $column->getLength());
        }

        throw new \InvalidArgumentException($column->getType()->getName() . ' is not supported');
    }

    /**
     * （対応しているなら） dual 表を返す
     *
     * @return string dual 表
     */
    public function getDualTable()
    {
        if ($this->platform instanceof MySQLPlatform) {
            return 'dual';
        }
        return '';
    }

    /**
     * CALC_FOUND_ROWS が使える場合にその SelectOption を返す
     *
     * @return SelectOption CALC_FOUND_ROWS が使えるなら SelectOption::SQL_CALC_FOUND_ROWS
     */
    public function getFoundRowsOption()
    {
        if ($this->platform instanceof MySQLPlatform) {
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
        if ($this->platform instanceof MySQLPlatform) {
            return 'SELECT FOUND_ROWS()';
        }
        return '';
    }

    /**
     * MERGE 構文を返す
     *
     * @param array $columns 一意制約カラム
     * @return string|bool MERGE 構文に対応してるなら文字列、対応していないなら false
     */
    public function getMergeSyntax($columns)
    {
        if ($this->platform instanceof SqlitePlatform) {
            $constraint = implode(',', $columns);
            return "ON CONFLICT($constraint) DO UPDATE SET";
        }
        if ($this->platform instanceof MySQLPlatform) {
            return "ON DUPLICATE KEY UPDATE";
        }
        if ($this->platform instanceof PostgreSQLPlatform) {
            $constraint = implode(',', $columns);
            return "ON CONFLICT($constraint) DO UPDATE SET";
        }
        return false;
    }

    /**
     * 参照構文（mysql における VALUES）を返す
     *
     * @param string $column 参照カラム名
     * @return string|bool 参照構文、対応していないなら false
     */
    public function getReferenceSyntax($column)
    {
        if ($this->platform instanceof SqlitePlatform) {
            return "excluded.$column";
        }
        if ($this->platform instanceof MySQLPlatform) {
            return "VALUES($column)";
        }
        if ($this->platform instanceof PostgreSQLPlatform) {
            return "EXCLUDED.$column";
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
        if ($this->platform instanceof PostgreSQLPlatform) {
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
     *
     * @param string|array $index_name インデックス名
     * @param string $mode HINT/FORCE などのモード名
     * @return string インデックスヒント構文
     */
    public function getIndexHintSQL($index_name, $mode = 'FORCE')
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
     * @internal
     * @codeCoverageIgnore
     */
    public function getListTableColumnsSQL($table, $database = null): string
    {
        // doctrine 4.4 から VIEW のカラムが得られなくなったので暫定対応（SQLServer 以外は辛うじて getListTableColumnsSQL が使える）
        if ($this->platform instanceof SQLServerPlatform) {
            return <<<SQL
                SELECT 
                    c.name                  AS name,
                    type_name(user_type_id) AS type,
                    c.max_length            AS length,
                    ~c.is_nullable          AS notnull,
                    NULL                    AS "default",
                    c.scale                 AS scale,
                    c.precision             AS precision,
                    0                       AS autoincrement,
                    c.collation_name        AS collation,
                    NULL                    AS comment
                FROM sys.columns c
                JOIN sys.views v ON v.object_id = c.object_id
                WHERE SCHEMA_NAME(v.schema_id) = SCHEMA_NAME() AND v.name = {$this->platform->quoteStringLiteral($table)}
            SQL;
        }
        return $this->platform->getListTableColumnsSQL($table, $database);
    }

    /**
     * クエリにロック構文を付加して返す
     *
     * @param string $query クエリ
     * @param ?int $lockmode ロック構文
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
     * @param array $wheres WHERE 配列
     * @param string $prefix 修飾子
     * @return Expression WHERE 条件をバインドパラメータに持つ Expression インスタンス
     */
    public function getPrimaryCondition($wheres, $prefix = '')
    {
        if (!$wheres) {
            return new Expression('');
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
     * @param ?string $separator セパレータ文字
     * @param string|array|null order 句。これが活きるのは mysql のみ
     * @return string GROUP_CONCAT 構文
     */
    public function getGroupConcatSyntax($expr, $separator = null, $order = null)
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

        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * null 許容演算子（<=>）構文を返す
     *
     * $column はカラム名を想定しており、エスケープされないので注意すること。
     *
     * @todo 呼び元で ? の数を算出してるので Syntax ではなく Expression 返しの方が良い
     *
     * @param string $column 左辺値
     * @return string null 許容演算子
     */
    public function getSpaceshipSyntax($column)
    {
        if ($this->platform instanceof SqlitePlatform) {
            return "$column IS ?";
        }
        if ($this->platform instanceof MySQLPlatform) {
            return "$column <=> ?";
        }
        if ($this->platform instanceof PostgreSQLPlatform) {
            return "$column IS NOT DISTINCT FROM ?";
        }
        if ($this->platform instanceof SQLServerPlatform && version_compare($this->version, '16') >= 0) {
            return "$column IS NOT DISTINCT FROM ?";
        }
        return "($column IS NULL AND ? IS NULL) OR $column = ?";
    }

    /**
     * 再帰 WITH 句を返す
     *
     * @return string 再帰 WITH
     */
    public function getWithRecursiveSyntax()
    {
        // 他 DBMS は無条件で RECURSIVE をつけても大丈夫だが Sqlserver はだめっぽい
        if ($this->platform instanceof SQLServerPlatform) {
            return "WITH";
        }
        return "WITH RECURSIVE";
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
        return new Expression("COUNT($column)");
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
        return new Expression("MIN({$column})");
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
        return new Expression("MAX({$column})");
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
        return new Expression("SUM({$column})");
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
        return new Expression("AVG({$column})");
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
     * 正規表現を返す
     *
     * @return Expression REGEXP Expression
     */
    public function getRegexpExpression($column, $pattern)
    {
        if ($this->platform instanceof SqlitePlatform) {
            return new Expression("$column REGEXP ?", [$pattern]);
        }
        if ($this->platform instanceof MySQLPlatform) {
            return new Expression("REGEXP_LIKE($column, ?, 'i')", [$pattern]);
        }
        if ($this->platform instanceof PostgreSQLPlatform) {
            return new Expression("$column ~* ?", [$pattern]);
        }

        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * binary 表現を返す
     *
     * @param string $data データ
     * @return Expression|string BINARY Expression
     */
    public function getBinaryExpression($data)
    {
        // SQLServer はキャストしなければ binary として扱えない
        if ($this->platform instanceof SQLServerPlatform) {
            return new Expression('CAST(? as VARBINARY(MAX))', [(string) $data]);
        }
        return $data;
    }

    /**
     * sleep 表現を返す
     *
     * @param float second 秒数
     * @return Expression SLEEP Expression
     */
    public function getSleepExpression($second)
    {
        if ($this->platform instanceof MySQLPlatform) {
            return new Expression("SLEEP(?)", $second);
        }
        if ($this->platform instanceof PostgreSQLPlatform) {
            return new Expression("pg_sleep(?)", $second);
        }

        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * random 表現（[0~1.0)）を返す
     *
     * @param ?int $seed 乱数シード
     * @return Expression RANDOM Expression
     */
    public function getRandomExpression($seed)
    {
        // Sqlite にシード設定方法は存在しない
        if ($this->platform instanceof SqlitePlatform) {
            return new Expression("(0.5 - RANDOM() / CAST(-9223372036854775808 AS REAL) / 2)");
        }
        // MySQL のみ単体クエリで setseed+random が実現できる
        if ($this->platform instanceof MySQLPlatform) {
            return $seed === null ? new Expression("RAND()") : new Expression("RAND(?)", $seed);
        }
        // PostgreSQL は setseed があるが、SELECT 句で呼んでも毎回 setseed され random が同じ値になってしまう
        if ($this->platform instanceof PostgreSQLPlatform) {
            return new Expression("random()");
        }
        // SQLServer の RAND はシードを与えなければ同じ値を返してしまう
        if ($this->platform instanceof SQLServerPlatform) {
            return new Expression("RAND(CHECKSUM(NEWID()))");
        }

        throw DBALException::notSupported(__METHOD__);
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
     *
     * @param bool $enabled 有効無効
     * @param ?string $table_name テーブル名
     * @param ?string $fkname 外部キー名
     * @return array 外部キーを切り替える一連のクエリ文字列配列
     */
    public function getSwitchForeignKeyExpression($enabled, $table_name = null, $fkname = null)
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
     *
     * @return string IGNORE 構文
     */
    public function getIgnoreSyntax()
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
            $s = "--";
            $e = "\n";
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

        // 指定されていない場合は $insertData を返す。ただし、データが長大な場合、2重に bind されることになり無駄なので参照構文を使う
        return array_each($insertData, function (&$carry, $v, $k) {
            $reference = $this->getReferenceSyntax($k);
            $carry[$k] = $reference === false ? $v : new Expression($reference);
        }, []);
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
     * SELECT 文を UPDATE 文に変換する
     *
     * @param QueryBuilder $builder 変換するクエリビルダ
     * @return string クエリビルダ を UPDATE に変換した文字列
     */
    public function convertUpdateQuery(QueryBuilder $builder)
    {
        $froms = $builder->getFromPart();
        $from = reset($froms);
        $sets = array_sprintf($builder->getQueryPart('colval'), '%2$s = %1$s', ', ');

        // JOIN がなければ変換はできる
        if (count($froms) === 1 || $this->platform instanceof \ryunosuke\Test\Platforms\SqlitePlatform || $this->platform instanceof MySQLPlatform) {
            // SQLServerPlatform はエイリアス指定の update をサポートしていない
            if ($from['alias'] !== $from['table'] && $this->platform instanceof SQLServerPlatform) {
                throw new \DomainException($this->getName() . ' is not supported');
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
        throw new \DomainException($this->getName() . ' is not supported');
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
        if (count($froms) === 1 || $this->platform instanceof \ryunosuke\Test\Platforms\SqlitePlatform || $this->platform instanceof MySQLPlatform || $this->platform instanceof SQLServerPlatform) {
            $builder->select('__dbml_from_maker');

            if ($targets) {
                // SQLServerPlatform は複数指定 delete をサポートしていない
                if (count($targets) > 1 && $this->platform instanceof SQLServerPlatform) {
                    throw new \DomainException($this->getName() . ' is not supported');
                }
                $alias = implode(', ', $targets);
            }
            else {
                $alias = '';
                if (count($froms) > 1) {
                    $alias = $from['alias'];
                }
                elseif ($from['alias'] !== $from['table'] && ($this->platform instanceof MySQLPlatform || $this->platform instanceof SQLServerPlatform)) {
                    $alias = $from['alias'];
                }
            }

            $alias = concat(' ', $alias);
            return preg_replace('#^SELECT __dbml_from_maker FROM#ui', "DELETE{$alias} FROM", (string) $builder);
        }

        // 上記以外は join delete をサポートしていない
        // 正確に言えば PostgreSql は using 構文をサポートしているが、select クエリから単純に変換できるものではない
        throw new \DomainException($this->getName() . ' is not supported');
    }
}
