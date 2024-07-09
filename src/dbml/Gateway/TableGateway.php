<?php

namespace ryunosuke\dbml\Gateway;

use Doctrine\DBAL\Schema\Column;
use ryunosuke\dbml\Attribute\VirtualColumn;
use ryunosuke\dbml\Database;
use ryunosuke\dbml\Entity\Entityable;
use ryunosuke\dbml\Mixin\AffectAndBeforeTrait;
use ryunosuke\dbml\Mixin\AffectAndPrimaryTrait;
use ryunosuke\dbml\Mixin\AffectIgnoreTrait;
use ryunosuke\dbml\Mixin\AffectOrThrowTrait;
use ryunosuke\dbml\Mixin\AggregateTrait;
use ryunosuke\dbml\Mixin\ExportTrait;
use ryunosuke\dbml\Mixin\FindTrait;
use ryunosuke\dbml\Mixin\IteratorTrait;
use ryunosuke\dbml\Mixin\JoinTrait;
use ryunosuke\dbml\Mixin\OptionTrait;
use ryunosuke\dbml\Mixin\SelectAggregateTrait;
use ryunosuke\dbml\Mixin\SelectForAffectTrait;
use ryunosuke\dbml\Mixin\SelectForUpdateTrait;
use ryunosuke\dbml\Mixin\SelectInShareTrait;
use ryunosuke\dbml\Mixin\SelectMethodTrait;
use ryunosuke\dbml\Mixin\SelectOrThrowTrait;
use ryunosuke\dbml\Mixin\SubAggregateTrait;
use ryunosuke\dbml\Mixin\SubSelectTrait;
use ryunosuke\dbml\Mixin\YieldTrait;
use ryunosuke\dbml\Query\Pagination\Paginator;
use ryunosuke\dbml\Query\Pagination\Sequencer;
use ryunosuke\dbml\Query\SelectBuilder;
use ryunosuke\dbml\Query\Statement;
use ryunosuke\dbml\Query\TableDescriptor;
use ryunosuke\dbml\Utility\Adhoc;
use ryunosuke\utility\attribute\Attribute\DebugInfo;
use ryunosuke\utility\attribute\ClassTrait\DebugInfoTrait;
use function ryunosuke\dbml\array_each;
use function ryunosuke\dbml\array_get;
use function ryunosuke\dbml\array_unset;
use function ryunosuke\dbml\arrayize;
use function ryunosuke\dbml\cache_fetch;
use function ryunosuke\dbml\concat;
use function ryunosuke\dbml\flagval;
use function ryunosuke\dbml\parameter_length;
use function ryunosuke\dbml\snake_case;
use function ryunosuke\dbml\split_noempty;

// @formatter:off
/**
 * ゲートウェイクラス
 *
 * Database の各種メソッドで「$table に自身に指定した」かのように動作する。
 * Database や SelectBuilder に実装されているメソッドは大抵利用できるが、コード補完に出ないメソッドはなるべく使用しないほうがよい。
 *
 * ```php
 * // ゲートウェイはこのように Database 経由で取得する
 * $gw = $db->table_name;   // プロパティ版（素の状態で取得）
 * $gw = $db->table_name(); // メソッド版（引数で各句を指定可能）
 *
 * // 全行全列を返す
 * $gw->array('*');
 * // id列の配列を返す
 * $gw->lists('id');
 *
 * // 複合主キー(1, 2)で検索した1行を返す
 * $gw->find(1, 2);
 *
 * // レコードが存在するか bool で返す
 * $gw->exists();
 * $gw->('*', ['status' => 'deleted']);
 * // id 列の最小値を返す
 * $gw->min('id');
 *
 * // 自身と子供テーブルを階層化して返す
 * $gw->array([
 *     'childassoc' => $db->child(),
 * ]);
 *
 * // 自身と子供テーブルを JOIN して返す
 * $gw->array([
 *     // INNER JOIN
 *     '+children1' => $db->child(),
 *     // LEFT JOIN
 *     '<children2' => $db->child(),
 * ]);
 *
 * // 自身と子供テーブルの集計を返す
 * $gw->array([
 *     'subcount' => $db->child->subcount(),
 *     'submin'   => $db->child->submin('child_id'),
 *     'submax'   => $db->child->submax('child_id'),
 *     'subsum'   => $db->child->subsum('child_id'),
 *     'subavg'   => $db->child->subavg('child_id'),
 * ]);
 *
 * // 行を挿入する
 * $gw->insert(['data array']);
 * // 行を更新する
 * $gw->update(['data array'], ['where array']);
 * // 行を削除する
 * $gw->delete(['where array']);
 *
 * // カラム値をインクリメント
 * $gw[1]['hoge_count'] += 1;                         // こういう指定もできるがこれは SELECT + UPDATE なので注意
 * $gw[1]['hoge_count'] = $db->raw('hoge_count + 1'); // 単純かつアトミックにやるならこうしなければならない
 * ```
 *
 * ### クエリスコープ
 *
 * SELECT 句や WHERE 句のセットに名前をつけて、簡単に呼ぶことができる。
 *
 * 基本的には `addScope` で登録して `scope` で縛る。
 * `addScope` の引数はクエリビルダ引数と全く同じだが、第1引数のみ Closure を受け付ける。
 * Closure を受けたスコープはクエリビルダ引数を返す必要があるが、引数を受けられるのでパラメータ付きスコープを定義することができる。
 * また、 Closure 内の `$this` は「その時点の Gateway インスタンス」を指すように bind される。これにより `$this->alias` などが使用でき、当たっているスコープやエイリアス名などが取得できる。
 * さらに `$this` に下記の `column` `where` `orderBy` などを適用して return すればクエリビルダ引数を返さなくてもメソッドベースで適用できる。
 * scopeXXX で始まるメソッドを定義すると上記のクロージャで定義したような動作となる。
 *
 * `scoping` を使用するとスコープを登録せずにその場限りのスコープを当てることができる。
 * また `column` `where` `orderBy` などの個別メソッドがあり、句別にスコープを当てることもできる。
 *
 * ```php
 * // デフォルトスコープを登録（select 時に常に `NOW()` が付いてくるようになる）
 * $gw->addScope('', 'NOW()');
 * // 有効レコードスコープを登録（select 時に `WHERE delete_flg=0` が付くようになる）
 * $gw->addScope('active', [], ['delete_flg' => 0]);
 * // 最新レコードスコープを登録（select 時に `ORDER BY create_date DESC LIMIT 10` が付くようになる）
 * $gw->addScope('latest', function ($limit = 10) {
 *     return [
 *         'orderBy' => 'create_date DESC',
 *         'limit'   => $limit,
 *     ];
 * });
 * // 上記の this 返し版（意味は同じ）
 * $gw->addScope('latest', function ($limit = 10) {
 *     return $this->orderBy('create_date DESC')->limit($limit);
 * });
 * // 上記のメソッド版（意味は同じ。$this を返す必要はない）
 * public function scopeLatest($limit = 10)
 * {
 *     $this->orderBy('create_date DESC')->limit($limit);
 * }
 *
 * // 有効レコードを全取得する（'active' スコープで縛る）
 * $gw->scope('active')->array();
 * // → SELECT NOW(), t_table.* FROM t_table WHERE t_table.delete_flg = 0
 * // NOW() が付いているのはデフォルトスコープが有効だから
 *
 * // デフォルトスコープを無効化して active, latest で縛る
 * $gw->noscope()->scope('active')->scope('latest')->array();
 * // → SELECT t_table.* FROM t_table WHERE t_table.delete_flg = 0 ORDER BY t_table.create_date DESC LIMIT 10
 * // これでも同じ。複数のスコープはスペース区切りで同時指定できる
 * $gw->noscope()->scope('active latest')->array();
 *
 * // Closure なスコープはパラメータを指定できる
 * $gw->scope('latest', 9)->array();
 * // → SELECT NOW(), t_table.* FROM t_table ORDER BY t_table.create_date DESC LIMIT 9
 *
 * // スコープを登録せず、その場限りのものとして縛る
 * $gw->scoping('id', ['invalid_flg' => 1], 'id DESC')->array();
 * // → SELECT id FROM t_table WHERE t_table.invalid_flg = 1 ORDER BY id DESC
 * // それぞれの句の個別メソッドもある
 * $gw->column('id')->where(['invalid_flg' => 1])->array();
 * // → SELECT id FROM t_table WHERE t_table.invalid_flg = 1
 *
 * // スコープは insert/update/delete にも適用できる
 * $gw->scope('active')->update(['column' => 'data']);
 * // → UPDATE t_table SET column = 'data' WHERE t_table.delete_flg = 0
 * ```
 *
 * insert/update/delete に当たるスコープの仕様はかなり上級者向けなので、基本的には「where が当たる」という認識でよい。
 * そもそも insert/update/delete に対してスコープを当てる機会自体が稀だと思うので、基本的には気にしなくてもよい。
 * （スコープを当てない insert/update/delete は通常通りの動作）。
 *
 * insert/update/delete にスコープを当てるときはデフォルトスコープに注意。
 * ありがちなのは上記の例で言うと `delete_flg = 0` をデフォルトスコープにしているときで、このとき `$gw->update(['delete_flg' => 1], ['primary_id' => 99])` として無効化しようとしても無効化されない。
 * デフォルトスコープの `delete_flg = 0` が当たってヒットしなくなるからである。
 * 基本的に insert/update/delete にスコープを当てるときは `noscope` や `unscope` でデフォルトスコープを外したほうが無難。
 * あるいは ignoreAffectScope でデフォルトスコープを外しておく。
 *
 * スコープが当たっているクエリビルダは `select` メソッドで取得できる。
 * ただ1点注意として、スコープを当てても**オリジナルのインスタンスは変更されない。変更が適用された別のインスタンスを返す。**
 * 下記のコードが分かりやすい。
 *
 * ```
 * // これは誤り
 * $gw->scope('active');
 * $gw->array();
 * // → `SELECT * FROM table_name` となり、スコープが当たっていない
 *
 * // これが正しい
 * $gw = $gw->scope('active');
 * $gw->array();
 * // → `SELECT * FROM table_name WHERE table_name.delete_flg = 0` となり、スコープが当たっている
 *
 * // あるいはメソッドチェーンでも良い（良い、というかそれを想定している）
 * $gw->scope('active')->array();
 * ```
 *
 * ### 仮想カラム
 *
 * `(virtual|get|set)VirtualNameColumn` というメソッドを定義すると仮想カラムとしてアクセスできるようになる（仮想カラムに関しては {@link Database::overrideColumns()} を参照）。
 * 参照時は get, 更新時は set が呼ばれる。
 * virtual の場合は get で引数なし、 set で引数ありでコールされる。
 * 実処理はメソッド本体だが、属性を用いて implicit や type 属性を指定できる（ここでは記述できないのでテストを参照）。
 *
 * ```php
 * // full_name という仮想カラムを定義（取得）
 * public function getFullNameColumn()
 * {
 *     return 'CONCAT(first_name, " ", family_name)';
 * }
 * // full_name という仮想カラムを定義（設定）
 * public function setFullNameColumn($value, $row)
 * {
 *     return array_combine(['first_name', 'family_name'], explode(':', $value, 2));
 * }
 * // full_name という仮想カラムを定義（上記2つを定義するのとほぼ同義）
 * public function virtualFullNameColumn($value=null, $row=null)
 * {
 *     if (func_num_args()) {
 *         return array_combine(['first_name', 'family_name'], explode(':', $value, 2));
 *     }
 *     else {
 *         return 'CONCAT(first_name, " ", family_name)';
 *     }
 * }
 * ```
 *
 * ### Traversable, Countable
 *
 * Traversable と Countable を実装しているので、 foreach で回すことができるし count() で件数取得もできる。
 *
 * ```php
 * // active スコープを foreach で回す
 * foreach ($gw->scope('active') as $item) {
 *     var_dump($item);
 * }
 *
 * // active スコープの件数を取得
 * $gw->count();
 * ```
 *
 * foreach で回すときのメソッドはデフォルトで array。 これは $defaultIteration で変更できる。
 * $defaultIteration は複数設定できる箇所があるが、下記の優先順位となる。
 *
 * - Database の defaultIteration オプション
 * - クラスの `$defaultIteration` プロパティ
 * - 明示的に設定した `$defaultIteration` プロパティ
 *
 * 下に行くほど優先される。要するに単純に個別で指定するほど強い。
 *
 * count() は `count($gw)` と `$gw->count('*')` で挙動が異なる（{@link count()} を参照）
 *
 * ### JOIN
 *
 * メソッドコール or マジックゲット or マジックコールを使用して JOIN を行うことができる。
 * それぞれできる範囲と記法が異なり、特色がある（メソッドコールは冗長、マジックゲットは end がウザイ、マジックコールはエイリアスが張れない など）。
 *
 * ```php
 * # メソッドコール（すべての基本。これがベースであり多少冗長になるが出来ないことはない）
 * $db->t_article->join('inner', $db->t_comment, [$oncond])->array();
 *
 * # マジックゲット（テーブル名でアクセスすると「自身に対象を JOIN して対象を返す」という動作になる）
 * // end() が必要
 * $db->t_article->t_comment->end()->array();
 * // end() がないと SELECT * FROM t_comment になる。なぜなら「t_article に t_comment を JOIN して t_comment を返す」という動作なので、t_comment は何も作用していない。つまり t_comment に対して array() しているだけである
 * $db->t_article->t_comment->array();
 * // このように「JOIN 対象に何らかの操作を行いたい」場合はマジックゲットが便利
 * $db->t_article->t_comment->as('C')->scope('active')->orderBy('id')->end()->array();
 *
 * # マジックコール（テーブル名でコールすると「自身に対象を JOIN して自身を返す」という動作になる）
 * // 「自身を返す」ので end() は不要
 * $db->t_article->t_comment()->array();
 * // 「自身を返す」ので t_user は t_article に JOIN される
 * $db->t_article->t_comment()->t_user()->array();
 * // 引数には scoping 引数が使える
 * $db->t_article->t_comment('id, comment', ['id' => 3])->array();
 *
 * # マジックゲット＋オフセットアクセス＋invoke を使用した高度な例
 * $db->t_article->t_comment['@active AS C']()->array();
 * ```
 *
 * 厳密にやりたいならメソッドコール、ある程度条件を付与したいならマジックゲット、とにかく単に JOIN して引っ張りたいだけならマジックコールが適している。
 *
 * マジック系 JOIN の 外部結合・内部結合は $defaultJoinMethod で決定する（メソッドコールは専用のメソッドが生えている）。
 * $defaultJoinMethod に INNER, LEFT などの文字列を設定するとそれで結合される。
 * ただし、特殊な結合モードとして "AUTO" がある。 AUTO JOIN は「外部キーカラム定義」に基づいて自動で INNER or LEFT を決定する。
 * 極めて乱暴に言えば「他方が見つからない可能性がある場合」に LEFT になる（カラム定義や親子関係を見て決める）。
 * 基本的にはこの動作で問題なく、明示指定より AUTO の方が優れているが、他の結合条件によっては「共に NOT NULL だけど結合したら他方が NULL」になる状況はありうるため、完全に頼り切ってはならない。
 *
 * JOIN の時、スコープがあたっている場合は下記の動作になる。
 *
 * | clause                                | 説明
 * |:--                                    |:--
 * | column                                | JOIN 時の取得カラムとして使用される
 * | where                                 | **ON 句として使用される**
 * | orderBy                               | 駆動表の ORDER 句に追加される
 * | limit, groupBy, having                | これらが一つでも指定されている場合はそれらを適用した**サブクエリと JOIN される。**この際、上記の where -> ON の適用は行われない（サブクエリに内包される）
 *
 * 「where が ON 句として使用される」はとても重要な性質で、これを利用することで外部キー結合しつつ、追加条件を指定することが出来るようになる。
 * 「駆動表の ORDER 句に追加」もそれなりに重要で、 RDBMS における JOIN は本質的には順序を持たないが、駆動表に追加することで擬似的に順序付きを実現できる。
 *
 * limit, having などがサブクエリ化されるのはこれらが指定されているときのテーブルとしての JOIN は本質的に不可能だからである。
 * 場合によっては非常に非効率なクエリになるので注意。
 * また、その性質上、外部キー結合をすることはできない。
 *
 * @method string                 getDefaultIteration()
 * @method $this                  setDefaultIteration($iterationMode)
 * @method string                 getDefaultJoinMethod()
 * @method $this                  setDefaultJoinMethod($string)
 * @method array                  getIgnoreAffectScope()
 * @method $this                  setIgnoreAffectScope(array $ignoreAffectScope)
 * @method \Closure               getScopeRenamer()
 * @method $this                  setScopeRenamer(\Closure $scopeRenamer)
 * @method \Closure               getColumnRenamer()
 * @method $this                  setColumnRenamer(\Closure $columnRenamer)
 *
 * これは phpstorm の as keyword が修正されたら不要になる
 * @method array|Entityable[] array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
 */
