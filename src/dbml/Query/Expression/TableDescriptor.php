<?php

namespace ryunosuke\dbml\Query\Expression;

use ryunosuke\dbml\Database;
use ryunosuke\dbml\Gateway\TableGateway;
use ryunosuke\dbml\Query\QueryBuilder;
use function ryunosuke\dbml\array_each;
use function ryunosuke\dbml\array_put;
use function ryunosuke\dbml\array_rekey;
use function ryunosuke\dbml\arrayize;
use function ryunosuke\dbml\concat;
use function ryunosuke\dbml\first_key;
use function ryunosuke\dbml\paml_import;
use function ryunosuke\dbml\preg_splice;
use function ryunosuke\dbml\quoteexplode;
use function ryunosuke\dbml\split_noempty;
use function ryunosuke\dbml\str_between;
use function ryunosuke\dbml\throws;

/**
 * テーブル記法の実装クラス
 *
 * テーブル記法の概念については {@link \ryunosuke\dbml\ dbml} を参照。
 * なお、内部的に使用されるだけで能動的に new したり活用されたりするようなクラスではない。
 *
 * 下記に記法としての定義を記載する。組み合わせた場合の使用例は {@link QueryBuilder::column()} を参照。
 *
 * `'(joinsign)tablename(pkval)@scope:fkeyname[condition]<groupby>+order-by#offset-limit AS Alias.col1, col2 AS C2'`
 *
 * | 要素               | 必須 | 説明
 * |:--                 |:--:  |:--
 * | joinsign           | 任意 | JOIN する場合に結合方法を表す記号を置く（'*':CROSS, '+':INNER, '<':LEFT, '>':RIGHT, '~':AUTO, ',':FROM）
 * | tablename          | 必須 | 取得するテーブル名を指定する
 * | (pkval)            | 任意 | 主キーの値を指定する
 * | @scope             | 任意 | 対応する Gateway がありかつ `scope` というスコープが定義されているならそのスコープを当てる（複数可）
 * | :fkeyname          | 任意 | JOIN に使用する外部キー名を指定する
 * | [condition]        | 任意 | 絞り込み条件を yaml で指定する（where 記法）
 * | {condition}        | 任意 | 絞り込み条件を yaml で指定する（カラム結合）
 * | &lt;groupby&gt;    | 任意 | GROUP BY を指定する
 * | +order-by          | 任意 | ORDER BY を指定する
 * | #offset-limit      | 任意 | LIMIT, OFFSET を指定する
 * | AS Alias           | 任意 | テーブルエイリアスを指定する
 * | .col1, col2 AS C2  | 任意 | 取得するカラムを指定する
 *
 * #### joinsign
 *
 * テーブルのプレフィックスとして `*+<>~,` を付けて JOIN を表す。
 * 他に特記事項はない。
 *
 * #### tablename
 *
 * テーブル名を表す。
 * 他に特記事項はない。
 *
 * #### (pkval)
 *
 * "()" 内で主キーの値を指定する。WHERE IN 化される。
 * 主キーはカンマ区切りで複数指定できる。また、 "()" をネストすることで行値式相当の動作になる。
 *
 * - e.g. `tablename(1)` （`WHERE pid IN (1)` となる）
 * - e.g. `tablename(1, 2)` （`WHERE pid IN (1, 2)` となる）
 * - e.g. `tablename((1, 2), (3, 4))` （`WHERE (mainid = 1 AND subid = 2) OR (mainid = 3 AND subid = 4)` となる）
 *
 * ※ 行値式は対応していない RDBMS やインデックスが使われない RDBMS が存在するため一律 AND OR で構築される
 *
 * #### @scope
 *
 * テーブルのサフィックスとして `@` を付けてスコープを表す。
 * 関連するゲートウェイクラスが存在しかつ指定されたスコープが定義されていなければならない。
 *
 * `@`を連続することで複数のスコープを当てることができる。
 *
 * - e.g. `tablename@scope1@scope2` （scope1 と scope2 を当てる）
 *
 * `@` だけを付けるとデフォルトスコープを表す（あくまでゲートウェイとは別概念なのでデフォルトスコープと言えど明示的に与えなければならない）。
 *
 * - e.g. `tablename@` （デフォルトスコープを当てる）
 * - e.g. `tablename@@scope` （デフォルトスコープと scope スコープを当てる）
 *
 * `@scope(1, 2)` とすることでパラメータ付きスコープの引数になる。
 *
 * - e.g. `tablename@latest(5)` （最新5件のようなスコープ）
 *
 * #### :fkeyname
 *
 * テーブルのサフィックスとして `:` を付けて外部キーを表す。
 * テーブル間外部キーが1つなら指定しなくても自動で使用される。
 * ただし、空文字を指定すると「外部キーを使用しない」を表す。
 *
 * - e.g. `tablename:fkname` （結合条件として外部キーカラムが使用される）
 * - e.g. `tablename` （同じ。テーブル間外部キーが1つならそれが指定されたとみなされる）
 * - e.g. `tablename:` （外部キー結合なし）
 *
 * #### [condition]
 *
 * テーブルのサフィックスとして yaml 記法で絞り込み条件を表す。
 * 駆動表に設定されている場合はただの WHERE 条件として働く。
 * 結合表に設定されている場合は ON 条件として働く。
 *
 * - e.g. `tablename[id: 1, delete_flg = 0]` （`id = 1 AND delete_flg = 0` となる（where 記法と同じ））
 *
 * #### {condition}
 *
 * テーブルのサフィックスとして yaml 記法で絞り込み条件を表す。
 *
 * - e.g. `tablename{selfid: otherid}` （`selfid = otherid` となる（カラムで結合する））
 *
 * #### <groupby>
 *
 * テーブルのサフィックスとして <group-key> で GROUP BY を表す。
 * "+" プレフィックスで昇順、 "-" プレフィックスで降順を表す。各指定の明確な区切りはない（≒[+-] のどちらかは必須）。
 *
 * - e.g. `tablename<id>` （`GROUP BY id` となる）
 * - e.g. `tablename<year, month>` （`GROUP BY year, month` となる）
 *
 * #### +order-by
 *
 * テーブルのサフィックスとして [+-]columnname で ORDER BY を表す。
 * "+" プレフィックスで昇順、 "-" プレフィックスで降順を表す。各指定の明確な区切りはない（≒[+-] のどちらかは必須）。
 *
 * - e.g. `tablename+id` （`ORDER BY id ASC` となる）
 * - e.g. `tablename-create_date+id` （`ORDER BY create_date DESC, id ASC` となる）
 *
 * #### #offset-limit
 *
 * テーブルのサフィックスとして #M-N で取得件数を表す。 M は省略可能。
 * 単純な LIMIT OFFSET ではない。言うなれば「範囲指定」のようなもので、例えば "#40-50" は LIMIT 10 OFFSET 40 を表す。
 * つまり、「40件目から50-1件目」を表す（M はそのまま OFFSET に、 N - M が LIMIT になる）。
 * さらに、-N を省略した場合は「LIMIT 1 OFFSET M」を意味する。つまり単純な1行を指すことになる。
 * さらにさらに、M を省略した場合は 0 が補填される。クエリ的には OFFSET が設定されないことになる。
 * さらにさらにさらにこの指定は**駆動表にのみ設定される**（JOIN の LIMIT はサブクエリになり効率的ではないし、そもそも利用頻度が少ない）。
 *
 * - e.g. `tablename#150-200` （`LIMIT 50 OFFSET 150` となり範囲を表す）
 * - e.g. `tablename#100` （`LIMIT 1 OFFSET 100` となり単一の1行を表す）
 * - e.g. `tablename#-100` （`LIMIT 100` となる（M を省略した場合、 OFFSET は設定されない））
 *
 * #### AS Alias
 *
 * テーブルにエイリアスをつける。
 * `AS` は省略して `tablename T` でも良い。
 *
 * #### .col1, col2 AS C2
 *
 * 取得するカラムリストを表す。カラムは直近のテーブル（エイリアス）で修飾される。
 * カンマ区切りで複数指定可能。
 * 各カラムに対して `AS aliasname` とすることでエイリアスを表す（AS は省略可能）。
 *
 * - e.g. `tablename.colA` （colA を取得）
 * - e.g. `tablename.colA, colB CB` （colA, colB（エイリアス CB） を取得）
 *
 * -----
 *
 * +order-by と #offset-limit は下記のように非常に相性が良い。
 *
 * - `tablename-create_date#0` （作成日降順で1件取得）
 *
 * (pkval), @scope, :fkeyname, [condition], +order-by, #offset-limit に順番の規則はないので任意に入れ替えることができる。
 * つまり、下記はすべて同じ意味となる（全組み合わせはとんでもない数になるので一部（:fkeyname, [condition] など）のみ列挙）。
 *
 * - `tablename@scope:fkeyname[condition]`
 * - `tablename@scope[condition]:fkeyname`
 * - `tablename:fkeyname@scope[condition]`
 * - `tablename:fkeyname[condition]@scope`
 * - `tablename[condition]@scope:fkeyname`
 * - `tablename[condition]:fkeyname@scope`
 *
 * ただし、 @scope(スコープ引数) と (pkval) の記法が重複しているため注意。
 * 例えば `@scope(1, 2)` これは「引数つきスコープ」なのか「引数なしスコープの後に (pkval)が来ている」のか区別ができない。
 * 見た目的な意味（あたかも関数コールのように見えて美しい）でも (pkval) はテーブル名の直後に置くのが望ましい。
 *
 * また、 yaml の中にまでは言及しないため、 "#" や "@" 等がリテラル内にある場合は誤作動を引き起こす。
 * 構文解析までするのは仰々しいため、仕方のない仕様として許容する。
 *
 * なお、**テーブル記法に決してユーザ入力を埋め込んではならない**。
 * (pkval) などは埋め込みたくなるが、テーブル記法は値のエスケープなどを一切行わないので致命的な脆弱性となりうる。
 *
 * @property string|QueryBuilder|TableGateway|mixed $descriptor
 * @property string $joinsign
 * @property string $table
 * @property string $alias
 * @property string $jointype
 * @property TableDescriptor[] $jointable
 * @property array $scope
 * @property array $condition
 * @property string $fkeyname
 * @property array $group
 * @property array $order
 * @property int $offset
 * @property int $limit
 * @property array $column
 * @property string $key
 * @property string $accessor
 * @property string $fkeysuffix
 * @property string $remaining
 */
