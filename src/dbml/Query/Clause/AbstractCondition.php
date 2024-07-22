<?php

namespace ryunosuke\dbml\Query\Clause;

use ryunosuke\dbml\Database;
use ryunosuke\dbml\Query\Expression\Expression;
use ryunosuke\dbml\Query\Expression\Operator;
use ryunosuke\dbml\Query\Queryable;
use ryunosuke\dbml\Query\SelectBuilder;
use ryunosuke\dbml\Utility\Adhoc;
use function ryunosuke\dbml\array_set;
use function ryunosuke\dbml\arrayize;
use function ryunosuke\dbml\str_subreplace;

/**
 * WHERE/HAVING 句の抽象共通クラス
 */
abstract class AbstractCondition extends AbstractClause
{
    /**
     * 条件を正規化する
     *
     * 基本的に配列を与えることが多いが、値はエスケープされるがキーは一切触らずスルーされるため**キーには決してユーザ由来の値を渡してはならない**。
     * また、トップレベル以下の下層に配列が来ても連想配列とはみなされない（キーを使用しない or 連番で振り直す）。
     *
     * ```php
     * # bad（トップレベルのキーにユーザ入力が渡ってしまう）
     * Where::and($_GET);
     *
     * # better（少なくともトップレベルのキーにユーザ入力が渡ることはない）
     * Where::and([
     *     'colA' => $_GET['colA'],
     *     'colB' => $_GET['colB'],
     * ]);
     * ```
     *
     * | No | predicates                                     | result                             | 説明
     * | --:|:--                                             |:--                                 |:--
     * |  0 | `''`                                           | -                                  | 値が(phpで)空判定される場合、その条件はスルーされる。空とは `null || '' || [] || 全てが!で除外された SelectBuilder` のこと
     * |  1 | `'hoge = 1'`                                   | `hoge = 1`                         | `['hoge = 1']` と同じ。単一文字列指定は配列化される
     * |  2 | `['hoge = 1']`                                 | `hoge = 1`                         | キーを持たないただの配列はそのまま条件文になる
     * |  3 | `['hoge = ?' => 1]`                            | `hoge = 1`                         | キーに `?` を含む配列は普通に想起されるプリペアードステートメントになる
     * |  4 | `['hoge = ? OR fuga = ?' => [1, 2]]`           | `hoge = 1 OR fuga = 2`             | キーに `?` を複数含む配列はパラメータがそれぞれのプレースホルダにバインドされる
     * |  5 | `['hoge' => 1]`                                | `hoge = 1`                         | キーに `?` を含まない [キー => 値] はキーがカラム名、値が bind 値とみなして `=` で結合される
     * |  6 | `['hoge' => null]`                             | `hoge IS NULL`                     | 上記と同じだが、値が null の場合は `IS NULL` 指定とみなす
     * |  7 | `['hoge' => [1, 2, 3]]`                        | `hoge IN (1, 2, 3)`                | 上上記と同じだが、値が配列の場合は `IN` 指定とみなす
     * |  8 | `['hoge' => []]`                               | `hoge IN (NULL)`                   | 値が空配列の場合は IN(NULL) になる（DBMSにもよるが、実質的には `FALSE` と同義）
     * |  9 | `['hoge:LIKE' => 'xxx']`                       | `hoge LIKE ('xxx')`                | `:演算子`を付与するとその演算子で比較が行われる
     * | 10 | `['hoge:!LIKE' => 'xxx']`                      | `NOT (hoge LIKE ('xxx'))`          | `:` で演算子を明示しかつ `!` を付与すると全体として NOT で囲まれる
     * | 11 | `['hoge:!' => 'xxx']`                          | `NOT (hoge = 'xxx')`               | `:` 以降がない場合はNo.5～8 とみなすその否定なので NOT = になる
     * | 15 | `[':hoge']`                                    | `hoge = :hoge`                     | :hoge のようにコロンで始まる要素は 'hoge = :hoge' に展開される（prepare の利便性が上がる）
     * | 21 | `['(hoge, fuga)'] => [[1, 2], [3, 4]]`         | `(hoge, fuga) IN ((1, 2), (3, 4))` | 行値式のようなキーは `IN` に展開される
     * | 22 | `['!hoge' => '']`                              | -                                  | キーが "!" で始まるかつ bind 値が(phpで)空判定される場合、その条件文自体が抹消される（記号は同じだが前述の `:!演算子` とは全く別個）
     * | 23 | `['AND/OR' => ['hoge' => 1, 'fuga' => 2]]`     | `hoge = 1 OR fuga = 2`             | キーが "AND/OR" の場合は特別扱いされ、AND/OR コンテキストの切り替えが行わる
     * | 24 | `['NOT' => ['hoge' => 1, 'fuga' => 2]]`        | `NOT(hoge = 1 AND fuga = 2)`       | キーが "NOT" の場合も特別扱いされ、その要素を NOT で反転する
     * | 25 | `[SelectBuilder]`                               | 略                                 | SelectBuilder の文字列表現をそのまま埋め込む。EXISTS などでよく使用されるが、使い方を誤ると「Operand should contain 1 column(s)」とか「Subquery returns more than 1 row」とか言われるので注意
     * | 26 | `['hoge' => SelectBuilder]`                     | 略                                 | キー付きで SelectBuilder を渡すとサブクエリで条件演算される。左記の場合は `hoge IN (SelectBuilder)` となる
     * | 27 | `[Operator]`                                   | 略                                 | 条件式の代わりに `Operator` インスタンスを渡すことができるが、難解なので説明は省略
     * | 28 | `['hoge' => Operator::equal(1)]`               | 略                                 | No.5 と同じだが、 equal を別のメソッドに変えればシンプルな key => value 配列を保ちつつ演算子が使える
     * | 31 | `['hoge' => function () {}]`                   | 略                                 | クロージャを渡すとクロージャの実行結果が「あたかもそこにあるかのように」振る舞う
     *
     * No.9,10 の演算子は `LIKE` や `BETWEEN` 、 `IS NULL` 、範囲指定できる独自の `[~]` 演算子などがある。
     * 組み込みの演算子は {@link Operator} を参照。
     *
     * ```php
     * # No.22（検索画面などの http 経由(文字列)でパラメータが流れてくる状況で便利）
     * if ($id) {
     *     $predicates['id'] = $id;
     * }
     * $predicates['!id'] = $id; // 上記コードとほぼ同義
     * // 空の定義には「全ての条件が!で除外されたSelectBuilder」も含むので、下記のコードは空の WHERE になる
     * $predicates['!subid IN(?)'] = $db->select('subtable.id', ['!name' => ''])->exists();
     *
     * # No.9,10（ややこしいことをしないで手軽に演算子が埋め込める）
     * $predicates['name:%LIKE%'] = 'hoge';  // WHERE name LIKE "%hoge%"
     * $predicates['period:(~]'] = [0, 100]; // WHERE period > 0 AND period <= 100
     *
     * # No.11（:以降がない場合は No.5～8 になる）
     * $predicates['id'] = 1;        // WHERE id = 1
     * $predicates['id:'] = 1;       // ↑と同じ
     * $predicates['id:!'] = 1;      // 用途なしに見えるが、このように:!とすると WHERE NOT (id = 1) になり、否定が行える
     * $predicates['id:!'] = [1, 2]; // No.5～8 相当なので配列を与えれば WHERE NOT (id IN (1, 2)) になり、IN の否定が行える
     *
     * # No.15（:hoge は hoge = :hoge になる。頻度は低いが下記のように prepare 化するときに指定が楽になる）
     * $stmt = $db->prepareDelete('table_name', ['id = :id']);    // prepare するときは本来ならこのように指定すべきだが・・・
     * $stmt = $db->prepareDelete('table_name', ['id' => ':id']); // このようなミスがよくある（これは id = ":id" に展開されるのでエラーになる）
     * $stmt = $db->prepareDelete('table_name', ':id');           // このように指定したほうが平易で良い。そしてこの時点で id = :id になるので・・・
     * $stmt->executeAffect(['id' => 1]);                         // WHERE id = 1 で実行できる
     * $stmt->executeAffect(['id' => 2]);                         // WHERE id = 2 で実行できる
     *
     * # No.23（最高にややこしいが、実用上は「OR する場合は配列で包む」という認識でまず事足りるはず）
     * # 原則として配列間は AND で結合される。しかし、要素を配列で包むと、現在のコンテキストとは逆（AND なら OR、OR なら AND）の演算子で結合させることができる
     * $predicates = [
     *     'delete_flg' => 0,
     *     [
     *         'create_date < ?' => '2016-01-01',
     *         'update_date < ?' => '2016-01-01',
     *         ['condA', 'condB']
     *     ]
     * ];
     * // WHERE delete_flg = 0 AND ((create_time < '2016-01-01') OR (update_date < '2016-01-01') OR (condA AND condB))
     *
     * // AND を明示することで (create_date, update_date) 間の結合が AND になる
     * $predicates = [
     *     'delete_flg' => 0,
     *     'AND' => [
     *         'create_date < ?' => '2016-01-01',
     *         'update_date < ?' => '2016-01-01',
     *         ['condA', 'condB']
     *     ]
     * ]);
     * // WHERE delete_flg = 0 AND ((create_time < '2016-01-01') AND (update_date < '2016-01-01') AND (condA OR condB))
     *
     * // 上記のような複雑な用途はほとんどなく、実際は下記のような「（アクティブで姓・名から LIKE 検索のような）何らかの AND と OR を1階層」程度が多い
     * $predicates = [
     *     'delete_flg' => 0,
     *     // 要するに配列で包むと OR になる
     *     [
     *         'sei:%LIKE%' => 'hoge',
     *         'mei:%LIKE%' => 'hoge',
     *     ]
     * ]);
     * // WHERE delete_flg = 0 AND ((sei LIKE "%hoge%") OR (mei LIKE "%hoge%"))
     *
     * # No.24（NOT キーで要素が NOT で囲まれる）
     * $predicates = [
     *     'delete_flg' => 0,
     *     'NOT' => [
     *         'sei:%LIKE%' => 'hoge',
     *         'mei:%LIKE%' => 'hoge',
     *     ],
     * ];
     * // WHERE (delete_flg = '0') AND (NOT ((sei LIKE '%hoge%') AND (mei LIKE '%hoge%')))
     *
     * # No.25,26（クエリビルダを渡すとそれらしく埋め込まれる）
     * $predicates = [
     *     // ただの EXSISTS クエリになる
     *     $db->select('subtable')->exists(),
     *     // ? なしのデフォルトではサブクエリの IN になる
     *     'subid1' => $db->select('subtable.subid'),
     *     // ? 付きだとそのとおりになる（ここでは = だが <> とか BETWEEN でも同じ。埋め込み演算子も使える）
     *     'subid2 = ?' => $db->select('subtable.subid')->limit(1),
     * ];
     * // WHERE EXISTS(SELECT * FROM subtable) AND (subid1 IN (SELECT subid FROM subtable)) AND (subid2 = (SELECT subid FROM subtable))
     *
     * # No.28（Operator::method を呼ぶと左辺がキーで遅延設定される）
     * $predicates = [
     *     // like を呼べばキーに演算子を埋め込まなくても LIKE できる
     *     'name' => Operator::like('hoge'),
     *     // not も使える
     *     'text' => Operator::like('hoge')->not(),
     * ];
     * // WHERE name LIKE '%hoge%' AND NOT(text LIKE '%hoge%')
     *
     * # No.31（クロージャを使うと三項演算子を駆使する必要はない上、スコープを閉じ込めることができる）
     * $predicates = [
     *     // $condition 次第で EXISTS したい（この程度なら三項演算子で十分だが、もっと複雑だと三項演算子で救いきれない）
     *     function ($db) use ($condition) {
     *         if (!$condition) {
     *             return [];
     *         }
     *         return $db->select('t_example', $condition)->exists();
     *     },
     * ];
     * ```
     *
     * @param array $predicate 条件配列
     * @param ?array $params bind 値が格納される
     * @param string $andor 結合演算子（内部向け引数なので気にしなくて良い）
     * @param ?bool $filterd 条件が全て ! などでフィルタされたら true が格納される（内部向け引数なので気にしなくて良い）
     * @return array 条件配列
     */
    public static function build(Database $database, array $predicate, ?array &$params = null, string $andor = 'OR', ?bool &$filterd = null): array
    {
        $params = $params ?? [];
        $orand = $andor === 'AND' ? 'OR' : 'AND';
        $criteria = [];

        foreach ($predicate as $cond => $value) {
            if ($value instanceof AbstractCondition) {
                $value = $value($database);
            }
            if ($value instanceof \Closure) {
                $value = $value($database);
            }
            if ($value instanceof \ArrayObject) {
                $value = iterator_to_array($value);
            }

            if (is_int($cond)) {
                // 空値はスキップ
                if (Adhoc::is_empty($value)) {
                    continue;
                }

                // 配列は再帰
                if (is_array($value)) {
                    $cds = [];
                    foreach ($value as $op => $vs) {
                        $ors = self::build($database, [$op => $vs], $params, $orand, $filterd);
                        array_set($cds, implode(" $andor ", Adhoc::wrapParentheses($ors)), null, function ($v) { return !Adhoc::is_empty($v); });
                    }
                    array_set($criteria, implode(" $andor ", Adhoc::wrapParentheses($cds)), null, function ($v) { return !Adhoc::is_empty($v); });
                    continue;
                }

                // Queryable はマージしたものを
                if ($value instanceof Queryable && $value->getQuery()) {
                    $criteria[] = $value->merge($params);
                }
                // :hoge なら hoge = :hoge に展開
                elseif (is_string($value) && strpos($value, ':') === 0) {
                    $criteria[] = substr($value, 1) . ' = ' . $value;
                }
                // 上記以外はそのまま
                else {
                    $criteria[] = $value;
                }
            }
            else {
                $cond = trim($cond);
                $emptyfilter = isset($cond[0]) && $cond[0] === '!';
                if ($emptyfilter) {
                    $vcopy = $value;
                    if (strpos($cond, '/* vcolumn') !== false && is_array($vcopy)) {
                        array_shift($vcopy);
                    }
                    if (Adhoc::is_empty($vcopy)) {
                        $database->debug("filter empty column $cond");
                        $filterd = ($filterd ?? true) && true;
                        continue;
                    }
                    $cond = substr($cond, 1);
                }

                // AND,OR だけは特例処理（カラム指定と曖昧だが "OR" なんて識別子は作れないし指定できないのでOK）
                // 仮に指定するにしても "`OR`" になるはずなので文字列的には一致しない
                $CANDOR = strtoupper($cond);
                if ($CANDOR === 'AND' || $CANDOR === 'OR') {
                    $ors = self::build($database, arrayize($value), $params, $CANDOR === 'AND' ? 'OR' : 'AND', $filterd);
                    array_set($criteria, implode(" $CANDOR ", Adhoc::wrapParentheses($ors)), null, function ($v) { return !Adhoc::is_empty($v); });
                    continue;
                }
                // 同じく、NOT も特別扱い
                if ($CANDOR === 'NOT') {
                    $nots = self::build($database, arrayize($value), $params, $andor, $filterd);
                    array_set($criteria, 'NOT (' . implode(" $orand ", Adhoc::wrapParentheses($nots)) . ')', null, function ($v) { return !Adhoc::is_empty($v); });
                    continue;
                }

                // Operator は列を後定義したものを
                if ($value instanceof Operator) {
                    if (strpos($cond, ':') !== false) {
                        throw new \UnexpectedValueException('OPFUNC and :OP both specified.');
                    }
                    $value->lazy($cond, $database->getCompatiblePlatform());
                    if ($value->getQuery()) {
                        $criteria[] = $value->merge($params);
                        if ($emptyfilter) {
                            $filterd = false;
                        }
                    }
                    continue;
                }
                // Queryable はマージしたものを
                if ($value instanceof Queryable) {
                    if (strpos($cond, '?') === false) {
                        $cond .= $value instanceof SelectBuilder ? ' IN ?' : ' = ?'; // IN のカッコはビルダが付けてくれる
                    }
                    $criteria[] = str_replace('?', $value->merge($params), $cond);
                    if ($emptyfilter) {
                        $filterd = false;
                    }
                    continue;
                }

                // 同上。配列の中に Queryable が紛れている場合
                if (Adhoc::containQueryable($value)) {
                    $subquerys = [];
                    $subvalues = [];
                    foreach ($value as $k => $v) {
                        if ($v instanceof Queryable) {
                            $subquerys[$k] = $v->merge($subvalues);
                        }
                        else {
                            $subvalues[] = $v;
                        }
                    }

                    $cond = str_subreplace($cond, '?', $subquerys);
                    $value = $subvalues;

                    if (strpos($cond, ':') === false && ($diff = count($value) - substr_count($cond, '?')) > 0) {
                        $cond .= ' IN (' . implode(',', array_fill(0, $diff, '?')) . ')';
                    }
                }

                // :区切りで演算子指定モード
                if (strpos($cond, ':') !== false) {
                    [$cond, $ope] = array_map('trim', explode(':', $cond, 2));
                }
                // ? が無いなら column OPERATOR value モード（OPERATOR は型に応じる）
                elseif (strpos($cond, '?') === false) {
                    $ope = Operator::COLVAL;
                }
                // それ以外は素（colA = ? and colB = ? or colC in (?, ?, ?) のような複雑な文字列）
                else {
                    $ope = Operator::RAW;
                }
                $operator = Operator::new($database->getCompatiblePlatform(), $ope, $cond, $value);
                if ($operator->getQuery()) {
                    $criteria[] = $operator->merge($params);
                    if ($emptyfilter) {
                        $filterd = false;
                    }
                }
                elseif ($emptyfilter) {
                    $filterd = ($filterd ?? true) && true;
                }
            }
        }

        return $criteria;
    }

    public static function and(array $predicate): static
    {
        return new static('AND', $predicate);
    }

    public static function or(array $predicate): static
    {
        return new static('OR', $predicate);
    }

    public function __construct(private string $andor, private array $predicate) { }

    public function __invoke(Database $database, ?bool &$filtered = null): Expression
    {
        $orand = $this->andor === 'AND' ? 'OR' : 'AND';
        $exprs = static::build($database, $this->predicate, $params, $orand, $filtered);
        return Expression::new(implode(" {$this->andor} ", Adhoc::wrapParentheses($exprs)), $params);
    }
}