// @formatter:on
class TableGateway implements \ArrayAccess, \IteratorAggregate, \Countable
{
    use DebugInfoTrait;
    use OptionTrait;
    use IteratorTrait {
        IteratorTrait::count as countIterator;
    }

    use JoinTrait;

    use SelectMethodTrait {
        selectArray as public array; // phpstorm がエラーを吐くので public を付けている
        selectAssoc as assoc;
        selectLists as lists;
        selectPairs as pairs;
        selectTuple as tuple;
        selectValue as value;
    }
    use SelectOrThrowTrait {
        selectArrayOrThrow as arrayOrThrow;
        selectAssocOrThrow as assocOrThrow;
        selectListsOrThrow as listsOrThrow;
        selectPairsOrThrow as pairsOrThrow;
        selectTupleOrThrow as tupleOrThrow;
        selectValueOrThrow as valueOrThrow;
    }
    use SelectInShareTrait {
        selectArrayInShare as arrayInShare;
        selectAssocInShare as assocInShare;
        selectListsInShare as listsInShare;
        selectPairsInShare as pairsInShare;
        selectTupleInShare as tupleInShare;
        selectValueInShare as valueInShare;
    }
    use SelectForUpdateTrait {
        selectArrayForUpdate as arrayForUpdate;
        selectAssocForUpdate as assocForUpdate;
        selectListsForUpdate as listsForUpdate;
        selectPairsForUpdate as pairsForUpdate;
        selectTupleForUpdate as tupleForUpdate;
        selectValueForUpdate as valueForUpdate;
    }
    use SelectForAffectTrait {
        selectArrayForAffect as arrayForAffect;
        selectAssocForAffect as assocForAffect;
        selectListsForAffect as listsForAffect;
        selectPairsForAffect as pairsForAffect;
        selectTupleForAffect as tupleForAffect;
        selectValueForAffect as valueForAffect;
    }
    use FindTrait;
    use YieldTrait;
    use ExportTrait;
    use SelectAggregateTrait;

    use AggregateTrait {
        AggregateTrait::count as countAggregate;
    }
    use SubSelectTrait {
        subselectArray as subArray;
        subselectAssoc as subAssoc;
        subselectLists as subLists;
        subselectPairs as subPairs;
        subselectTuple as subTuple;
        subselectValue as subValue;
    }
    use SubAggregateTrait;

    use AffectIgnoreTrait {
        insertSelectIgnoreWithoutTable as public insertSelectIgnore;
        insertArrayIgnoreWithoutTable as public insertArrayIgnore;
        updateArrayIgnoreWithoutTable as public updateArrayIgnore;
        deleteArrayIgnoreWithoutTable as public deleteArrayIgnore;
        modifyArrayIgnoreWithoutTable as public modifyArrayIgnore;
        changeArrayIgnoreWithoutTable as public changeArrayIgnore;
        affectArrayIgnoreWithoutTable as public affectArrayIgnore;
        saveIgnoreWithoutTable as public saveIgnore;
        insertIgnoreWithoutTable as public insertIgnore;
        updateIgnoreWithoutTable as public updateIgnore;
        deleteIgnoreWithoutTable as public deleteIgnore;
        invalidIgnoreWithoutTable as public invalidIgnore;
        reviseIgnoreWithoutTable as public reviseIgnore;
        upgradeIgnoreWithoutTable as public upgradeIgnore;
        removeIgnoreWithoutTable as public removeIgnore;
        destroyIgnoreWithoutTable as public destroyIgnore;
        createIgnoreWithoutTable as public createIgnore;
        modifyIgnoreWithoutTable as public modifyIgnore;
        modifyIgnoreWithoutTable as public modifyIgnore;
    }
    use AffectOrThrowTrait {
        insertArrayOrThrowWithoutTable as public insertArrayOrThrow;
        createWithoutTable as public create;
        insertOrThrowWithoutTable as public insertOrThrow;
        updateOrThrowWithoutTable as public updateOrThrow;
        deleteOrThrowWithoutTable as public deleteOrThrow;
        invalidOrThrowWithoutTable as public invalidOrThrow;
        reviseOrThrowWithoutTable as public reviseOrThrow;
        upgradeOrThrowWithoutTable as public upgradeOrThrow;
        removeOrThrowWithoutTable as public removeOrThrow;
        destroyOrThrowWithoutTable as public destroyOrThrow;
        reduceOrThrowWithoutTable as public reduceOrThrow;
        upsertOrThrowWithoutTable as public upsertOrThrow;
        modifyOrThrowWithoutTable as public modifyOrThrow;
        replaceOrThrowWithoutTable as public replaceOrThrow;
    }
    use AffectAndPrimaryTrait {
        insertAndPrimaryWithoutTable as public insertAndPrimary;
        updateAndPrimaryWithoutTable as public updateAndPrimary;
        deleteAndPrimaryWithoutTable as public deleteAndPrimary;
        invalidAndPrimaryWithoutTable as public invalidAndPrimary;
        reviseAndPrimaryWithoutTable as public reviseAndPrimary;
        upgradeAndPrimaryWithoutTable as public upgradeAndPrimary;
        removeAndPrimaryWithoutTable as public removeAndPrimary;
        destroyAndPrimaryWithoutTable as public destroyAndPrimary;
        upsertAndPrimaryWithoutTable as public upsertAndPrimary;
        modifyAndPrimaryWithoutTable as public modifyAndPrimary;
        replaceAndPrimaryWithoutTable as public replaceAndPrimary;
    }
    use AffectAndBeforeTrait {
        updateArrayAndBeforeWithoutTable as public updateArrayAndBefore;
        deleteArrayAndBeforeWithoutTable as public deleteArrayAndBefore;
        modifyArrayAndBeforeWithoutTable as public modifyArrayAndBefore;
        updateAndBeforeWithoutTable as public updateAndBefore;
        deleteAndBeforeWithoutTable as public deleteAndBefore;
        invalidAndBeforeWithoutTable as public invalidAndBefore;
        reviseAndBeforeWithoutTable as public reviseAndBefore;
        upgradeAndBeforeWithoutTable as public upgradeAndBefore;
        removeAndBeforeWithoutTable as public removeAndBefore;
        destroyAndBeforeWithoutTable as public destroyAndBefore;
        reduceAndBeforeWithoutTable as public reduceAndBefore;
        upsertAndBeforeWithoutTable as public upsertAndBefore;
        modifyAndBeforeWithoutTable as public modifyAndBefore;
        replaceAndBeforeWithoutTable as public replaceAndBefore;
    }