class TableDescriptor
{
    /** @var string[] テーブル記法を表すメタ文字 */
    public const META_CHARACTORS = ['(', ')', '@', '[', ']', '{', '}', '+', '-', '#'];

    /** @var string オリジナル文字列 */
    private $descriptor;

    /** @var string JOIN記号 */
    private $joinsign;

    /** @var string テーブル名 */
    private $table;

    /** @var string エイリアス名 */
    private $alias;

    /** @var TableDescriptor[] JOIN 表 */
    private $jointable = [];

    /** @var array スコープ */
    private $scope = [];

    /** @var array 結合条件 */
    private $condition = [];

    /** @var string 外部キー名 */
    private $fkeyname;

    /** @var array GROUP */
    private $group = [];

    /** @var array 並び順 */
    private $order = [];

    /** @var int 取得件数 */
    private $offset, $limit;

    /** @var array 取得カラム名 */
    private $column = [];

    /** @var string 結合キー */
    private $key;

    /** @var string パースの過程で残ってしまったゴミ（これがあるということは何らかの理由でパースに失敗している可能性が高い） */
    private $remaining;

    private static function _split($descriptor, $defcol)
    {
        // @todo 影響が小さい内にリファクタする（何をしてるかさっぱりわからない）

        $joinsigns = implode('', Database::JOIN_MAPPER);
        $ejoinsigns = preg_quote($joinsigns, '#u');

        $split = function ($delimiters, $string, $skip) use ($ejoinsigns) {
            $brace_count = 0;
            $current = 0;
            $result = [];
            $tagging = false;
            for ($i = 0, $l = strlen($string); $i < $l; $i++) {
                if (strpos('([{', $string[$i]) !== false) {
                    $brace_count++;
                    continue;
                }
                if (strpos(')]}', $string[$i]) !== false) {
                    $brace_count--;
                    continue;
                }
                if ($string[$i] === '<' && ($p = strpos($string, '>', $i + 1)) !== false) {
                    $next = $string[$p + 1] ?? '';
                    if (!(ctype_alpha($next) || $next === '_' || $next === ' ')) {
                        $tagging = true;
                        $brace_count++;
                        continue;
                    }
                }
                if ($string[$i] === '>' && $tagging) {
                    $tagging = false;
                    $brace_count--;
                    continue;
                }
                if ($i !== 0 && $brace_count === 0 && strpos($delimiters, $string[$i]) !== false) {
                    $prev = $string[$i - 1] ?? '';
                    if ($prev === '.') {
                        continue;
                    }
                    if ($prev === '*' && $string[$i] === '*') {
                        continue;
                    }
                    $result[] = preg_replace("#^([$ejoinsigns])\s#u", '$1', trim(substr($string, $current, $i - $current), ', '));
                    $current = $i + (int) $skip;
                }
            }
            $result[] = preg_replace("#^([$ejoinsigns])\s#u", '$1', trim(substr($string, $current, $i - $current), ', '));
            return $result;
        };

        $result = [];
        foreach (explode('/', $descriptor) as $column) {
            $aliases = [];
            $tables = [];
            $lasttable = null;
            foreach ($split(",$joinsigns", $column, false) as $col) {
                $parts = array_filter(array_map('trim', $split('.', $col, true)), 'strlen');
                // 1つ（カラムだけ指定）の場合は最後のテーブルを使用する
                if (count($parts) === 1) {
                    // ただし、最後のテーブルがない場合はテーブル名として扱う
                    if ($lasttable === null) {
                        $tables[$parts[0]] = $defcol;
                        $table = preg_split('#\s+(as\s+)?#ui', $parts[0]) + [1 => null];
                        $aliases[$table[1] ?: $table[0]] = $parts[0];
                    }
                    else {
                        $tables[$lasttable][] = trim($parts[0], ', ');
                    }
                }
                // 2つ（テーブル指定）はそのまま
                elseif (count($parts) === 2) {
                    // ただし、過去のテーブルに存在するなら追加せずそれを使う
                    $lasttable = trim($parts[0], ', ');
                    if (isset($aliases[$lasttable])) {
                        $lasttable = $aliases[$lasttable];
                    }
                    else {
                        $table = preg_split('#\s+(as\s+)?#ui', $lasttable) + [1 => null];
                        $aliases[$table[1] ?: $table[0]] = $lasttable;
                    }
                    $tables[$lasttable][] = trim($parts[1], ', ');
                }
                // 3つ（別スキーマ指定）は例外
                elseif (count($parts) >= 3) {
                    throw new \InvalidArgumentException('not supports specify other schema.');
                }
            }
            $result[] = $tables;
        }

        for ($i = count($result) - 1; $i > 0; $i--) {
            $prev = $result[$i - 1];
            $key = key($prev);
            $result[$i - 1][$key] += $result[$i];
        }
        return $result[0];
    }

    /**
     * 文字列や配列からインスタンスの配列を生成する
     *
     * @param Database $database データベースオブジェクト
     * @param string|array $descriptor テーブル記法
     * @param array $columnIfString テーブルのみ指定時のデフォルトカラム
     * @return $this[] 自身の配列
     */
    public static function forge(Database $database, $descriptor, $columnIfString = ['*'])
    {
        // 文字列はバラす（table1, table2 => [table1 => [], table2 => []]）
        if (is_string($descriptor)) {
            $descriptor = self::_split($descriptor, $columnIfString);
        }

        $tables = [];
        foreach (arrayize($descriptor) as $key => $val) {
            if ($val instanceof TableDescriptor) {
                $tables[] = $val;
                continue;
            }
            // 値だけならテーブル名として扱う
            if (is_int($key)) {
                // ただし、null はスルー
                if (is_null($val)) {
                    continue;
                }
                // $val が文字列なら全カラム
                elseif (is_string($val)) {
                    $tables[] = new self($database, $val, ['*']);
                }
                // それ以外はテーブル指定なしのただのカラム
                else {
                    $tables[] = new self($database, '', $val);
                }
            }
            // キー付きなら テーブル名 => カラム名 として扱う
            else {
                $tables[] = new self($database, $key, $val);
            }
        }
        return $tables;
    }