    protected function getDatabase(): Database { return $this->database; }

    /** @var array scope のデフォルト値 */
    private static array $defargs = [
        'column'  => [],
        'where'   => [],
        'orderBy' => [],
        'limit'   => [],
        'groupBy' => [],
        'having'  => [],
        'set'     => [],
    ];

    protected string $defaultIteration  = '';
    protected string $defaultJoinMethod = '';

    private Database $database;

    private TableGateway $original;

    private string  $tableName;
    private ?string $alias;

    private \ArrayObject $scopes;
    private array        $activeScopes = ['' => []];

    private ?string $foreign = null;
    private ?string $hint    = null;

    #[DebugInfo(false)]
    private TableGateway $end;

    /** @var TableGateway[] */
    private array $joins      = [];
    private array $joinParams = [];

    private array $pkukval = [];

    public static function getDefaultOptions(): array
    {
        return [
            /** @var string 直接回した場合のフェッチモード */
            'defaultIteration'  => 'array',
            /** @var string マジック JOIN 時のデフォルトモード */
            'defaultJoinMethod' => 'auto',
            /** @var array affect 系で無視するスコープ */
            'ignoreAffectScope' => [],
            /** @var \Closure メソッドベーススコープの命名規則 */
            'scopeRenamer'      => function ($name) { return lcfirst($name); },
            /** @var \Closure メソッドベース仮想カラムの命名規則 */
            'columnRenamer'     => function ($name) { return snake_case($name); },
        ];
    }

    /**
     * コンストラクタ
     */
    public function __construct(Database $database, string $table_name, ?string $entity_name = null)
    {
        $this->database = $database;
        $this->tableName = $table_name;
        $this->alias = $entity_name;

        $this->original = $this;
        $this->scopes = new \ArrayObject();

        $default = [];
        if ($this->defaultIteration) {
            $default['defaultIteration'] = $this->defaultIteration;
        }
        if ($this->defaultJoinMethod) {
            $default['defaultJoinMethod'] = $this->defaultJoinMethod;
        }
        $this->setDefault($default + $database->getOptions());

        $cacher = $this->database->getCacheProvider();
        $classname = strpos(static::class, '@anonymous') === false ? strtr(static::class, ['\\' => '-']) : sha1(static::class);
        $magic_methods = cache_fetch($cacher, "TableGateway-$classname-magics", function () {
            $scope_renamer = $this->getUnsafeOption('scopeRenamer');
            $column_renamer = $this->getUnsafeOption('columnRenamer');

            $result = [];
            foreach (get_class_methods($this) as $method) {
                if (preg_match('#^scope(.+)#i', $method, $m)) {
                    $result[] = [
                        'type'   => 'scope',
                        'name'   => $scope_renamer($m[1]),
                        'method' => $method,
                    ];
                }
                if (preg_match('#^(virtual|get|set)(.+?)Column$#i', $method, $m)) {
                    $result[] = [
                        'type'    => 'vcolumn',
                        'subtype' => strtolower($m[1]),
                        'name'    => $column_renamer($m[2]),
                        'method'  => $method,
                    ];
                }
            }
            return $result;
        });

        $vcolumns = [];
        foreach ($magic_methods as $method) {
            if ($method['type'] === 'scope') {
                $this->addScope($method['name'], function (...$args) use ($method) {
                    $this->{$method['method']}(...$args);
                    return $this;
                });
                continue;
            }
            elseif ($method['type'] === 'vcolumn') {
                $rmethod = new \ReflectionMethod($this, $method['method']);

                $attrs = VirtualColumn::of($rmethod)?->getNamedArguments() ?? [];
                $attrs['type'] = $attrs['type'] ?? null;
                $attrs['lazy'] = flagval($attrs['lazy'] ?? true);
                $attrs['implicit'] = flagval($attrs['implicit'] ?? false);

                $closure = $rmethod->getClosure($this);
                $vcolumns[$method['name']] ??= [];
                if ($method['subtype'] === 'virtual') {
                    $vcolumns[$method['name']] += array_replace($attrs, [
                        'select' => fn() => $closure(),
                        'affect' => fn($value, $row) => $closure($value, $row),
                    ]);
                }
                if ($method['subtype'] === 'get') {
                    $vcolumns[$method['name']] += array_replace($attrs, [
                        'select' => $closure,
                    ]);
                }
                if ($method['subtype'] === 'set') {
                    $vcolumns[$method['name']] += array_replace($attrs, [
                        'affect' => $closure,
                    ]);
                }
            }
        }
        $this->database->overrideColumns([
            $table_name => $vcolumns,
        ]);

        $this->addScope('');
        $this->setProvider(function () {
            $method = $this->getDefaultIteration();
            return $this->$method();
        });
    }

    /**
     * 自身と指定先テーブルを JOIN する
     *
     * 返り値として「JOIN したテーブルの Gateway」を返す。
     * JOIN 先に対してなにかしたい場合は {@link end()} が必要。冒頭の「メソッドコール or マジックゲット or マジックコール」も参照。
     */
    public function __get(string $name): ?self
    {
        $tname = $this->database->convertTableName($name);
        if (isset($this->database->$tname)) {
            $that = $this->join($this->getUnsafeOption('defaultJoinMethod') ?: 'auto', $this->database->$name);
            return end($that->joins);
        }
        return null;
    }

    /**
     * サポートされない
     *
     * 将来のために予約されており、呼ぶと無条件で例外を投げる。
     */
    public function __set(string $name, mixed $value): void { throw new \DomainException(__METHOD__ . ' is not supported.'); }

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

        // マジックジョイン
        $tname = $this->database->convertTableName($name);
        if (isset($this->database->$tname)) {
            return $this->join($this->getUnsafeOption('defaultJoinMethod') ?: 'auto', $this->database->$name(...$arguments));
        }