    /**
     * コンストラクタ
     *
     * @param Database $database データベースオブジェクト
     * @param string $descriptor テーブル記法
     * @param string|array $cols テーブルのみ指定時のデフォルトカラム
     */
    public function __construct(Database $database, $descriptor, $cols)
    {
        /// e.g. +tablename@scope(1, 2):fkeyname[condition]#1-3 AS T.col1, col2 AS C2

        $schema = $database->getSchema();
        $alnumscore = "[_0-9a-z]";
        $identifier = "[_a-z]$alnumscore";

        // テーブルに紐付かないカラムのための下ごしらえ
        if (true
            && preg_match("#^$alnumscore+$#i", $descriptor)
            && !$schema->hasTable($database->convertTableName($descriptor))
        ) {
            $cols = [$descriptor => $cols];
            $descriptor = '';
        }

        $this->descriptor = $descriptor;

        $joinsigns = preg_quote(implode('', Database::JOIN_MAPPER), '`');
        $descriptor = preg_splice("`^([$joinsigns]?)\s*($alnumscore+)`ui", '', trim($descriptor), $m);
        $joinsign = $m[1] ?? null;
        $table = $m[2] ?? null;

        $descriptor = preg_splice("`(\[.*])`ui", '', trim($descriptor), $m);
        $condition1 = $m[1] ?? null;
        $descriptor = preg_splice("`({.+})`ui", '', trim($descriptor), $m);
        $condition2 = $m[1] ?? null;

        $descriptor = preg_splice("`(<.+>)`ui", '', trim($descriptor), $m);
        $group = $m[1] ?? null;

        $descriptor = preg_splice("`(:[_0-9a-z]*)`ui", '', trim($descriptor), $m);
        $fkeyname = $m[1] ?? null;

        $descriptor = preg_splice("`(@$alnumscore*(\((?:[^()]+|(?1))*\))?)+`ui", '', trim($descriptor), $m);
        $scope = $m[0] ?? null;

        $descriptor = preg_splice("`(\(.*\))`ui", '', trim($descriptor), $m);
        $primary = $m[1] ?? null;

        $descriptor = preg_splice("`(\s*[+-]$identifier*)+`ui", '', trim($descriptor), $m);
        if ($m) {
            foreach (preg_split('#(?=[+-])#u', $m[0], -1, PREG_SPLIT_NO_EMPTY) as $order) {
                $sign = $order[0];
                $order = substr($order, 1);
                $this->order[trim($order)] = ['+' => 'ASC', '-' => 'DESC'][$sign];
            }
        }

        $descriptor = preg_splice('`#((\d+)?-?(\d+)?)`ui', '', trim($descriptor), $m);
        if (isset($m[1])) {
            [$offset, $limit] = explode('-', $m[1]) + [1 => ''];
            if (strlen($offset) && strlen($limit)) {
                $this->offset = (int) $offset;
                $this->limit = $limit - $offset;
            }
            elseif (strlen($offset)) {
                $this->offset = (int) $offset;
                $this->limit = 1;
            }
            elseif (strlen($limit)) {
                $this->offset = null;
                $this->limit = (int) $limit;
            }
        }

        $descriptor = preg_splice("`^(as\s+)?($alnumscore+)?`ui", '', trim($descriptor), $m);
        $alias = $m[2] ?? null;

        $descriptor = preg_splice('`(\.(.+))?`ui', '', trim($descriptor), $m);
        $column = $m[2] ?? null;

        $this->remaining = trim($descriptor);
        $this->joinsign = $joinsign;
        $this->alias = $alias;

        $this->table = $database->convertTableName($table);
        if ($this->alias === null && $this->table !== $table) {
            $this->alias = $table;
        }
        if ($cols instanceof QueryBuilder) {
            if ($cols->getSubmethod() === null) {
                $this->alias = $table;
                $this->table = $cols;
                $cols = [];
            }
        }
        // この段階で Gateway をロードしておく
        if (is_string($this->table)) {
            $gateway = $database->{$this->table};
            assert(is_null($gateway) || is_object($gateway));
        }

        $this->key = $this->joinsign . $this->table . $primary . $scope . $fkeyname . $condition1 . $condition2 . $group . concat(' ', $this->alias);

        if ($scope !== null) {
            $this->scope = array_each(array_slice(explode('@', $scope), 1), function (&$carry, $item) {
                $sargs = [];
                $args = str_between($item, '(', ')');
                if ($args !== false) {
                    $item = str_replace("($args)", '', $item);
                    $sargs = paml_import($args);
                }
                $carry[$item] = $sargs;
            }, null);
        }

        if ($fkeyname !== null) {
            $this->fkeyname = trim(ltrim($fkeyname, ':'));
        }

        if ($primary) {
            $primary = preg_replace('#^\(|\)$#u', '', $primary);
            $pcols = $schema->getTablePrimaryKey($this->table)->getColumns();
            $pvals = array_each(quoteexplode(',', $primary, null, ['(' => ')']), function (&$carry, $pval) use ($pcols) {
                $pvals = explode(',', str_between($pval, '(', ')') ?: $pval);
                if (count($pcols) !== count($pvals)) {
                    throw new \InvalidArgumentException('argument\'s length is not match primary columns.');
                }
                $carry[] = array_combine($pcols, array_map('trim', $pvals));
            });
            $this->condition[] = $database->getCompatiblePlatform()->getPrimaryCondition($pvals, $this->accessor);
        }
        if ($condition1 !== null) {
            $this->condition = array_merge($this->condition, $database->parseYaml(trim($condition1)));
        }
        if ($condition2 !== null) {
            $this->condition = array_merge($this->condition, [
                (object) array_rekey((array) $database->parseYaml(trim($condition2)), function ($k, $v) {
                    return is_int($k) ? $v : $k;
                }),
            ]);
        }

        if ($group !== null) {
            $this->group = split_noempty(',', preg_replace('#^<|>$#u', '', $group));
        }

        $this->column = split_noempty(',', "$column");
        foreach (arrayize($cols) as $k => $c) {
            // 素の配列が来たら JOIN 条件
            if (is_int($k) && is_array($c)) {
                $this->condition = array_merge($this->condition, $c);
            }
            // ['columname' => '+othertable.columname'] モード
            elseif (is_string($c) && preg_match("#^[$joinsigns]$identifier*#ui", trim($c), $m)) {
                $join = new self($database, $c, []);
                foreach ($join->column as $c2) {
                    $this->column[] = new Alias(...Alias::split($join->accessor . '.' . $c2, is_int($k) ? null : $k));
                }
                $join->descriptor = [];
                $this->jointable[] = $join;
            }
            // ['+othertable' => ['columname']] モード
            elseif (preg_match("#^[$joinsigns].#u", trim($k), $m)) {
                $join = new self($database, $k, []);
                foreach ($join->column as $c2) {
                    $this->column[] = new Alias(...Alias::split($join->accessor . '.' . $c2, null));
                }
                // ['+Alias' => $db->t_table] のために特殊なことをしなければならない（テーブル名部分がなくエイリアス部分だけなので読み替える）
                if ($c instanceof TableGateway && $c->tableName() !== $join->table) {
                    $c = $c->clone();
                    $c->as($join->table);// alias とかもいじる必要がある。が、当面使ってないのでこれで OK
                    $join->key = $join->joinsign . $c->descriptor();
                }
                $join->descriptor = $c;
                $this->jointable[] = $join;
            }
            // 上記以外は単純に追加すれば良い
            else {
                array_put($this->column, $c, $k);
            }
        }

        // **+ カラムの処理1パス目（'**' なカラムを集める）
        $subcols = [];
        foreach ($this->column as $k => $col) {
            if (!is_string($col) || !preg_match('#^\*(\*+)$#u', $col, $m)) {
                continue;
            }

            // 自身は * で良いので上書き
            $this->column[$k] = '*';

            // 自身を親とする外部キーが対象
            foreach ($schema->getForeignKeys($this->table) as $fkey) {
                $ltable = first_key($schema->getForeignTable($fkey));

                // 取得カラム内に含まれているならそちらを優先するためスキップ
                if (array_key_exists($ltable, $this->column)) {
                    continue;
                }
                // 1対1なら子テーブルとして取得する価値が無いのでスキップ
                if (!array_diff($schema->getTablePrimaryKey($ltable)->getColumns(), $fkey->getLocalColumns())) {
                    continue;
                }

                // 配列を入れることで subselect に移譲する
                $subcol = $database->convertEntityName($ltable);
                $subcols[$subcol][$fkey->getName()] = [$m[1]];
            }
        }
        // **+ カラムの処理2パス目（集めたカラムを subselect として代入。複数外部キーを考慮するとどうしても2パス必要）
        foreach ($subcols as $subcol => $scols) {
            $suffix = count($scols) > 1;
            foreach ($scols as $fname => $fcol) {
                $this->column[$subcol . ($suffix ? ':' . $fname : '')] = $fcol;
            }
        }
    }

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        if (strcasecmp($name, 'accessor') === 0) {
            return $this->alias ?: $this->table;
        }
        if (strcasecmp($name, 'jointype') === 0) {
            if ("$this->joinsign" === "") {
                return null;
            }
            return array_search($this->joinsign, Database::JOIN_MAPPER, true) ?: throws(new \UnexpectedValueException('undefined joinsign.'));
        }
        if (strcasecmp($name, 'fkeysuffix') === 0) {
            return concat(':', $this->fkeyname);
        }

        throw new \InvalidArgumentException("'$name' is undefined.");
    }
}