        throw new \BadMethodCallException("'$name' is undefined.");
    }

    /**
     * scoping + end する
     *
     * 引数で scoping(...func_get_args()) したあと end(1) することで JOIN先 Geteway を返す。
     *
     * 冒頭に記載の通り、 マジックコールは「自身に対象を JOIN して自身」を返す。
     * 引数は各句を表すので、**エイリアス（AS A）やスコープを適用することが出来ない**。
     *
     * つまり、ただ呼び出すだけで無意味なように思えるが、これがあることで `$db->t_table['@scope AS T']('column', 'where')` のような記法が可能になっている。
     *
     * サンプルは {@link offsetGet()} を参照。
     *
     * @inheritdoc scoping()
     *
     * @return $this|array|Entityable|mixed レコード・カラム値・JOIN先 Geteway
     */
    public function __invoke($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        if (filter_var($tableDescriptor, \FILTER_VALIDATE_INT) !== false) {
            return $this->pk($tableDescriptor);
        }

        $args = func_get_args();
        if ($args) {
            $this->scoping(...$args);
        }
        return $this->end(1);
    }

    /**
     * 完全なクエリ文字列を返す
     *
     * エスケープ済みで実行可能なクエリを返す。
     *
     * ```php
     * // SELECT * FROM t_table WHERE t_table.primary_id = '1'
     * echo $gw->pk(1);
     * // SELECT T.id, T.title FROM t_table T WHERE T.create_at = '2014-03-31' LIMIT 1
     * echo $gw->as('T')->column(['id', 'title'])->where(['create_at' => '2014-03-31'])->limit(1);
     * ```
     */
    public function __toString(): string
    {
        return $this->select([])->queryInto();
    }

    private function _primary($pkval): array
    {
        $pcols = $this->database->getSchema()->getTablePrimaryKey($this->tableName)->getColumns();
        $pvals = array_values((array) $pkval);
        return array_combine(array_slice($pcols, 0, count($pvals)), $pvals) ?: throw new \InvalidArgumentException("array_combine: argument's length is not match primary columns.");
    }

    private function _accessor(string $name, $value)
    {
        // 引数なしの場合は getter として振る舞う
        if ($value === null) {
            return $this->$name;
        }

        $that = $this->clone();
        $that->$name = $value;
        return $that;
    }

    /**
     * なにがしかの存在を返す
     *
     * $offset が数値・配列なら主キーとみなして行の存在を返す。
     * $offset がそれ以外ならカラムの存在を返す。
     *
     * ```php
     * # 行の存在を確認する
     * $exists1 = isset($gw[1]);      // 単一主キー値1のレコードがあるなら true
     * $exists2 = isset($gw[[1, 2]]); // 複合主キー値[1, 2]のレコードがあるなら true
     *
     * # カラムの存在を確認する
     * $exists1 = isset($gw['article_title']);    // true
     * $exists2 = isset($gw['undefined_column']); // false
     * ```
     */
    public function offsetExists(mixed $offset): bool
    {
        if (is_array($offset) || filter_var($offset, \FILTER_VALIDATE_INT) !== false) {
            return $this->exists('*', $this->_primary($offset));
        }
        return $this->describe()->hasColumn($offset);
    }

    /**
     * なにがしかの値を取得する
     *
     * $offset が数値・配列なら主キーとみなして where する（≒pk）。
     * $offset が '*' なら*指定とみなしてレコードを返す（≒tuple）。
     * $offset が半角英数字ならカラムとみなしてカラム値を返す（≒value）。
     * $offset がテーブル記法ならその記法が適用された自分自身を返す。
     * テーブル記法のうち、 [condition] だけであれば `[]` が省略可能となる。
     *
     * ```php
     * # 数値・配列なら pk (where) と同義
     * $row = $gw[1]->tuple();      // 単一主キー値1のレコードを返す
     * $row = $gw[[1, 2]]->tuple(); // 複合主キー値[1, 2]のレコードを返す
     * $row = $gw->find($pk);       // 上2つは実質的にこれの糖衣構文
     *
     * # レコードを返す
     * $row = $gw['*'];
     * // ただし、WHERE を指定しないとエラーになるので通常はこのように使用する
     * $row = $gw->[1]['*'];  // 主キー=1 の全カラムを返す（SELECT * FROM t_table WHERE id = 1）
     * $row = $gw->[1]['**']; // 怠惰取得も可能（怠惰取得については SelectBuilder::column() を参照）
     *
     * # カラム値を返す
     * $title = $gw['article_title'];
     * // ただし、WHERE を指定しないとほぼ意味がないので通常はこのように使用する
     * $title = $gw->pk(1)['article_title'];
     * $title = $gw->scope('scopename')['article_title'];
     *
     * # スコープとエイリアスが適用された自分自身を返す
     * $gw = $gw['@scope1@scope2 AS GW'];
     * $gw = $gw->scope('scope1')->scope('scope2')->as('GW'); // 上記は実質的にこれと同じ
     *
     * # エイリアスやカラムも指定できるのでこういった面白い指定も可能
     * $gw['G.id']->array();
     * // SELECT G.id FROM t_table G
     *
     * # [condition] だけであれば [] は不要。下記はすべて同じ意味になる
     * $gw = $gw['[id: 123]']; // 本来であればこのように指定すべきだが・・・
     * $gw = $gw['id: 123'];   // [] は省略可能（[] がネストしないのでシンタックス的に美しくなる）
     * $gw = $gw['id=123'];    // 素の文字列が許容されるならこのようにすると属性アクセスしてるように見えてさらに美しい
     * $gw = $gw->where(['id' => 123]); // あえてメソッドモードで指定するとしたらこのようになる
     *
     * # invoke と組み合わせると下記のようなことが可能になる
     * $db->t_article->t_comment['@scope1@scope2 AS C']($column, $where);
     * ```
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (is_array($offset) || filter_var($offset, \FILTER_VALIDATE_INT) !== false) {
            return $this->pk($offset);
        }
        if (preg_match('#^\\*+$#ui', $offset)) {
            return $this->tuple($offset);
        }
        if (preg_match('#^[_a-z0-9]+$#ui', $offset)) {
            return $this->value($offset);
        }

        // テーブル記法パースをするが、 "id: 2" のような特別な表記は yaml 配列として扱う
        $td = new TableDescriptor($this->database, $this->tableName . ' ' . $offset, []);
        if ($offset[0] !== '[' && $offset[0] !== '{' && $td->remaining) {
            $td = new TableDescriptor($this->database, $this->tableName . "[$offset]", []);
        }

        $that = $this->clone();
        $that->as($td->alias);
        $that->foreign($td->fkeyname);
        foreach ($td->scope as $scope => $sargs) {
            $that->scope($scope, ...$sargs);
        }
        if ($td->column) {
            $that->column($td->column);
        }
        if ($td->condition) {
            $that->where($td->condition);
        }
        if ($td->order) {
            $that->orderBy($td->order);
        }
        if ($td->offset || $td->limit) {
            $that->limit([$td->offset => $td->limit]);
        }
        return $that;
    }

    /**
     * なにがしかの値を設定する
     *
     * $offset が null なら {@link insert()} になる。
     * $offset が数値・配列なら {@link modify()} になる。
     * $offset が半角英数字ならカラムの {@link update()} になる。
     *
     * ```php
     * # 1行 insert
     * $gw[] = [$dataarray]; // $dataarray が insert される
     *
     * # 1行 modify
     * $gw[1] = [$dataarray];      // $gw->modify([$dataarray] + [pcol => 1])
     * $gw[[1, 2]] = [$dataarray]; // $gw->modify([$dataarray] + [pcol1 => 1, pcol2 => 2])
     *
     * # 記事のタイトルを設定する
     * $gw['article_title'] = 'タイトル';
     * // ただし、WHERE を指定しないと全行更新され大事故になるので通常は下記のように何らかで縛って使用する
     * $gw->scope('scopename')['article_title'] = 'タイトル';
     * $gw['id: 1']['article_title'] = 'タイトル';
     * $gw->pk(1)['article_title'] = 'タイトル';
     * $gw[1]['article_title'] = 'タイトル';
     * ```
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->insert($value);
            return;
        }
        if (is_array($offset) || filter_var($offset, \FILTER_VALIDATE_INT) !== false) {
            $this->modify($value + $this->_primary($offset));
            return;
        }
        $this->update([$offset => $value]);
    }

    /**
     * なにがしかの値を削除する
     *
     * $offset が数値・配列なら主キー指定の {@link delete()} になる。
     * それ以外は例外を投げる。
     *
     * ```php
     * # 主キーで削除
     * unset($gw[1]);      // 単一主キー値1のレコードを削除する
     * unset($gw[[1, 2]]); // 複合主キー値[1, 2]のレコードを削除する
     * ```
     */
    public function offsetUnset(mixed $offset): void
    {
        if (is_array($offset) || filter_var($offset, \FILTER_VALIDATE_INT) !== false) {
            $this->delete($this->_primary($offset));
            return;
        }
        throw new \DomainException(__METHOD__ . ' is not supported.');
    }

    /**
     * コピーインスタンスを返す
     *
     * 「コピーインスタンス」とは「オリジナルではないインスタンス」のこと。
     * オリジナルでなければコピーなので複数回呼んでも初回以外は同じインスタンスを返す。
     * それを避けるには $force に true を渡す必要がある。
     */
    public function clone(bool $force = false): static
    {
        $this->resetResult();

        // スコープを呼ぶたびにコピーが生成されるのは無駄なので clone する（ただし、1度だけ）
        if ($force || $this->original === $this) {
            return clone $this;
        }
        return $this;
    }

    /**
     * 自身の Table オブジェクトを返す
     *
     * @inheritdoc Database::describe()
     */
    public function describe(): \Doctrine\DBAL\Schema\Table
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->database->describe($this->tableName);
    }

    /**
     * 行を正規化する
     */
    public function normalize(array $row): array
    {
        return $row;
    }

    /**
     * 無効カラムを返す
     */
    public function invalidColumn(): ?array
    {
        return null;
    }

    /**
     * テーブルエイリアス名を設定する
     *
     * ```php
     * // SELECT * FROM tablename AS hoge_alias
     * echo $gw->as('hoge_alias');
     * ```
     */
    public function as(?string $alias): static
    {
        if ("$alias" === "") {
            return $this;
        }
        $that = $this->clone();
        $that->alias = $alias;
        return $that;
    }

    /**
     * @ignore
     */
    public function joinize(): array
    {
        $sparams = $this->getScopeParams([]);
        $addition = array_get($this->joinParams, 'addition', []);
        if ($sparams['limit'] || $sparams['groupBy'] || $sparams['having']) {
            return [
                'table'     => $this->database->select(...array_values($sparams)),
                'condition' => arrayize($addition),
            ];
        }
        else {
            // @todo getScopeParams を呼ばないと判定できない＋判定後じゃないと where したらまずい、ので2回呼んでるが無駄なのでなんとかしたい
            if ($addition) {
                $sparams = $this->where($addition)->getScopeParams([]);
            }
            return [
                'table'     => reset($sparams['column']),
                'condition' => $sparams['where'],
                'order'     => $sparams['orderBy'],
            ];
        }
    }

    /**
     * join の起点オブジェクトを返す
     *
     * jQuery の end() を想像すると分かりやすいかもしれない。
     *
     * なお、引数で戻る回数を指定できる。省略した場合は全て戻る。
     *
     * ```php
     * # この $select は t_child のビルダを指す（__get はそれ自身を返すから）
     * $select = $db->t_parent->t_child->select();
     *
     * # この $select は t_parent のビルダを指す（end() することで join 先を辿るから）
     * $select = $db->t_parent->t_child->end()->select();
     *
     * # この $select は t_child のビルダを指す（1回を指定してるから）
     * $select = $db->t_parent->t_child->t_grand->end(1)->select();
     * ```
     */
    public function end(int $back = 0): self
    {
        if (!isset($this->end)) {
            return $this;
        }

        return $this->end->end($back - 1);
    }

    /**
     * 実テーブル名を返す
     */
    public function tableName(): string
    {
        return $this->tableName;
    }

    /**
     * select の際に使用されるエイリアス名を設定・取得する
     *
     * 引数を与えると setter, 与えないと getter として動作する。
     * setter の場合は自分自身を返す。
     */
    public function alias(?string $alias = null): string|static|null
    {
        return $this->_accessor('alias', $alias);
    }

    /**
     * select の際に使用される外部キーを設定・取得する
     *
     * 引数を与えると setter, 与えないと getter として動作する。
     * setter の場合は自分自身を返す。
     *
     * @param string|null $foreign 外部キー名
     * @return $this|string 外部キー名 or 自分自身
     */
    public function foreign(?string $foreign = null): string|static|null
    {
        return $this->_accessor('foreign', $foreign);
    }

    /**
     * インデックスヒントを設定・取得する
     *
     * 引数を与えると setter, 与えないと getter として動作する。
     * setter の場合は自分自身を返す。
     */
    public function hint(?string $hint = null): string|static|null
    {
        return $this->_accessor('hint', $hint);
    }

    /**
     * @ignore
     */
    public function descriptor(): string
    {
        return $this->tableName . ($this->foreign === null ? '' : ":$this->foreign") . concat(' ', $this->alias);
    }

    /**
     * エイリアス指定されているならそれを、されていないならテーブル名を返す
     *
     * ```php
     * // t_tablename
     * echo $gw->modifier();
     * // T
     * echo $gw->as('T')->modifier();
     * ```
     */
    public function modifier(): string
    {
        return $this->alias ?: $this->tableName;
    }

    /**
     * 結合タイプや結合条件、外部キーを指定して JOIN する
     *
     * 実際は下記のようなエイリアスメソッドが定義されているのでそちらを使うことが多く、明示的に呼ぶことはほとんどない。
     * さらに{@link TableGateway クラス冒頭に記載の通り}マジックゲットやマジックコールの方が平易なシンタックスになるため、ますます出番は少ない。
     *
     * ```php
     * # joinOn は innerJoinOn のエイリアス
     * $db->t_from->joinOn($db->t_join, ['hoge = fuga']);
     * // SELECT t_from.* FROM t_from INNER JOIN t_join ON hoge = fuga
     *
     * # leftJoinOn を使うと LEFT を明示できる
     * $db->t_from->leftJoinOn($db->t_join, ['hoge = fuga']);
     * // SELECT t_from.* FROM t_from LEFT JOIN t_join ON hoge = fuga
     *
     * # joinForeign は autoJoinForeign のようなもの（外部キー定義によって INNER か AUTO かが自動で決まる）
     * $db->t_from->joinForeign($db->t_join);
     * // SELECT t_from.* FROM t_from INNER JOIN t_join ON t_from.foreign_col = t_join.foreign_col
     *
     * # leftJoinForeign を使うと LEFT を明示できる
     * $db->t_from->leftJoinForeign($db->t_join);
     * // SELECT t_from.* FROM t_from LEFT JOIN t_join ON t_from.foreign_col = t_join.foreign_col
     *
     * # joinForeignOn は autoJoinForeignOn のようなもの（外部キー定義によって INNER か AUTO かが自動で決まる）
     * $db->t_from->joinForeignOn($db->t_join, ['hoge = fuga']);
     * // SELECT t_from.* FROM t_from INNER JOIN t_join ON (t_from.foreign_col = t_join.foreign_col) AND (hoge = fuga)
     *
     * # leftJoinForeignOn を使うと LEFT を明示できる
     * $db->t_from->leftJoinForeignOn($db->t_join, ['hoge = fuga']);
     * // SELECT t_from.* FROM t_from LEFT JOIN t_join ON (t_from.foreign_col = t_join.foreign_col) AND (hoge = fuga)
     * ```
     *
     * @used-by joinOn()
     * @used-by innerJoinOn()
     * @used-by leftJoinOn()
     * @used-by rightJoinOn()
     * @used-by joinForeign()
     * @used-by autoJoinForeign()
     * @used-by innerJoinForeign()
     * @used-by leftJoinForeign()
     * @used-by rightJoinForeign()
     * @used-by joinForeignOn()
     * @used-by autoJoinForeignOn()
     * @used-by innerJoinForeignOn()
     * @used-by leftJoinForeignOn()
     * @used-by rightJoinForeignOn()
     *
     * @param string $type 結合タイプ（AUTO, INNER, ...）
     * @param TableGateway $gateway 結合するテーブルゲートウェイ
     * @param string|array $on 結合条件。 {@link where()} と同じ形式が使える
     * @param ?string $fkeyname 外部キー名称。省略時は唯一の外部キーを使用（無かったり2個以上ある場合は例外）
     */
    public function join(string $type, TableGateway $gateway, $on = [], ?string $fkeyname = null): self
    {
        // 対象
        $gateway = $gateway->clone();
        $gateway->foreign = $fkeyname;
        $gateway->joinParams = [
            'type'     => Database::JOIN_MAPPER[strtoupper($type)],
            'addition' => $on,
        ];

        // 自身
        $that = $this->clone();
        $that->joins[] = $gateway;
        $gateway->end = $that;

        return $that;
    }

    /**
     * dryrun モードに移行する
     *
     * Gateway 版の {@link Database::dryrun()} 。
     */
    public function dryrun(): static
    {
        $that = $this->context();
        $that->database = $that->database->dryrun();
        return $that;
    }

    /**
     * prepare モードに移行する
     *
     * Gateway 版の {@link Database::prepare()} 。
     */
    public function prepare(): static
    {
        $that = $this->context();
        $that->database = $that->database->prepare();
        return $that;
    }

    /**
     * 取得系クエリをプリペアする
     *
     * Gateway 版の {@link Database::prepareSelect()} 。
     */
    public function prepareSelect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []): Statement
    {
        return $this->select(...func_get_args())->prepare()->getPreparedStatement();
    }

    /**
     * スコープを定義する
     *
     * 空文字のスコープはデフォルトスコープとなり、デフォルトで適用されるようになる。
     *
     * スコープはオリジナルに対しても反映される（インスタンス間で共用される）。
     *
     * ```php
     * $gw = $db->t_article->as('A');
     * $gw->addScope('scopename', []);
     * // $db->t_article と $gw は（as してるので）別インスタンスだが、 $gw で定義したスコープはオリジナルでも使用することができる
     * $gw2 = $db->t_article->scope('scopename');
     * ```
     *
     * @param string $name スコープ名
     * @param string|array|\Closure $tableDescriptor SELECT 句
     * @param string|array $where WHERE 句
     * @param string|array $orderBy ORDER BY 句
     * @param string|array $limit LIMIT 句
     * @param string|array $groupBy GROUP BY 句
     * @param string|array $having HAVING 句
     */
    public function addScope(string $name = '', $tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [], $sets = []): static
    {
        if ($tableDescriptor instanceof \Closure) {
            $scope = $tableDescriptor;
        }
        else {
            $scope = [
                'column'  => arrayize($tableDescriptor),
                'where'   => arrayize($where),
                'orderBy' => arrayize($orderBy),
                'limit'   => arrayize($limit),
                'groupBy' => arrayize($groupBy),
                'having'  => arrayize($having),
                'set'     => arrayize($sets),
            ];
        }
        $this->scopes[$name] = $scope;
        return $this;
    }

    /**
     * スコープを合成する（スコープを利用してスコープを定義する）
     *
     * 合成スコープの引数は「元スコープの引数が足りない場合に補うように」動作する。
     * しかしそもそも優先順位がややこしいので使用は推奨しない。
     * 動的を動的のまま合成したいことはあまりないと思うので、合成時に引数を完全に指定するのがもっとも無難。
     *
     * ```php
     * # 既にスコープ a, b, c が登録されているとする
     *
     * // このようにスコープ当てるように合成できる
     * $gw->mixScope('mixedABC', 'a b c');
     *
     * // 既存スコープが動的スコープなら引数を与えることができる
     * $gw->mixScope('mixedABC', [
     *     'a' => [1 ,2 ,3], // スコープ a の引数
     *     'b' => [4, 5, 6], // スコープ a の引数
     *     'c' => [7, 8 ,9], // スコープ a の引数
     * ]);
     *
     * // いずれにせよ合成したスコープは普通のスコープと同じように使用できる
     * $gw->scope('mixedABC')->array();
     * // 実質的にこのように使用時に全部当てることと同義だが、頻出するなら使用時に複数を当てるよりも定義したほうが保守性が高くなる
     * $gw->scope('a b c')->array();
     * ```
     *
     * @param string $name スコープ名
     * @param string|array $sourceScopes 既存スコープ名
     * @param array|\Closure ...$newscopes 追加で設定するスコープ（scoping と同じ）
     */
    public function mixScope(string $name, $sourceScopes, ...$newscopes): static
    {
        if (is_string($sourceScopes)) {
            $sourceScopes = split_noempty(' ', $sourceScopes);
        }

        // 定義のチェックと配列の正規化
        $scopes = [];
        foreach ($sourceScopes as $scope => $args) {
            if (is_int($scope)) {
                $scope = $args;
                $args = [];
            }
            if (!isset($this->scopes[$scope])) {
                throw new \InvalidArgumentException("'$scope' scope is undefined.");
            }
            $scopes[$scope] = arrayize($args);
        }

        if ($newscopes) {
            $hash = spl_object_id($this) . '_' . count($this->scopes);
            $this->addScope($hash, ...$newscopes);
            $scopes[$hash] = [];
        }

        // 指定されたスコープをすべて当てるような動的スコープとして定義する
        $defargs = self::$defargs;
        $this->scopes[$name] = function (...$params) use ($scopes, $defargs) {
            $result = $defargs;
            foreach ($scopes as $scope => $args) {
                $currents = $this->getScopes();
                if ($currents[$scope] instanceof \Closure) {
                    $alength = parameter_length($currents[$scope], false, true);
                    if (is_infinite($alength)) {
                        $alength = count($params);
                    }
                    $args = array_merge($args, array_splice($params, 0, $alength - count($args), []));
                }
                $parts = $this->getScopeParts($scope, ...$args);
                $result = array_merge_recursive($result, $parts);

                // limit は配列とスカラーで扱いが異なるので「指定されていたら上書き」という挙動にする
                if ($parts['limit']) {
                    $result['limit'] = $parts['limit'];
                }
            }
            return $result;
        };
        return $this;
    }

    /**
     * 引数付きスコープを特定の値で bind してデフォルト化する
     *
     * $args のインデックスは活きる（ゼロベース）。
     * つまり、 `[1 => 'hoge', 3 => 'fuga']` という配列で bind すると第1引数と第3引数を与えたことになる。
     *
     * 複数呼ぶと蓄積される（例を参照）。
     * また、いかなる状況でも bind した値より当てる時に指定した値が優先される。
     *
     * ```php
     * // 3つの引数を取るスコープがあるとして・・・
     * $gw->addScope('abc', function ($a, $b, $c) {});
     *
     * // こうすると第3引数が不要になるので・・・
     * $gw->bindScope('abc', [2 => 'c']);
     * // このように呼べるようになる（第3引数には 'c' が渡ってくる）
     * $gw->scope('abc', 'a', 'b');
     *
     * // 効果は蓄積されるので・・・
     * $gw->bindScope('abc', [1 => 'b']);
     * // このように呼べる（第2引数には 'b', 第3引数には 'c' が渡ってくる）
     * $gw->scope('abc', 'a');
     *
     * // このように当てる時に指定した値が優先される（第2引数には 'y', 第3引数には 'z' が渡ってくる）
     * $gw->scope('abc', 'a', 'y', 'z');
     * ```
     *
     * @param string $name スコープ名
     * @param array $binding bind する引数配列
     */
    public function bindScope(string $name, array $binding): static
    {
        if (!isset($this->scopes[$name])) {
            throw new \InvalidArgumentException("'$name' scope is undefined.");
        }
        if (!$this->scopes[$name] instanceof \Closure) {
            throw new \InvalidArgumentException("'$name' scope must be closure.");
        }

        $original_name = "binded\0\0$name";
        if (!isset($this->scopes[$original_name])) {
            $this->scopes[$original_name] = $this->scopes[$name];
        }
        $scope = $this->scopes[$original_name];
        ksort($binding);
        $this->scopes[$name] = function (...$args) use ($scope, $binding) {
            return $scope(...($args + $binding));
        };

        return $this;
    }

    /**
     * スコープの追加と縛りを同時に行う
     *
     * 実際は {@link column()}, {@link where()} 等の句別メソッドを使うほうが多い。
     *
     * ```php
     * // 下記は同じ（スコープ名は自動で決まる≒使い捨てスコープ）
     * $gw->addScope('hoge', 'column', 'where', 'order', 99, 'group', 'having')->scope('hoge')->array();
     * $gw->scoping('column', 'where', 'order', 99, 'group', 'having')->array();
     * ```
     *
     * @used-by column()
     * @used-by where()
     * @used-by orderBy()
     * @used-by limit()
     * @used-by groupBy()
     * @used-by having()
     *
     * @inheritdoc addScope()
     */
    public function scoping($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []): static
    {
        $that = $this->clone();
        $hash = spl_object_id($that) . '_' . count($that->scopes);
        $that->addScope($hash, ...func_get_args());
        $that->activeScopes[$hash] = [];
        return $that;
    }

    /**
     * SELECT 句を追加する（{@uses SelectBuilder::column()} を参照）
     *
     * ```php
     * // SELECT id, name FROM tablename
     * echo $gw->column('id, name');
     * ```
     *
     * @inheritdoc SelectBuilder::column()
     */
    public function column($tableDescriptor): static
    {
        return $this->scoping(...array_values(array_replace(self::$defargs, ['column' => $tableDescriptor])));
    }

    /**
     * WHERE 句を追加する（{@uses SelectBuilder::where()} を参照）
     *
     * ```php
     * // SELECT * FROM tablename WHERE id = 99
     * echo $gw->where(['id' => 99]);
     * ```
     *
     * @inheritdoc SelectBuilder::where()
     */
    public function where($where): static
    {
        return $this->scoping(...array_values(array_replace(self::$defargs, ['where' => $where])));
    }

    /**
     * ORDER BY 句を追加する（{@uses SelectBuilder::orderBy()} を参照）
     *
     * ```php
     * // SELECT * FROM tablename ORDER BY id ASC
     * echo $gw->orderBy(['id']);
     * ```
     *
     * @inheritdoc SelectBuilder::orderBy()
     */
    public function orderBy($orderBy): static
    {
        return $this->scoping(...array_values(array_replace(self::$defargs, ['orderBy' => $orderBy])));
    }

    /**
     * LIMIT 句を追加する（{@uses SelectBuilder::limit()} を参照）
     *
     * ```php
     * // SELECT * FROM tablename LIMIT 50 OFFSET 40
     * echo $gw->limit([40 => 50]);
     * ```
     *
     * @inheritdoc SelectBuilder::limit()
     */
    public function limit($limit): static
    {
        return $this->scoping(...array_values(array_replace(self::$defargs, ['limit' => $limit])));
    }

    /**
     * GROUP BY 句を追加する（{@uses SelectBuilder::groupBy()} を参照）
     *
     * ```php
     * // SELECT * FROM tablename GROUP BY group_key
     * echo $gw->groupBy('group_key');
     * ```
     *
     * @inheritdoc SelectBuilder::groupBy()
     */
    public function groupBy($groupBy): static
    {
        return $this->scoping(...array_values(array_replace(self::$defargs, ['groupBy' => $groupBy])));
    }

    /**
     * HAVING 句を追加する（{@uses SelectBuilder::having()} を参照）
     *
     * ```php
     * // SELECT * FROM tablename HAVING id = 99
     * echo $gw->having(['id' => 99]);
     * ```
     *
     * @inheritdoc SelectBuilder::having()
     */
    public function having($having): static
    {
        return $this->scoping(...array_values(array_replace(self::$defargs, ['having' => $having])));
    }

    /**
     * SET 句を追加する
     */
    public function set(array $sets): static
    {
        return $this->scoping(...array_values(array_replace(self::$defargs, ['set' => $sets])));
    }

    /**
     * スコープで縛る
     *
     * スコープは空白区切りで複数指定できる。
     * 第2引数はクロージャによる動的スコープの引数となる。
     *
     * ```php
     * # scope1 と scope 2 を当てる
     * $gw->scope('scope1 scope2');
     *
     * # 動的スコープにパラメータを与えて当てる
     * $gw->scope('scopename', 5);
     *
     * # 配列指定で複数の動的スコープにパラメータを与えて当てる
     * $gw->scope([
     *     'scope1' => 1,          // 本来は引数を配列で与えるが、配列でない場合は配列化される（[1]と同義）
     *     'scope2' => ['a', 'b'], // 'a' が第1引数、'b' が第2引数の意味になる
     *     'scope3',               // パラメータなしスコープも同時指定できる
     * ]);
     * ```
     *
     * パラメータ有りを含むスコープをスペース区切りで同時に当てた場合は全てのスコープに引数が渡る。
     * 意図しない挙動になり得るのでその場合は配列指定で当てたほうが良い。
     *
     * @param string|array $name スコープ名
     * @param mixed $variadic_parameters スコープパラメータ
     */
    public function scope($name = '', $variadic_parameters = []): static
    {
        if (is_string($name)) {
            $name = split_noempty(' ', $name);
        }

        $that = $this->clone();
        $args = array_slice(func_get_args(), 1);
        foreach ($name as $n => $scope) {
            if (is_string($n)) {
                $args = arrayize($scope) + $args;
                $scope = $n;
            }
            if (!isset($this->scopes[$scope])) {
                throw new \InvalidArgumentException("'$scope' scope is undefined.");
            }
            $that->activeScopes[$scope] = $args;
        }
        return $that;
    }

    /**
     * 名前指定でスコープを外す
     *
     * スコープは空白区切りで複数指定できる。
     *
     * ```php
     * # 特に意味はないが、スコープを当てて外すコード
     * $gw->scope('scope1 scope2')->unscope('scope1 scope2');
     * ```
     */
    public function unscope($name = ''): static
    {
        if (is_string($name)) {
            // デフォルトスコープも考慮して split_noempty ではなく explode
            $name = explode(' ', $name);
        }

        $that = $this->clone();
        foreach ($name as $scope) {
            if (!isset($that->activeScopes[$scope])) {
                throw new \InvalidArgumentException("scope '$scope' is undefined.");
            }
            unset($that->activeScopes[$scope]);
        }
        return $that;
    }

    /**
     * デフォルトスコープを含め、縛っているスコープをすべて解除する
     */
    public function noscope(): static
    {
        $that = $this->clone();
        $that->activeScopes = [];
        return $that;
    }

    /**
     * スコープが定義されているかを返す
     *
     * 配列を与えると定義されているスコープだけの配列を返す。
     * 文字列を与えると定義されている時にそのまま返す（未定義は null を返す）。
     *
     * @param string|array $name スコープ名
     * @return array|string|null 自分自身
     */
    public function definedScope($name)
    {
        if (is_array($name)) {
            $result = [];
            foreach ($name as $n) {
                if (isset($this->scopes[$n])) {
                    $result[] = $n;
                }
            }
            return $result;
        }

        if (!isset($this->scopes[$name])) {
            return null;
        }
        return $name;
    }

    /**
     * 定義されているすべてのスコープを返す
     */
    public function getScopes(): array
    {
        return $this->scopes->getArrayCopy();
    }

    /**
     * スコープのクエリ引数を得る
     *
     * スコープは基本的に固定的だが、クロージャを与えたときのみ動的になる。
     * $variadic_parameters を与えるとそれを引数として渡す（普通に scope した時の動作）。
     * ただし、自身に既に当たっている場合はそれが使用される（引数を与えると上書きされる）。
     *
     * ```php
     * # 静的スコープ
     * $gw->addScope('scope1', 'NOW()', 'cond');
     * $gw->getScopeParts('scope1');
     * // result: 単純にパーツ配列が得られる
     * [
     *     'column'  => 'NOW()',
     *     'where'   => 'cond',
     *     'orderBy' => [],
     *     'limit'   => [],
     *     'groupBy' => [],
     *     'having'  => [],
     * ];
     *
     * # 動的スコープ
     * $gw->addScope('scope2', function ($id) {
     *     return [
     *         'column' => 'NOW()',
     *         'where'  => ['col' => $id],
     *     ];
     * });
     * $gw->getScopeParts('scope2', 123);
     * // result:
     * [
     *     'column'  => 'NOW()',
     *     'where'   => ['col' => 123],
     *     'orderBy' => [],
     *     'limit'   => [],
     *     'groupBy' => [],
     *     'having'  => [],
     * ];
     * ```
     */
    public function getScopeParts(string $name = '', $variadic_parameters = []): array
    {
        if (!isset($this->scopes[$name])) {
            throw new \InvalidArgumentException("scope '$name' is undefined.");
        }

        $scope = $this->scopes[$name];
        if ($scope instanceof \Closure) {
            $params = array_slice(func_get_args(), 1);
            if (!$params && array_key_exists($name, $this->activeScopes)) {
                $params = $this->activeScopes[$name];
            }
            $currents = $this->activeScopes;
            $scope = $scope->call($this, ...$params);
            if ($scope instanceof TableGateway) {
                $that = $scope;
                $scope = self::$defargs;
                foreach (array_diff_key($that->activeScopes, $currents) as $name => $args) {
                    $scope = array_merge_recursive($scope, $that->scopes[$name]);

                    // limit は配列とスカラーで扱いが異なるので「指定されていたら上書き」という挙動にする
                    if ($that->scopes[$name]['limit']) {
                        $scope['limit'] = $that->scopes[$name]['limit'];
                    }
                }
            }
            else {
                $scope += self::$defargs;
            }
        }
        return $scope;
    }

    /**
     * 現スコープのクエリビルダ引数を取得する
     *
     * 引数は全て省略できる。省略した場合結果はスコープのもののみとなる。
     * 指定した場合は追加でスコープを指定したように振舞う。
     *
     * @inheritdoc scoping()
     */
    public function getScopeParams($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []): array
    {
        // スコープの解決
        $that = ($tableDescriptor || $where || $orderBy || $limit || $groupBy || $having) ? $this->scoping(...func_get_args()) : $this;
        $scopes = array_map([$that, 'getScopeParts'], array_keys($that->activeScopes));

        // 修飾子の解決
        $aname = $that->descriptor();

        // JOIN の解決
        $column = array_each($that->joins, function (&$carry, TableGateway $join) use ($aname) {
            $joinname = $join->joinParams['type'] . $join->descriptor();
            $carry[$aname][$joinname] = $join;
        }, [$aname => []]);

        // スコープを順に適用
        $sargs = ['column' => $column] + self::$defargs;
        foreach ($scopes as $scope) {
            $nonames = null;
            if (is_array($scope['column']) && isset($scope['column'][''])) {
                $nonames = array_unset($scope['column'], '');
            }

            $scope['column'] = [$aname => $scope['column']];
            if ($nonames) {
                $scope['column'][''] = $nonames;
            }

            $sargs = array_merge_recursive($sargs, $scope);

            // limit は配列とスカラーで扱いが異なるので「指定されていたら上書き」という挙動にする
            if ($scope['limit']) {
                $sargs['limit'] = $scope['limit'];
            }
        }

        // 修飾子を付加して返す（$column はビルダ側で付けてくれるので不要）
        $columns = array_filter($this->database->getSchema()->getTableColumns($this->tableName), function (Column $column) {
            return !($column->getPlatformOptions()['virtual'] ?? false);
        });
        $alias = $that->modifier();
        return [
            'column'  => $sargs['column'],
            'where'   => Adhoc::modifier($alias, $columns, $sargs['where']),
            'orderBy' => Adhoc::modifier($alias, $columns, $sargs['orderBy']),
            'limit'   => $sargs['limit'],
            'groupBy' => Adhoc::modifier($alias, $columns, $sargs['groupBy']),
            'having'  => Adhoc::modifier($alias, $columns, $sargs['having']),
            'set'     => $sargs['set'],
        ];
    }

    /**
     * 更新用の {@link getScopeParams()}
     *
     * @inheritdoc getScopeParams()
     */
    public function getScopeParamsForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []): array
    {
        $activeScopes = $this->activeScopes;
        foreach ($this->getUnsafeOption('ignoreAffectScope') as $scope) {
            unset($this->activeScopes[$scope]);
        }

        try {
            $result = $this->getScopeParams(...func_get_args());
        }
        finally {
            $this->activeScopes = $activeScopes;
        }

        return $result;
    }

    /**
     * 主キー値指定の where メソッド
     *
     * 主キーの値だけを与えて {@link where()} する。
     * 可変引数で複数の主キー値を与えることができる。
     *
     * 単一主キーの場合でもそれなりに有用だし、複合主キーの場合は劇的に記述を減らすことができる。
     * さらに複合主キーの場合は主キー値が足りなくても良い。その場合、指定された分だけで where する。
     * ただし、多い場合は例外を投げる。
     *
     * ```php
     * # 単一主キー値を1つ指定（下記は等価になる）
     * $gw->pk(1);               // 配列で指定
     * $gw->where(['pid' => 1]); // where で指定
     * // SELECT * FROM t_table WHERE (pid = 1)
     *
     * # 単一主キー値を可変引数で2つ指定（下記は等価になる）
     * $gw->pk(1, 2);                 // 配列で指定
     * $gw->where(['pid' => [1, 2]]); // where で指定
     * // SELECT * FROM t_table WHERE (pid = 1 OR pid = 2)
     *
     * # 複合主キー値を1つ指定（下記は等価になる）
     * $gw->pk([1, 1]);                           // 配列で指定
     * $gw->where(['mainid' => 1, 'subid' => 1]); // where で指定
     * // SELECT * FROM t_table WHERE (mainid = 1 AND subid = 1)
     *
     * # 複合主キー値を可変引数で2つ指定（下記は等価になる）
     * $gw->pk([1, 1], [2, 2]); // 配列で指定
     * $gw->where([             // where で指定
     *     [
     *         ['mainid' => 1, 'subid' => 1],
     *         ['mainid' => 2, 'subid' => 2],
     *     ]
     * ]);
     * // SELECT * FROM t_table WHERE (mainid = 1 AND subid = 1) OR (mainid = 2 AND subid = 2)
     *
     * # 欠けた複合主キー値を可変引数で2つ指定（下記は等価になる）
     * $gw->pk([1], [2, 2]); // 配列で指定
     * $gw->where([       // where で指定
     *     [
     *         ['mainid' => 1],
     *         ['mainid' => 2, 'subid' => 2],
     *     ]
     * ]);
     * // SELECT * FROM t_table WHERE (mainid = 1) OR (mainid = 2 AND subid = 2)
     * ```
     *
     * @param mixed $variadic 主キー値
     */
    public function pk(...$variadic): static
    {
        $where = array_map([$this, '_primary'], $variadic);
        $that = $this->where([$where]);
        if (count($where) === 1) {
            $that->pkukval = reset($where);
        }
        return $that;
    }

    /**
     * 一意キー値指定の where メソッド
     *
     * 一意キーの値だけを与えて {@link where()} する。
     * 可変引数で複数の一意キー値を与えることができる。
     *
     * **主キーは一意キーとはみなされない**（主キーは pk があるので、このメソッドを使うメリットがない）。
     * 使い方は pk とほぼ同じ。ただし、主キーと違い一意キーは複数個の存在が許容されるので使い方のルールがある。
     *
     * 一意キーが1つしか存在しない場合はシンプルにそのキーを使う。
     * 一意キーが2つ以上存在する場合は型がすべて一致するものを使う。
     * いずれにせよ引数の数と一意キーのカラム数が一致しないものは使われない。
     *
     * ```php
     * # 下記は等価
     * $gw->uk([1, 2]); // 配列で指定
     * $gw->where(['unique_id1' => 1, 'unique_id2' => 2]); // where で指定
     * // SELECT * FROM t_table WHERE (unique_id1 = 1) AND (unique_id2 = 2)
     *
     * # このような使い方を想定している
     * $gw->uk('mail@address'); // 一意キーの値が 'mail@address' のレコード
     * // SELECT * FROM t_user WHERE mailaddress = 'mail@address'
     *
     * # 複数指定も可
     * $gw->uk('mail1@address', 'mail2@address');
     * // SELECT * FROM t_user WHERE mailaddress = 'mail1@address' OR mailaddress = 'mail2@address'
     * ```
     *
     * @param mixed $variadic 一意キー値
     */
    public function uk(...$variadic): static
    {
        $uvals = array_each($variadic, function (&$carry, $pvals) {
            $carry[] = array_values((array) $pvals);
        }, []);
        $uvalcount = null;
        foreach ($uvals as $uval) {
            $count = count($uval);
            $uvalcount = $uvalcount ?? $count;
            if ($count !== $uvalcount) {
                throw new \InvalidArgumentException("argument's length is not match unique index.");
            }
        }

        $table = $this->database->getSchema()->getTable($this->tableName);
        $ukeys = [];
        foreach ($table->getIndexes() as $index) {
            // 一意キーかつ非主キーで
            if ($index->isUnique() && !$index->isPrimary()) {
                $ucols = $index->getColumns();
                // 数が同じものを候補とする
                if (count($ucols) === $uvalcount) {
                    $ukeys[$index->getName()] = $ucols;
                }
            }
        }

        // 候補が1つしか無いならそれを使う（型は緩めでいい）
        if (count($ukeys) === 1) {
            $ucols = reset($ukeys);
            $where = array_each($uvals, function (&$carry, $pvals) use ($ucols) {
                $carry[] = array_combine($ucols, $pvals);
            }, []);
            $that = $this->where([$where]);
            if (count($where) === 1) {
                $that->pkukval = reset($where);
            }
            return $that;
        }

        // 2つ以上なら型が一致するものを使う
        foreach ($ukeys as $ucols) {
            // 代表選手でチェック
            foreach (array_combine($ucols, reset($uvals)) as $col => $val) {
                $type = $table->getColumn($col)->getType();
                // String はキャスト、それ以外は convertToPHPValue をかます（String の convertToPHPValue はそのまま返すのでキャストされない）
                $checker = $type instanceof \Doctrine\DBAL\Types\StringType
                    ? static function ($value) { return (string) $value; }
                    : [$type, 'convertToPHPValue'];
                if ($val !== $checker($val, $this->database->getPlatform())) {
                    continue 2;
                }
            }
            $where = array_each($uvals, function (&$carry, $pvals) use ($ucols) {
                $carry[] = array_combine($ucols, $pvals);
            }, []);
            $that = $this->where([$where]);
            if (count($where) === 1) {
                $that->pkukval = reset($where);
            }
            return $that;
        }

        // ここまでたどり着くということは一致する一意キーがない
        throw new \InvalidArgumentException("argument's length is not match unique index.");
    }

    /**
     * 駆動表を省略できる {@uses Database::select()}
     *
     * @used-by array()
     * @used-by arrayOrThrow()
     * @used-by arrayInShare()
     * @used-by arrayForUpdate()
     * @used-by arrayForAffect()
     * @used-by assoc()
     * @used-by assocOrThrow()
     * @used-by assocInShare()
     * @used-by assocForUpdate()
     * @used-by assocForAffect()
     * @used-by lists()
     * @used-by listsOrThrow()
     * @used-by listsInShare()
     * @used-by listsForUpdate()
     * @used-by listsForAffect()
     * @used-by pairs()
     * @used-by pairsOrThrow()
     * @used-by pairsInShare()
     * @used-by pairsForUpdate()
     * @used-by pairsForAffect()
     * @used-by tuple()
     * @used-by tupleOrThrow()
     * @used-by tupleInShare()
     * @used-by tupleForUpdate()
     * @used-by tupleForAffect()
     * @used-by value()
     * @used-by valueOrThrow()
     * @used-by valueInShare()
     * @used-by valueForUpdate()
     * @used-by valueForAffect()
     *
     * @inheritdoc Database::select()
     */
    public function select($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []): SelectBuilder
    {
        $sp = $this->getScopeParams($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having);
        $return = $this->database->select(...array_values($sp));

        $return->hint($this->hint);
        if ($this->original->alias) {
            $return->cast(null);
        }

        return $return;
    }

    /**
     * 主キーが指定されたクエリビルダを返す
     *
     * 引数がかなりややこしいことになっている。複合主キーが id1, id2, id3 というテーブルだとすると
     *
     * - `find([10, 20, 30])` のように呼び出した（配列指定主キー）
     * - `find(10, 20, 30)` のように呼び出した（可変長引数主キー）
     * - 上記は2つとも id1 = 10, id2 = 20, id3 = 30 とみなされる
     *
     * - `find([10, 20, 30], ['column1', 'column2'])` のように呼び出した（配列指定主キー＋配列指定カラム）
     * - `find([10, 20, 30], 'column1', 'column2')` のように呼び出した（配列指定主キー＋可変長引数カラム）
     * - `find(10, 20, 30, ['column1', 'column2'])` のように呼び出した（可変長引数主キー＋配列指定カラム）
     * - 上記はすべて id1 = 10, id2 = 20, id3 = 30 とみなされるとともに、SELECT 句に column1, column2 が含まれる
     *
     * この仕様は「主キーを配列で持っている」「主キーを個別に持っている」という2つの状況に簡単に対応するため。
     * 前者の状況はほとんど無いため、実質的な呼び出し方は `(10, 20, 30)` 方式で十分。
     *
     * ```php
     * # レコードを1行取得する（単一主キーで全カラムを取得する最もシンプルな例）
     * $row = $gw->find(1);
     * // SELECT * FROM t_table WHERE primary_id = 1
     *
     * # レコードを1行取得する（複合主キーでカラムを指定して取得するシンプルでない例）
     * $row = $gw->find([1, 2], ['column1', 'column2']);
     * // SELECT column1, column2 FROM t_table WHERE (primary_id1 = 1) AND (primary_id2 = 2)
     * ```
     *
     * @used-by find()
     * @used-by findOrThrow()
     * @used-by findInShare()
     * @used-by findForUpdate()
     * @used-by findForAffect()
     *
     * @param mixed $variadic_primary 主キー値あるいは配列
     * @param mixed $tableDescriptor 取得カラム
     */
    public function selectFind($variadic_primary, $tableDescriptor = []): SelectBuilder
    {
        $arguments = func_get_args();
        if (is_array($arguments[0])) {
            $primary = $arguments[0];
            $columns = array_slice($arguments, 1) ?: [];
        }
        else {
            $primary = $arguments;
            $columns = is_array(end($primary)) ? array_pop($primary) : [];
        }
        return $this->pk($primary)->column($columns)->select();
    }

    /**
     * 駆動表を省略できる（{@uses Database::selectAggregate()} を参照）
     *
     * @used-by selectExists()
     * @used-by selectNotExists()
     * @used-by selectCount()
     * @used-by selectMin()
     * @used-by selectMax()
     * @used-by selectSum()
     * @used-by selectAvg()
     *
     * @inheritdoc Database::selectAggregate()
     */
    public function selectAggregate($aggregation, $column, $where = [], $groupBy = [], $having = []): SelectBuilder
    {
        return $this->select($column, $where, [], [], $groupBy, $having)->aggregate($aggregation);
    }

    /**
     * 駆動表を省略できる {@uses Database::subselect()}
     *
     * @used-by subselectArray()
     * @used-by subselectAssoc()
     * @used-by subselectLists()
     * @used-by subselectPairs()
     * @used-by subselectTuple()
     * @used-by subselectValue()
     *
     * @used-by subArray()
     * @used-by subAssoc()
     * @used-by subLists()
     * @used-by subPairs()
     * @used-by subTuple()
     * @used-by subValue()
     *
     * @inheritdoc Database::subselect()
     */
    public function subselect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []): SelectBuilder
    {
        $sp = $this->getScopeParams($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having);
        $return = $this->database->subselect(...array_values($sp));
        $return->hint($this->hint);
        return $return;
    }

    /**
     * 駆動表を省略できる {@uses Database::subquery()}
     *
     * @used-by subexists()
     * @used-by notSubexists()
     *
     * @inheritdoc Database::subquery()
     */
    public function subquery($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []): SelectBuilder
    {
        $sp = $this->getScopeParams($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having);
        $return = $this->database->subquery(...array_values($sp));
        $return->hint($this->hint);
        return $return;
    }

    /**
     * 駆動表を省略できる {@uses Database::subaggregate()}
     *
     * @used-by subcount()
     * @used-by submin()
     * @used-by submax()
     * @used-by subsum()
     * @used-by subavg()
     *
     * @inheritdoc Database::subaggregate()
     */
    public function subaggregate($aggregate, $column, $where = []): SelectBuilder
    {
        $sp = $this->getScopeParams($column, $where);
        $return = $this->database->subaggregate($aggregate, ...array_values($sp));
        $return->hint($this->hint);
        return $return;
    }

    /**
     * 駆動表を省略できる {@uses Database::aggregate()}
     *
     * @used-by exists()
     * @used-by count()
     * @used-by min()
     * @used-by max()
     * @used-by sum()
     * @used-by avg()
     *
     * @inheritdoc Database::aggregate()
     */
    public function aggregate($aggregation, $column, $where = [], $groupBy = [], $having = [])
    {
        $sp = $this->getScopeParams($column, $where, [], [], $groupBy, $having);
        return $this->database->aggregate($aggregation, $sp['column'], $sp['where'], $sp['groupBy'], $sp['having']);
    }

    /**
     * 前後のレコードを返す（{@uses SelectBuilder::neighbor()} を参照）
     *
     * @inheritdoc SelectBuilder::neighbor()
     */
    public function neighbor(array $predicates = [], int $limit = 1): array
    {
        if ($this->pkukval && !$predicates) {
            $predicates = $this->pkukval;
        }
        $select = $this->select();
        if ($this->original->alias) {
            $select->cast(null);
        }
        return $select->neighbor($predicates, $limit);
    }

    /**
     * レコード件数を返す
     *
     * 件数取得は下記の2種類の方法が存在する。
     *
     * 1. `count($gw);`
     * 2. `$gw->count('*');`
     *
     * 1 は php 標準の count() 関数フックであり、**レコードをフェッチしてその件数を返す。**
     * 2 は メソッドコールであり、**COUNT クエリを発行する。**
     * 当たっている WHERE が同じであれば結果も同じになるが、その内部処理は大きく異なる。
     *
     * 内部的にメソッド呼び出しと count 呼び出しを判断する術がないので引数で分岐している。
     *
     * @param string|array $column SELECT 句
     * @param string|array $where WHERE 句
     * @param string|array $groupBy GROUP BY 句
     * @param string|array $having HAVING 句
     */
    public function count($column = [], $where = [], $groupBy = [], $having = []): int
    {
        // 引数が来ているならメソッド（Countable::count は引数が来ない）
        if (func_num_args() > 0) {
            return $this->countAggregate($column, $where, $groupBy, $having);
        }

        // 上記以外は Countable::count
        return $this->countIterator();
    }

    /**
     * new Paginator へのプロキシメソッド
     *
     * 引数が与えられている場合は {@link Paginator::paginate()} も同時に行う。
     *
     * @inheritdoc SelectBuilder::paginate()
     */
    public function paginate(?int $currentpage = null, ?int $countperpage = null): Paginator
    {
        return $this->select()->paginate(...func_get_args());
    }

    /**
     * new Sequencer へのプロキシメソッド
     *
     * 引数が与えられている場合は {@link Sequencer::sequence()} も同時に行う。
     *
     * @inheritdoc SelectBuilder::sequence()
     */
    public function sequence(?array $condition = null, ?int $count = null, ?bool $orderbyasc = true): Sequencer
    {
        return $this->select()->sequence(...func_get_args());
    }

    /**
     * 分割して sequence してレコードジェネレータを返す
     *
     * Gateway 版の {@link SelectBuilder::chunk()} 。
     *
     * @inheritdoc SelectBuilder::chunk()
     */
    public function chunk(int $count, ?string $column = null): \Generator
    {
        return $this->select()->chunk(...func_get_args());
    }

    /**
     * 空レコードを返す
     *
     * Gateway 版の {@link Database::getEmptyRecord()} 。
     *
     * @inheritdoc Database::getEmptyRecord()
     */
    public function getEmptyRecord($default = [])
    {
        return $this->database->getEmptyRecord($this->original->alias ?: $this->tableName, $default);
    }

    /**
     * レコード情報をかき集める
     *
     * Gateway 版の {@link Database::gather()} 。
     *
     * @inheritdoc Database::gather()
     */
    public function gather($wheres = [], $other_wheres = [], $parentive = false): array
    {
        $sp = $this->getScopeParams([], $wheres);
        return $this->database->gather($this->tableName, $sp['where'], $other_wheres, $parentive);
    }

    /**
     * レコード配列の差分をとる
     *
     * Gateway 版の {@link Database::differ()} 。
     *
     * @inheritdoc Database::differ()
     */
    public function differ(array $array, $wheres = []): array
    {
        $sp = $this->getScopeParams([], $wheres);
        return $this->database->differ($array, $this->tableName, $sp['where']);
    }

    /**
     * 駆動表を省略できる <@uses Database::insertSelect()>
     *
     * @used-by insertSelectIgnore()
     *
     * @inheritdoc Database::insertSelect()
     */
    public function insertSelect($sql, $columns = [], iterable $params = [])
    {
        $this->resetResult();
        return $this->database->insertSelect($this, ...func_get_args());
    }

    /**
     * 駆動表を省略できる <@uses Database::insertArray()>
     *
     * @used-by insertArrayIgnore()
     * @used-by insertArrayOrThrow()
     *
     * @inheritdoc Database::insertArray()
     */
    public function insertArray($data)
    {
        $this->resetResult();
        return $this->database->insertArray($this, ...func_get_args());
    }

    /**
     * 駆動表を省略できる <@uses Database::updateArray()>
     *
     * @used-by updateArrayIgnore()
     * @used-by updateArrayAndBefore()
     *
     * @inheritdoc Database::updateArray()
     */
    public function updateArray($data, $where = [])
    {
        $this->resetResult();
        return $this->database->updateArray($this, ...func_get_args());
    }

    /**
     * 駆動表を省略できる <@uses Database::deleteArray()>
     *
     * @used-by deleteArrayIgnore()
     * @used-by deleteArrayAndBefore()
     *
     * @inheritdoc Database::deleteArray()
     */
    public function deleteArray($where = [])
    {
        $this->resetResult();
        return $this->database->deleteArray($this, ...func_get_args());
    }

    /**
     * 駆動表を省略できる <@uses Database::modifyArray()>
     *
     * @used-by modifyArrayIgnore()
     * @used-by modifyArrayAndBefore()
     *
     * @inheritdoc Database::modifyArray()
     */
    public function modifyArray($insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        $this->resetResult();
        return $this->database->modifyArray($this, ...func_get_args());
    }

    /**
     * 駆動表を省略できる <@uses Database::changeArray()>
     *
     * @used-by changeArrayIgnore()
     *
     * @inheritdoc Database::changeArray()
     */
    public function changeArray($dataarray, $where, $uniquekey = 'PRIMARY', $returning = [])
    {
        $this->resetResult();
        return $this->database->changeArray($this->tableName, ...func_get_args());
    }

    /**
     * 駆動表を省略できる <@uses Database::affectArray()>
     *
     * @used-by affectArrayIgnore()
     *
     * @inheritdoc Database::affectArray()
     */
    public function affectArray($dataarray)
    {
        $this->resetResult();
        return $this->database->affectArray($this->tableName, ...func_get_args());
    }

    /**
     * 駆動表を省略できる <@uses Database::save()>
     *
     * @used-by saveIgnore()
     *
     * @inheritdoc Database::save()
     */
    public function save($data)
    {
        $this->resetResult();
        return $this->database->save($this->tableName, ...func_get_args());
    }

    /**
     * 駆動表を省略できる <@uses Database::insert()>
     *
     * @used-by insertOrThrow()
     * @used-by insertAndPrimary()
     * @used-by insertIgnore()
     *
     * @inheritdoc Database::insert()
     */
    public function insert($data)
    {
        $this->resetResult();
        return $this->database->insert($this, ...func_get_args());
    }

    /**
     * 駆動表を省略できる <@uses Database::update()>
     *
     * @used-by updateOrThrow()
     * @used-by updateAndPrimary()
     * @used-by updateAndBefore()
     * @used-by updateIgnore()
     *
     * @inheritdoc Database::update()
     */
    public function update($data, $where = [])
    {
        $this->resetResult();
        return $this->database->update($this, ...func_get_args());
    }

    /**
     * 駆動表を省略できる <@uses Database::delete()>
     *
     * @used-by deleteOrThrow()
     * @used-by deleteAndPrimary()
     * @used-by deleteAndBefore()
     * @used-by deleteIgnore()
     *
     * @inheritdoc Database::delete()
     */
    public function delete($where = [])
    {
        $this->resetResult();
        return $this->database->delete($this, ...func_get_args());
    }

    /**
     * 駆動表を省略できる <@uses Database::invalid()>
     *
     * @used-by invalidOrThrow()
     * @used-by invalidAndPrimary()
     * @used-by invalidAndBefore()
     * @used-by invalidIgnore()
     *
     * @inheritdoc Database::invalid()
     */
    public function invalid($where = [], ?array $invalid_columns = null)
    {
        $this->resetResult();
        return $this->database->invalid($this, ...func_get_args());
    }

    /**
     * 駆動表を省略できる <@uses Database::revise()>
     *
     * @used-by reviseOrThrow()
     * @used-by reviseAndPrimary()
     * @used-by reviseAndBefore()
     * @used-by reviseIgnore()
     *
     * @inheritdoc Database::revise()
     */
    public function revise($data, $where = [])
    {
        $this->resetResult();
        return $this->database->revise($this, ...func_get_args());
    }

    /**
     * 駆動表を省略できる <@uses Database::upgrade()>
     *
     * @used-by upgradeOrThrow()
     * @used-by upgradeAndPrimary()
     * @used-by upgradeAndBefore()
     * @used-by upgradeIgnore()
     *
     * @inheritdoc Database::upgrade()
     */
    public function upgrade($data, $where = [])
    {
        $this->resetResult();
        return $this->database->upgrade($this, ...func_get_args());
    }

    /**
     * 駆動表を省略できる <@uses Database::remove()>
     *
     * @used-by removeOrThrow()
     * @used-by removeAndPrimary()
     * @used-by removeAndBefore()
     * @used-by removeIgnore()
     *
     * @inheritdoc Database::remove()
     */
    public function remove($where = [])
    {
        $this->resetResult();
        return $this->database->remove($this, ...func_get_args());
    }

    /**
     * 駆動表を省略できる <@uses Database::destroy()>
     *
     * @used-by destroyOrThrow()
     * @used-by destroyAndPrimary()
     * @used-by destroyAndBefore()
     * @used-by destroyIgnore()
     *
     * @inheritdoc Database::destroy()
     */
    public function destroy($where = [])
    {
        $this->resetResult();
        return $this->database->destroy($this, ...func_get_args());
    }

    /**
     * 駆動表を省略できる <@uses Database::reduce()>
     *
     * @used-by reduceOrThrow()
     * @used-by reduceAndBefore()
     *
     * @inheritdoc Database::reduce()
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $where = [])
    {
        $this->resetResult();
        return $this->database->reduce($this, ...func_get_args());
    }

    /**
     * 駆動表を省略できる <@uses Database::upsert()>
     *
     * @used-by upsertOrThrow()
     * @used-by upsertAndPrimary()
     * @used-by upsertAndBefore()
     *
     * @inheritdoc Database::upsert()
     */
    public function upsert($insertData, $updateData = [])
    {
        $this->resetResult();
        return $this->database->upsert($this, ...func_get_args());
    }

    /**
     * 駆動表を省略できる <@uses Database::modify()>
     *
     * @used-by modifyOrThrow()
     * @used-by modifyAndPrimary()
     * @used-by modifyIgnore()
     * @used-by modifyAndBefore()
     *
     * @inheritdoc Database::modify()
     */
    public function modify($insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        $this->resetResult();
        return $this->database->modify($this, ...func_get_args());
    }

    /**
     * 駆動表を省略できる <@uses Database::replace()>
     *
     * @used-by replaceOrThrow()
     * @used-by replaceAndPrimary()
     * @used-by replaceAndBefore()
     *
     * @inheritdoc Database::replace()
     */
    public function replace($insertData)
    {
        $this->resetResult();
        return $this->database->replace($this, ...func_get_args());
    }

    /**
     * 駆動表を省略できる <@uses Database::truncate()>
     *
     * @inheritdoc Database::truncate()
     */
    public function truncate()
    {
        $this->resetResult();
        return $this->database->truncate($this);
    }

    /**
     * 駆動表を省略できる <@uses Database::eliminate()>
     *
     * @inheritdoc Database::eliminate()
     */
    public function eliminate()
    {
        $this->resetResult();
        return $this->database->eliminate($this);
    }

    /**
     * 最後に挿入した ID を返す
     *
     * Gateway 版の {@link Database::getLastInsertId()} 。
     *
     * @inheritdoc Database::getLastInsertId()
     */
    public function getLastInsertId(?string $columnname = null): null|int|string
    {
        return $this->database->getLastInsertId($this->tableName, $columnname);
    }

    /**
     * 自動採番列をリセットする
     *
     * Gateway 版の {@link Database::resetAutoIncrement()} 。
     *
     * @inheritdoc Database::resetAutoIncrement()
     */
    public function resetAutoIncrement(?int $seq = 1)
    {
        return $this->database->resetAutoIncrement($this->tableName, $seq);
    }
}
