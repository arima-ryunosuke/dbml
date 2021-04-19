<?php

namespace ryunosuke\dbml\Gateway;

use ryunosuke\dbml\Database;
use ryunosuke\dbml\Entity\Entityable;
use ryunosuke\dbml\Generator\Yielder;
use ryunosuke\dbml\Mixin\IteratorTrait;
use ryunosuke\dbml\Mixin\OptionTrait;
use ryunosuke\dbml\Query\Expression\TableDescriptor;
use ryunosuke\dbml\Query\Pagination\Paginator;
use ryunosuke\dbml\Query\Pagination\Sequencer;
use ryunosuke\dbml\Query\QueryBuilder;
use ryunosuke\dbml\Query\Statement;
use ryunosuke\dbml\Utility\Adhoc;
use function ryunosuke\dbml\array_each;
use function ryunosuke\dbml\array_get;
use function ryunosuke\dbml\arrayize;
use function ryunosuke\dbml\concat;
use function ryunosuke\dbml\parameter_length;
use function ryunosuke\dbml\reflect_callable;
use function ryunosuke\dbml\split_noempty;
use function ryunosuke\dbml\throws;
use function ryunosuke\dbml\try_finally;

// @formatter:off
/**
 * ゲートウェイクラス
 *
 * Database の各種メソッドで「$table に自身に指定した」かのように動作する。
 * Database や QueryBuilder に実装されているメソッドは大抵利用できるが、コード補完に出ないメソッドはなるべく使用しないほうがよい。
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
 * @method string                 getDefaultIteration() {デフォルトイテレーションモードを返す}
 * @method $this                  setDefaultIteration($iterationMode) {
 *     デフォルトイテレーションモードを設定する
 *
 *     デフォルトのデフォルトは "array" なので、何も考えずに foreach で回すと array 相当の動作になる。
 *
 *     @param string $iterationMode {@link Database::METHOD_ARRAY} などの定数
 * }
 * @method string                 getDefaultJoinMethod() {デフォルト JOIN モードを返す}
 * @method $this                  setDefaultJoinMethod($string) {
 *     デフォルト JOIN モードを設定する
 *
 *     デフォルトのデフォルトは "AUTO" なので、何も考えずに JOIN すると最も良い感じに JOIN される。
 *
 *     @param string $string {@link Database::JOIN_MAPPER} のいずれかのキー
 * }
 *
 * @method array                  getIgnoreAffectScope() {更新時に無視するスコープ名を返す}
 * @method $this                  setIgnoreAffectScope(array $ignoreAffectScope) {更新時に無視するスコープ名を設定する}
 *
 * @method $this                  joinOn(TableGateway $table, $on) {
 *     結合方法が INNER で結合条件指定の <@uses TableGateway::join()>
 * }
 * @method $this                  innerJoinOn(TableGateway $table, $on) {
 *     結合方法が INNER で結合条件指定の <@uses TableGateway::join()>
 * }
 * @method $this                  leftJoinOn(TableGateway $table, $on) {
 *     結合方法が LEFT で結合条件指定の <@uses TableGateway::join()>
 * }
 * @method $this                  rightJoinOn(TableGateway $table, $on) {
 *     結合方法が RIGHT で結合条件指定の <@uses TableGateway::join()>
 * }
 *
 * @method $this                  joinForeign(TableGateway $table, $fkeyname = null) {
 *     結合方法が AUTO で外部キー指定の <@uses TableGateway::join()>
 * }
 * @method $this                  innerJoinForeign(TableGateway $table, $fkeyname = null) {
 *     結合方法が INNER で外部キー指定の <@uses TableGateway::join()>
 * }
 * @method $this                  leftJoinForeign(TableGateway $table, $fkeyname = null) {
 *     結合方法が LEFT で外部キー指定の <@uses TableGateway::join()>
 * }
 * @method $this                  rightJoinForeign(TableGateway $table, $fkeyname = null) {
 *     結合方法が RIGHT で外部キー指定の <@uses TableGateway::join()>
 * }
 *
 * @method $this                  joinForeignOn(TableGateway $table, $on, $fkeyname = null) {
 *     結合方法が AUTO で結合条件・外部キー指定の <@uses TableGateway::join()>
 * }
 * @method $this                  innerJoinForeignOn(TableGateway $table, $on, $fkeyname = null) {
 *     結合方法が INNER で結合条件・外部キー指定の <@uses TableGateway::join()>
 * }
 * @method $this                  leftJoinForeignOn(TableGateway $table, $on, $fkeyname = null) {
 *     結合方法が LEFT で結合条件・外部キー指定の <@uses TableGateway::join()>
 * }
 * @method $this                  rightJoinForeignOn(TableGateway $table, $on, $fkeyname = null) {
 *     結合方法が RIGHT で結合条件・外部キー指定の <@uses TableGateway::join()>
 * }
 *
 * @method $this                  column($tableDescriptor) {
 *     SELECT 句を追加する（<@uses QueryBuilder::column()> を参照）
 *
 *     ```php
 *     // SELECT id, name FROM tablename
 *     echo $gw->column('id, name');
 *     ```
 *
 *     @param string|array $tableDescriptor SELECT 句
 * }
 * @method $this                  where($where) {
 *     WHERE 句を追加する（<@uses QueryBuilder::where()> を参照）
 *
 *     ```php
 *     // SELECT * FROM tablename WHERE id = 99
 *     echo $gw->where(['id' => 99]);
 *     ```
 *
 *     @param string|array $where WHERE 句
 * }
 * @method $this                  orderBy($orderBy) {
 *     ORDER BY 句を追加する（<@uses QueryBuilder::orderBy()> を参照）
 *
 *     ```php
 *     // SELECT * FROM tablename ORDER BY id ASC
 *     echo $gw->orderBy(['id']);
 *     ```
 *
 *     @param string|array $orderBy ORDER BY 句
 * }
 * @method $this                  limit($limit) {
 *     LIMIT 句を追加する（<@uses QueryBuilder::limit()> を参照）
 *
 *     ```php
 *     // SELECT * FROM tablename LIMIT 50 OFFSET 40
 *     echo $gw->limit([40 => 50]);
 *     ```
 *
 *     @param int|array $limit LIMIT 句
 * }
 * @method $this                  groupBy($groupBy) {
 *     GROUP BY 句を追加する（<@uses QueryBuilder::groupBy()> を参照）
 *
 *     ```php
 *     // SELECT * FROM tablename GROUP BY group_key
 *     echo $gw->groupBy('group_key');
 *     ```
 *
 *     @param string|array $groupBy GROUP BY 句
 * }
 * @method $this                  having($having) {
 *     HAVING 句を追加する（<@uses QueryBuilder::having()> を参照）
 *
 *     ```php
 *     // SELECT * FROM tablename HAVING id = 99
 *     echo $gw->having(['id' => 99]);
 *     ```
 *
 *     @param string|array $having HAVING 句
 * }
 *
 * @method array|Entityable[]     array($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     レコード群を配列で返す（<@uses Database::selectArray()> を参照）
 * }
 * @method array|Entityable[]     assoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     レコード群を連想配列で返す（<@uses Database::selectAssoc()> を参照）
 * }
 * @method array                  lists($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     レコード群を[value]で返す（<@uses Database::selectLists()> を参照）
 * }
 * @method array                  pairs($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     レコード群を[key => value]で返す（<@uses Database::selectPairs()> を参照）
 * }
 * @method array|Entityable|false tuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     レコードを配列で返す（<@uses Database::selectTuple()> を参照）
 * }
 * @method mixed                  value($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     カラム値をスカラーで返す（<@uses Database::selectValue()> を参照）
 * }
 * @method array|Entityable|false find($variadic_primary, $tableDescriptor = []) {
 *     主キー指定でレコードを取得する
 *
 *     引数がかなりややこしいことになっている。複合主キーが id1, id2, id3 というテーブルだとすると
 *
 *     - `find([10, 20, 30])` のように呼び出した（配列指定主キー）
 *     - `find(10, 20, 30)` のように呼び出した（可変長引数主キー）
 *     - 上記は2つとも id1 = 10, id2 = 20, id3 = 30 とみなされる
 *
 *     - `find([10, 20, 30], ['column1', 'column2'])` のように呼び出した（配列指定主キー＋配列指定カラム）
 *     - `find([10, 20, 30], 'column1', 'column2')` のように呼び出した（配列指定主キー＋可変長引数カラム）
 *     - `find(10, 20, 30, ['column1', 'column2'])` のように呼び出した（可変長引数主キー＋配列指定カラム）
 *     - 上記はすべて id1 = 10, id2 = 20, id3 = 30 とみなされるとともに、SELECT 句に column1, column2 が含まれる
 *
 *     この仕様は「主キーを配列で持っている」「主キーを個別に持っている」という2つの状況に簡単に対応するため。
 *     前者の状況はほとんど無いため、実質的な呼び出し方は `(10, 20, 30)` 方式で十分。
 *
 *     ```php
 *     # レコードを1行取得する（単一主キーで全カラムを取得する最もシンプルな例）
 *     $row = $gw->find(1);
 *     // SELECT * FROM t_table WHERE primary_id = 1
 *
 *     # レコードを1行取得する（複合主キーでカラムを指定して取得するシンプルでない例）
 *     $row = $gw->find([1, 2], ['column1', 'column2']);
 *     // SELECT column1, column2 FROM t_table WHERE (primary_id1 = 1) AND (primary_id2 = 2)
 *     ```
 *
 *     @param mixed $variadic_primary 主キー値あるいは配列
 *     @param mixed $tableDescriptor 取得カラム
 * }
 *
 * @method array|Entityable[]     arrayInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses TableGateway::array()> の共有ロック版
 * }
 * @method array|Entityable[]     assocInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses TableGateway::assoc()> の共有ロック版
 * }
 * @method array                  listsInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses TableGateway::lists()> の共有ロック版
 * }
 * @method array                  pairsInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses TableGateway::pairs()> の共有ロック版
 * }
 * @method array|Entityable|false tupleInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses TableGateway::tuple()> の共有ロック版
 * }
 * @method mixed                  valueInShare($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses TableGateway::value()> の共有ロック版
 * }
 * @method array|Entityable|false findInShare($variadic_primary, $tableDescriptor = []) {
 *     <@uses TableGateway::find()> の共有ロック版
 * }
 *
 * @method array|Entityable[]     arrayForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses TableGateway::array()> の排他ロック版
 * }
 * @method array|Entityable[]     assocForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses TableGateway::assoc()> の排他ロック版
 * }
 * @method array                  listsForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses TableGateway::lists()> の排他ロック版
 * }
 * @method array                  pairsForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses TableGateway::pairs()> の排他ロック版
 * }
 * @method array|Entityable|false tupleForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses TableGateway::tuple()> の排他ロック版
 * }
 * @method mixed                  valueForUpdate($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses TableGateway::value()> の排他ロック版
 * }
 * @method array|Entityable|false findForUpdate($variadic_primary, $tableDescriptor = []) {
 *     <@uses TableGateway::find()> の排他ロック版
 * }
 *
 * @method array|Entityable[]     arrayOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses TableGateway::array()> の例外送出版
 * }
 * @method array|Entityable[]     assocOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses TableGateway::assoc()> の例外送出版
 * }
 * @method array                  listsOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses TableGateway::lists()> の例外送出版
 * }
 * @method array                  pairsOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses TableGateway::pairs()> の例外送出版
 * }
 * @method array|Entityable       tupleOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses TableGateway::tuple()> の例外送出版
 * }
 * @method mixed                  valueOrThrow($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses TableGateway::value()> の例外送出版
 * }
 * @method array|Entityable       findOrThrow($variadic_primary, $tableDescriptor = []) {
 *     <@uses TableGateway::find()> の例外送出版
 * }
 *
 * @method Yielder                yieldArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     レコード群を配列で少しずつ返す（<@uses Database::yieldArray()> を参照）
 * }
 * @method Yielder                yieldAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     レコード群を連想配列で少しずつ返す（<@uses Database::yieldAssoc()> を参照）
 * }
 * @method Yielder                yieldLists($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     レコード群を[value]で少しずつ返す（<@uses Database::yieldLists()> を参照）
 * }
 * @method Yielder                yieldPairs($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     レコード群を[key => value]で少しずつ返す（<@uses Database::yieldPairs()> を参照）
 * }
 *
 * @method int                exportArray($config = [], $tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     レコード群を php 配列でエクスポートする（<@uses Database::exportArray()> を参照）
 * }
 * @method int                exportCsv($config = [], $tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     レコード群を CSV でエクスポートする（<@uses Database::exportCsv()> を参照）
 * }
 * @method int                exportJson($config = [], $tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     レコード群を JSON でエクスポートする（<@uses Database::exportJson()> を参照）
 * }
 *
 * @method QueryBuilder           subselectArray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     子供レコード（array）を表すサブビルダを返す（<@uses Database::subselect()> を参照）
 * }
 * @method QueryBuilder           subselectAssoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     子供レコード（assoc）を表すサブビルダを返す（<@uses Database::subselect()> を参照）
 * }
 * @method QueryBuilder           subselectLists($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     子供レコード（lists）を表すサブビルダを返す（<@uses Database::subselect()> を参照）
 * }
 * @method QueryBuilder           subselectPairs($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     子供レコード（pairs）を表すサブビルダを返す（<@uses Database::subselect()> を参照）
 * }
 * @method QueryBuilder           subselectTuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     子供レコード（tuple）を表すサブビルダを返す（<@uses Database::subselect()> を参照）
 * }
 * @method QueryBuilder           subselectValue($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     子供レコード（value）を表すサブビルダを返す（<@uses Database::subselect()> を参照）
 * }
 *
 * @method QueryBuilder subarray($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {{@link subselectArray()} のエイリアス@inheritdoc subselectArray()}
 * @method QueryBuilder subassoc($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {{@link subselectAssoc()} のエイリアス@inheritdoc subselectAssoc()}
 * @method QueryBuilder sublists($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {{@link subselectLists()} のエイリアス@inheritdoc subselectLists()}
 * @method QueryBuilder subpairs($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {{@link subselectPairs()} のエイリアス@inheritdoc subselectPairs()}
 * @method QueryBuilder subtuple($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {{@link subselectTuple()} のエイリアス@inheritdoc subselectTuple()}
 * @method QueryBuilder subvalue($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {{@link subselectValue()} のエイリアス@inheritdoc subselectValue()}
 *
 * @method QueryBuilder           select($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     駆動表を省略できる <@uses Database::select()>
 * }
 * @method QueryBuilder           subquery($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     駆動表を省略できる <@uses Database::subquery()>
 * }
 * @method QueryBuilder           subexists($tableDescriptor = [], $where = []) {
 *     駆動表を省略できる <@uses Database::subexists()>
 * }
 * @method QueryBuilder           notSubexists($tableDescriptor = [], $where = []) {
 *     駆動表を省略できる <@uses Database::notSubexists()>
 * }
 * @method QueryBuilder           subcount($column = [], $where = []) {
 *     駆動表を省略できる <@uses Database::subcount()>
 * }
 * @method QueryBuilder           submin($column = [], $where = []) {
 *     駆動表を省略できる <@uses Database::submin()>
 * }
 * @method QueryBuilder           submax($column = [], $where = []) {
 *     駆動表を省略できる <@uses Database::submax()>
 * }
 * @method QueryBuilder           subsum($column = [], $where = []) {
 *     駆動表を省略できる <@uses Database::subsum()>
 * }
 * @method QueryBuilder           subavg($column = [], $where = []) {
 *     駆動表を省略できる <@uses Database::subavg()>
 * }
 *
 * @method array|Entityable[]     neighbor($predicates = [], $limit = 1) {
 *     前後のレコードを返す（<@uses QueryBuilder::neighbor()> を参照）
 * }
 * @method int|float              exists($where = [], $for_update = false) {
 *     駆動表を省略できる <@uses Database::exists()>
 * }
 * @method int|float              min($column, $where = [], $groupBy = [], $having = []) {
 *     駆動表を省略できる <@uses Database::min()>
 * }
 * @method int|float              max($column, $where = [], $groupBy = [], $having = []) {
 *     駆動表を省略できる <@uses Database::max()>
 * }
 * @method int|float              sum($column, $where = [], $groupBy = [], $having = []) {
 *     駆動表を省略できる <@uses Database::sum()>
 * }
 * @method int|float              avg($column, $where = [], $groupBy = [], $having = []) {
 *     駆動表を省略できる <@uses Database::avg()>
 * }
 *
 * @method QueryBuilder           selectExists($where = [], $for_update = false) {
 *     駆動表を省略できる <@uses Database::selectExists()>
 * }
 * @method QueryBuilder           selectNotExists($where = [], $for_update = false) {
 *     駆動表を省略できる <@uses Database::selectNotExists()>
 * }
 * @method QueryBuilder           selectCount($column = [], $where = [], $groupBy = [], $having = []) {
 *     駆動表を省略できる <@uses Database::selectCount()>
 * }
 * @method QueryBuilder           selectMin($column, $where = [], $groupBy = [], $having = []) {
 *     駆動表を省略できる <@uses Database::selectMin()>
 * }
 * @method QueryBuilder           selectMax($column, $where = [], $groupBy = [], $having = []) {
 *     駆動表を省略できる <@uses Database::selectMax()>
 * }
 * @method QueryBuilder           selectSum($column, $where = [], $groupBy = [], $having = []) {
 *     駆動表を省略できる <@uses Database::selectSum()>
 * }
 * @method QueryBuilder           selectAvg($column, $where = [], $groupBy = [], $having = []) {
 *     駆動表を省略できる <@uses Database::selectAvg()>
 * }
 *
 * @method array                  insertOrThrow($data) {
 *     <@uses TableGateway::insert()> の例外送出版
 * }
 * @method array                  updateOrThrow($data, array $identifier = []) {
 *     <@uses TableGateway::update()> の例外送出版
 * }
 * @method array                  deleteOrThrow(array $identifier = []) {
 *     <@uses TableGateway::delete()> の例外送出版
 * }
 * @method array                  removeOrThrow(array $identifier = []) {
 *     <@uses TableGateway::remove()> の例外送出版
 * }
 * @method array                  destroyOrThrow(array $identifier = []) {
 *     <@uses TableGateway::destroy()> の例外送出版
 * }
 * @method array                  reduceOrThrow($limit = null, $orderBy = [], $groupBy = [], $identifier = []) {
 *     <@uses TableGateway::reduce()> の例外送出版
 * }
 * @method array                  upsertOrThrow($insertData, $updateData = []) {
 *     <@uses TableGateway::upsert()> の例外送出版
 * }
 * @method array                  modifyOrThrow($insertData, $updateData = []) {
 *     <@uses TableGateway::modify()> の例外送出版
 * }
 * @method array                  replaceOrThrow($data) {
 *     <@uses TableGateway::replace()> の例外送出版
 * }
 *
 * @method array                  insertSelectIgnore($sql, $columns = [], iterable $params = []) {
 *     駆動表を省略できる <@uses Database::insertSelectIgnore()>
 * }
 * @method array                  insertArrayIgnore($data, $chunk = 0) {
 *     駆動表を省略できる <@uses Database::insertArrayIgnore()>
 * }
 * @method array                  updateArrayIgnore($data, $identifier = []) {
 *     駆動表を省略できる <@uses Database::updateArrayIgnore()>
 * }
 * @method array                  modifyArrayIgnore($insertData, $updateData = [], $chunk = 0) {
 *     駆動表を省略できる <@uses Database::modifyArrayIgnore()>
 * }
 * @method array                  changeArrayIgnore($dataarray, $identifier) {
 *     駆動表を省略できる <@uses Database::changeArrayIgnore()>
 * }
 * @method array                  saveIgnore($data) {
 *     駆動表を省略できる <@uses Database::saveIgnore()>
 * }
 * @method array                  insertIgnore($data) {
 *     駆動表を省略できる <@uses Database::insertIgnore()>
 * }
 * @method array                  updateIgnore($data, array $identifier = []) {
 *     駆動表を省略できる <@uses Database::updateIgnore()>
 * }
 * @method array                  deleteIgnore(array $identifier = []) {
 *     駆動表を省略できる <@uses Database::deleteIgnore()>
 * }
 * @method array                  removeIgnore(array $identifier = []) {
 *     駆動表を省略できる <@uses Database::removeIgnore()>
 * }
 * @method array                  destroyIgnore(array $identifier = []) {
 *     駆動表を省略できる <@uses Database::destroyIgnore()>
 * }
 * @method array                  modifyIgnore($insertData, $updateData = []) {
 *     駆動表を省略できる <@uses Database::modifyIgnore()>
 * }
 *
 * @method array                  insertConditionally($condition, $data) {
 *     駆動表を省略できる <@uses Database::insertConditionally()>
 * }
 * @method array                  upsertConditionally($condition, $insertData, $updateData = []) {
 *     駆動表を省略できる <@uses Database::upsertConditionally()>
 * }
 * @method array                  modifyConditionally($condition, $insertData, $updateData = []) {
 *     駆動表を省略できる <@uses Database::modifyConditionally()>
 * }
 *
 * @method Statement              prepareSelect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     駆動表を省略できる <@uses Database::prepareSelect()>
 * }
 * @method Statement              prepareInsert($data) {
 *     駆動表を省略できる <@uses Database::prepareInsert()>
 * }
 * @method Statement              prepareUpdate($data, array $identifier = []) {
 *     駆動表を省略できる <@uses Database::prepareUpdate()>
 * }
 * @method Statement              prepareDelete(array $identifier = []) {
 *     駆動表を省略できる <@uses Database::prepareDelete()>
 * }
 * @method Statement              prepareModify($insertData, $updateData = []) {
 *     駆動表を省略できる <@uses Database::prepareModify()>
 * }
 * @method Statement              prepareReplace($data) {
 *     駆動表を省略できる <@uses Database::prepareReplace()>
 * }
 */
// @formatter:on
class TableGateway implements \ArrayAccess, \IteratorAggregate, \Countable
{
    use OptionTrait;
    use IteratorTrait {
        count as countIterator;
    }

    /** @var array scope のデフォルト値 */
    private static $defargs = [
        'column'  => [],
        'where'   => [],
        'orderBy' => [],
        'limit'   => [],
        'groupBy' => [],
        'having'  => [],
    ];

    /** @var string デフォルト iterate メソッド */
    protected $defaultIteration = '';

    /** @var string デフォルト JOIN メソッド */
    protected $defaultJoinMethod = '';

    /** @var Database Database オブジェクト */
    private $database;

    /** @var TableGateway */
    private $original;

    /** @var string テーブル名 */
    private $tableName;

    /** @var string エイリアス名 */
    private $alias;

    /** @var \ArrayObject クエリスコープ配列（インスタンス間共用） */
    private $scopes;

    /** @var array 有効スコープ配列 */
    private $activeScopes = ['' => []];

    /** @var string 使用する外部キー */
    private $foreign;

    /** @var string インデックスヒント */
    private $hint;

    /** @var TableGateway */
    private $end;

    /** @var TableGateway[] join する Gateway 配列 */
    private $joins = [];

    /** @var array join するパラメータ */
    private $joinParams = [];

    /** @var array 行を一意に特定できるなにか */
    private $pkukval = [];

    public static function getDefaultOptions()
    {
        return [
            // 直接回した場合のフェッチモード
            'defaultIteration'  => 'array',
            // マジック JOIN 時のデフォルトモード
            'defaultJoinMethod' => 'auto',
            // affect 系で無視するスコープ
            'ignoreAffectScope' => [], // for compatible. In the future the default will be ['']
            // bindScope が上書きか累積か
            'overrideBindScope' => false, // for compatible. In the future the default will be true or delete
        ];
    }

    /**
     * コンストラクタ
     *
     * @param Database $database データベース
     * @param string $table_name テーブル名
     * @param ?string $entity_name エンティティ名
     */
    public function __construct(Database $database, $table_name, $entity_name = null)
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
     *
     * @param string $name テーブル名
     * @return $this JOIN した ゲートウェイ
     */
    public function __get($name)
    {
        $tname = $this->database->convertTableName($name);
        if (isset($this->database->$tname)) {
            $method = $this->getUnsafeOption('defaultJoinMethod') . 'join';
            $that = $this->$method($this->database->$name);
            return end($that->joins);
        }
    }

    /**
     * サポートされない
     *
     * 将来のために予約されており、呼ぶと無条件で例外を投げる。
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value) { throw new \DomainException(__METHOD__ . ' is not supported.'); }

    /**
     * @ignore
     *
     * @param string $name メソッド名
     * @param array $arguments 引数配列
     * @return $this|mixed
     */
    public function __call($name, $arguments)
    {
        // OptionTrait へ移譲
        $result = $this->OptionTrait__callGetSet($name, $arguments, $called);
        if ($called) {
            return $result;
        }

        // スコープ系メソッド
        if (preg_match('#^(column|where|orderBy|limit|groupBy|having)$#ui', $name)) {
            $params = array_change_key_case(self::$defargs, CASE_LOWER);
            $params[strtolower($name)] = $arguments[0];
            return $this->scoping(...array_values($params));
        }

        // join 系メソッド
        if (preg_match('#^(inner|left|right)?JoinOn$#ui', $name, $matches)) {
            return $this->join($matches[1] ?? '' ?: 'inner', array_shift($arguments), array_get($arguments, 0, []), '');
        }
        if (preg_match('#^(auto|inner|left|right)?Join(Foreign)?$#ui', $name, $matches)) {
            return $this->join($matches[1] ?? '' ?: 'auto', array_shift($arguments), [], array_get($arguments, 0, null));
        }
        if (preg_match('#^(auto|inner|left|right)?JoinForeignOn$#ui', $name, $matches)) {
            return $this->join($matches[1] ?? '' ?: 'auto', array_shift($arguments), array_get($arguments, 0, []), array_get($arguments, 1, null));
        }

        // スコープを当てた後に Database への移譲で済むもの系
        if (preg_match("#^((prepare)?select|select(count|min|max|sum|avg)|(not)?subexists|sub(query|count|min|max|sum|avg)|_count|min|max|sum|avg)$#ui", $name)) {
            // Countable のために別メソッドにしているので読み替え
            if (strcasecmp($name, '_count') === 0) {
                $name = 'count';
            }

            $sp = $this->getScopeParams(...$arguments);
            $return = $this->database->$name(...array_values($sp));

            // メソッドによってはクエリビルダが返ってくるのでこの段階でヒントを紐付ける
            if ($return instanceof QueryBuilder) {
                $return->hint($this->hint);
                // エンティティモードの場合は更に cast する
                if ($this->original->alias && strcasecmp($name, 'select') === 0) {
                    $return->cast(null);
                }
            }
            return $return;
        }

        // ↑と同じだが、引数体系が違うもの系
        if (preg_match('/^(exists|select(Not)?Exists)$/ui', $name, $matches)) {
            $sp = $this->getScopeParams([], array_get($arguments, 0, []));
            return $this->database->$name($sp['column'], $sp['where'], array_get($arguments, 1, false));
        }

        // subselect 系
        if (preg_match('/^sub(select)?(.+?)$/ui', $name, $matches)) {
            return $this->database->{"subselect{$matches[2]}"}(...array_values($this->getScopeParams(...$arguments)));
        }

        // find メソッド（tuple メソッドの特別版とみなせる）
        if (preg_match('#^find(.*)#ui', $name, $matches)) {
            $method = 'tuple' . $matches[1];
            if (is_array($arguments[0])) {
                $primary = $arguments[0];
                $columns = array_slice($arguments, 1) ?: [];
            }
            else {
                $primary = $arguments;
                $columns = is_array(end($primary)) ? array_pop($primary) : [];
            }
            return $this->pk($primary)->$method($columns);
        }

        // yield 系メソッド
        if (preg_match('/^yield/ui', $name, $matches)) {
            return $this->database->$name($this->select(...$arguments));
        }

        // export 系メソッド
        if (preg_match('/^export/ui', $name, $matches)) {
            $config = (array) array_shift($arguments);
            $file = array_get($config, 'file');
            return $this->database->$name($this->select(...$arguments), [], $config, $file);
        }

        // builder への移譲系メソッド
        if (preg_match('#^(neighbor)$#ui', $name, $matches)) {
            if (strcasecmp($name, 'neighbor') === 0 && $this->pkukval && !($arguments[0] ?? null)) {
                $arguments[0] = $this->pkukval;
            }
            $select = $this->select();
            if ($this->original->alias) {
                $select->cast(null);
            }
            return $select->$name(...$arguments);
        }

        // マジックジョイン
        $tname = $this->database->convertTableName($name);
        if (isset($this->database->$tname)) {
            $method = $this->getUnsafeOption('defaultJoinMethod') . 'join';
            return $this->$method($this->database->$name(...$arguments));
        }

        // prepare～affect 系
        if (preg_match('/^prepare(.+)/ui', $name, $matches)) {
            $method = strtolower($matches[1]);
            $restorer = $this->database->storeOptions(['preparing' => true]);
            return try_finally([$this, $method], $restorer, ...$arguments);
        }
        // affect～OrThrow 系
        if (preg_match('/^(insert|update|delete|remove|destroy|reduce|upsert|modify|replace|truncate)OrThrow$/ui', $name, $matches)) {
            $method = strtolower($matches[1]);
            Adhoc::reargument($arguments, [$this, $method], []);
            $arguments[] = ['primary' => 1, 'throw' => true];
            return $this->$method(...$arguments);
        }
        // affect～Ignore 系
        if (preg_match('/^(insertSelect|insertArray|updateArray|modifyArray|changeArray|save|insert|update|delete|remove|destroy|modify)Ignore$/ui', $name, $matches)) {
            $method = strtolower($matches[1]);
            Adhoc::reargument($arguments, [$this, $method], []);
            $arguments[] = ['primary' => 2, 'ignore' => true];
            return $this->$method(...$arguments);
        }
        // affect～Conditionally 系
        if (preg_match('/^(insert|upsert|modify)Conditionally$/ui', $name, $matches)) {
            $method = strtolower($matches[1]);
            $opt = Adhoc::reargument($arguments, [$this, $method], [0 => 'where']);
            $arguments[] = ['primary' => 2] + $opt;
            return $this->$method(...$arguments);
        }

        // 上記以外はすべて fetch 系メソッドとする（例外は Database 側で投げてくれるはず）
        $lockmode = null;
        if (preg_match('#(ForUpdate|InShare)#ui', $name, $matches)) {
            $lockmode = strtolower($matches[1]);
            $name = str_ireplace($lockmode, '', $name);
        }
        $select = $this->select(...$arguments);
        if ($lockmode) {
            $select->{"lock$lockmode"}();
        }
        return $this->database->{"fetch$name"}($select);
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
     *
     * @return string クエリ文字列
     */
    public function __toString()
    {
        return $this->select([])->queryInto();
    }

    private function _primary($pkval)
    {
        $pcols = $this->database->getSchema()->getTablePrimaryKey($this->tableName)->getColumns();
        $pvals = array_values((array) $pkval);
        return array_combine(array_slice($pcols, 0, count($pvals)), $pvals) ?: throws(new \InvalidArgumentException("argument's length is not match primary columns."));
    }

    private function _accessor($name, $value)
    {
        // 引数なしの場合は getter として振る舞う
        if ($value === null) {
            return $this->$name;
        }

        $that = $this->clone();
        $that->$name = $value;
        return $that;
    }

    private function _rewhere(&$where)
    {
        $sp = $this->getScopeParamsForAffect([], $where);
        $where = $sp['where'];

        // 集約系はどうしようもないので例外（集約を利用した update/delete なんて考慮したくない）
        if (count($sp['groupBy']) > 0 || count($sp['having']) > 0) {
            throw new \UnexpectedValueException('group or having is not allow affect query.');
        }
        // 順序・制限系クエリなら（mysql なら）それを活かした update/delete が実行できるのでそのようにする
        if (count($sp['column']) > 1 || count($sp['orderBy']) > 0 || count($sp['limit']) > 0) {
            $sp['where'] = [];
            return $this->database->select(...array_values($sp));
        }
        return $this->tableName;
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
     *
     * @param string $offset カラム名
     * @return bool 存在するなら true
     */
    public function offsetExists($offset)
    {
        if (is_array($offset) || filter_var($offset, \FILTER_VALIDATE_INT) !== false) {
            return $this->exists($this->_primary($offset));
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
     * $row = $gw->[1]['**']; // 怠惰取得も可能（怠惰取得については QueryBuilder::column() を参照）
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
     *
     * @param string $offset カラム名あるいはテーブル記法
     * @return $this|array|Entityable|mixed レコード・カラム値・自分自身
     */
    public function offsetGet($offset)
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
     *
     * @param string $offset カラム名
     * @param mixed $value カラム値
     * @return int|array 作用行
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            return $this->insert($value);
        }
        if (is_array($offset) || filter_var($offset, \FILTER_VALIDATE_INT) !== false) {
            return $this->modify($value + $this->_primary($offset));
        }
        return $this->update([$offset => $value]);
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
     *
     * @param mixed $offset
     * @return int|array 作用行
     */
    public function offsetUnset($offset)
    {
        if (is_array($offset) || filter_var($offset, \FILTER_VALIDATE_INT) !== false) {
            return $this->delete($this->_primary($offset));
        }
        throw new \DomainException(__METHOD__ . ' is not supported.');
    }

    /**
     * コピーインスタンスを返す
     *
     * 「コピーインスタンス」とは「オリジナルではないインスタンス」のこと。
     * オリジナルでなければコピーなので複数回呼んでも初回以外は同じインスタンスを返す。
     * それを避けるには $force に true を渡す必要がある。
     *
     * @param bool $force true にすると強制的にコピーして返す
     * @return $this コピーインスタンス
     */
    public function clone($force = false)
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
     * @return \Doctrine\DBAL\Schema\Table Table オブジェクト
     */
    public function describe()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->database->describe($this->tableName);
    }

    /**
     * テーブルエイリアス名を設定する
     *
     * ```php
     * // SELECT * FROM tablename AS hoge_alias
     * echo $gw->as('hoge_alias');
     * ```
     *
     * @param string $alias テーブルエイリアス名
     * @return $this 自分自身
     */
    public function as($alias)
    {
        if (strlen($alias) === 0) {
            return $this;
        }
        $that = $this->clone();
        $that->alias = $alias;
        return $that;
    }

    /**
     * @ignore
     */
    public function joinize()
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
     *
     * @param int $back 戻る回数
     * @return $this 自分自身
     */
    public function end($back = 0)
    {
        if ($this->end === null) {
            return $this;
        }

        return $this->end->end($back - 1);
    }

    /**
     * 実テーブル名を返す
     *
     * @return string 実テーブル名
     */
    public function tableName()
    {
        return $this->tableName;
    }

    /**
     * select の際に使用されるエイリアス名を設定・取得する
     *
     * 引数を与えると setter, 与えないと getter として動作する。
     * setter の場合は自分自身を返す。
     *
     * @param string|null $alias エイリアス名
     * @return $this|string エイリアス名 or 自分自身
     */
    public function alias($alias = null)
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
    public function foreign($foreign = null)
    {
        return $this->_accessor('foreign', $foreign);
    }

    /**
     * インデックスヒントを設定・取得する
     *
     * 引数を与えると setter, 与えないと getter として動作する。
     * setter の場合は自分自身を返す。
     *
     * @param string|null $hint ヒント句
     * @return $this|string  ヒント句 or 自分自身
     */
    public function hint($hint = null)
    {
        return $this->_accessor('hint', $hint);
    }

    /**
     * @ignore
     *
     * @return string
     */
    public function descriptor()
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
     *
     * @return string テーブル修飾子
     */
    public function modifier()
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
     * @used-by innerJoinForeign()
     * @used-by leftJoinForeign()
     * @used-by rightJoinForeign()
     * @used-by joinForeignOn()
     * @used-by innerJoinForeignOn()
     * @used-by leftJoinForeignOn()
     * @used-by rightJoinForeignOn()
     *
     * @param string $type 結合タイプ（AUTO, INNER, ...）
     * @param TableGateway $gateway 結合するテーブルゲートウェイ
     * @param string|array $on 結合条件。 {@link where()} と同じ形式が使える
     * @param ?string $fkeyname 外部キー名称。省略時は唯一の外部キーを使用（無かったり2個以上ある場合は例外）
     * @return $this 自分自身
     */
    public function join($type, TableGateway $gateway, $on = [], $fkeyname = null)
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
     *
     * @return $this 自分自身
     */
    public function dryrun()
    {
        $that = $this->context();
        $that->database = $that->database->dryrun();
        return $that;
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
     * @return $this 自分自身
     */
    public function addScope($name = '', $tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        if ($tableDescriptor instanceof \Closure) {
            $scope = $tableDescriptor;
        }
        else {
            $scope = array_combine(QueryBuilder::CLAUSES, [
                arrayize($tableDescriptor),
                arrayize($where),
                arrayize($orderBy),
                arrayize($limit),
                arrayize($groupBy),
                arrayize($having)
            ]);
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
     * @return $this 自分自身
     */
    public function mixScope($name, $sourceScopes, ...$newscopes)
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
     * @return $this 自分自身
     */
    public function bindScope($name, array $binding)
    {
        if (!isset($this->scopes[$name])) {
            throw new \InvalidArgumentException("'$name' scope is undefined.");
        }
        if (!$this->scopes[$name] instanceof \Closure) {
            throw new \InvalidArgumentException("'$name' scope must be closure.");
        }

        if ($this->getUnsafeOption('overrideBindScope')) {
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

        // @codeCoverageIgnoreStart
        $scope = $this->scopes[$name];
        $ref = reflect_callable($scope);
        $uses = $ref->getStaticVariables();
        if ($ref->getFileName() === __FILE__ && isset($uses['binding'])) {
            $binding += $uses['binding'];
        }
        $this->scopes[$name] = function (...$args) use ($scope, $binding) {
            ksort($binding);
            return $scope(...($args + $binding));
        };

        return $this;
        // @codeCoverageIgnoreEnd
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
    public function scoping($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        $that = $this->clone();
        $hash = spl_object_id($that) . '_' . count($that->scopes);
        $that->addScope($hash, ...func_get_args());
        $that->activeScopes[$hash] = [];
        return $that;
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
     * @return $this 自分自身
     */
    public function scope($name = '', $variadic_parameters = [])
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
     *
     * @param string $name スコープ名
     * @return $this 自分自身
     */
    public function unscope($name = '')
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
     *
     * @return $this 自分自身
     */
    public function noscope()
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
     *
     * @return array スコープ配列
     */
    public function getScopes()
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
     *
     * @param string|array $name スコープ名
     * @param mixed $variadic_parameters スコープパラメータ
     * @return array スコープパラメータ
     */
    public function getScopeParts($name = '', $variadic_parameters = [])
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
     *
     * @return array スコープの各句配列
     */
    public function getScopeParams($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
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
            $scope['column'] = [$aname => $scope['column']];

            $sargs = array_merge_recursive($sargs, $scope);

            // limit は配列とスカラーで扱いが異なるので「指定されていたら上書き」という挙動にする
            if ($scope['limit']) {
                $sargs['limit'] = $scope['limit'];
            }
        }

        // 修飾子を付加して返す（$column はビルダ側で付けてくれるので不要）
        $alias = $that->modifier();
        return array_combine(QueryBuilder::CLAUSES, [
            $sargs['column'],
            Adhoc::modifier($alias, $sargs['where']),
            Adhoc::modifier($alias, $sargs['orderBy']),
            $sargs['limit'],
            Adhoc::modifier($alias, $sargs['groupBy']),
            Adhoc::modifier($alias, $sargs['having']),
        ]);
    }

    /**
     * 更新用の {@link getScopeParams()}
     *
     * @inheritdoc getScopeParams()
     */
    public function getScopeParamsForAffect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
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
     * @return $this 自分自身
     */
    public function pk(...$variadic)
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
     * @return $this 自分自身
     */
    public function uk(...$variadic)
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
     * @used-by min()
     * @used-by max()
     * @used-by sum()
     * @used-by avg()
     *
     * @param string|array $column SELECT 句
     * @param string|array $where WHERE 句
     * @param string|array $groupBy GROUP BY 句
     * @param string|array $having HAVING 句
     * @return int レコード件数
     */
    public function count($column = [], $where = [], $groupBy = [], $having = [])
    {
        // 引数が来ているならメソッド（Countable::count は引数が来ない）
        if (func_num_args() > 0) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->_count($column, $where, $groupBy, $having);
        }

        // 上記以外は Countable::count
        return $this->countIterator();
    }

    /**
     * new Paginator へのプロキシメソッド
     *
     * 引数が与えられている場合は {@link Paginator::paginate()} も同時に行う。
     *
     * @inheritdoc QueryBuilder::paginate()
     */
    public function paginate($currentpage = null, $countperpage = null, $shownpage = null)
    {
        return $this->select()->paginate(...func_get_args());
    }

    /**
     * new Sequencer へのプロキシメソッド
     *
     * 引数が与えられている場合は {@link Sequencer::sequence()} も同時に行う。
     *
     * @inheritdoc QueryBuilder::sequence()
     */
    public function sequence($condition, $count, $orderbyasc = true, $bidirection = true)
    {
        return $this->select()->sequence(...func_get_args());
    }

    /**
     * 分割して sequence してレコードジェネレータを返す
     *
     * Gateway 版の {@link QueryBuilder::chunk()} 。
     *
     * @inheritdoc QueryBuilder::chunk()
     */
    public function chunk($count, $column = null)
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
    public function gather($wheres = [], $other_wheres = [], $parentive = false)
    {
        $sp = $this->getScopeParams([], $wheres);
        return $this->database->gather($this->tableName, $sp['where'], $other_wheres, $parentive);
    }

    /**
     * 駆動表を省略できる <@uses Database::insertSelect()>
     *
     * @inheritdoc Database::insertSelect()
     */
    public function insertSelect($sql, $columns = [], iterable $params = [])
    {
        $this->resetResult();
        return $this->database->insertSelect($this->tableName, $sql, $columns, $params, ...array_slice(func_get_args(), 3));
    }

    /**
     * 駆動表を省略できる <@uses Database::insertArray()>
     *
     * @inheritdoc Database::insertArray()
     */
    public function insertArray($data, $chunk = 0)
    {
        $this->resetResult();
        return $this->database->insertArray($this->tableName, $data, $chunk, ...array_slice(func_get_args(), 2));
    }

    /**
     * 駆動表を省略できる <@uses Database::updateArray()>
     *
     * @inheritdoc Database::updateArray()
     */
    public function updateArray($data, $identifier = [])
    {
        $this->resetResult();
        return $this->database->updateArray($this->tableName, $data, $identifier, ...array_slice(func_get_args(), 2));
    }

    /**
     * 駆動表を省略できる <@uses Database::modifyArray()>
     *
     * @inheritdoc Database::modifyArray()
     */
    public function modifyArray($insertData, $updateData = [], $chunk = 0)
    {
        $this->resetResult();
        return $this->database->modifyArray($this->tableName, $insertData, $updateData, $chunk, ...array_slice(func_get_args(), 3));
    }

    /**
     * 駆動表を省略できる <@uses Database::changeArray()>
     *
     * @inheritdoc Database::changeArray()
     */
    public function changeArray($dataarray, $identifier)
    {
        $this->resetResult();
        return $this->database->changeArray($this->tableName, $dataarray, $identifier, ...array_slice(func_get_args(), 2));
    }

    /**
     * 駆動表を省略できる <@uses Database::save()>
     *
     * @inheritdoc Database::save()
     */
    public function save($data)
    {
        $this->resetResult();
        return $this->database->save($this->tableName, $data, ...array_slice(func_get_args(), 1));
    }

    /**
     * 駆動表を省略できる <@uses Database::insert()>
     *
     * @inheritdoc Database::insert()
     */
    public function insert($data)
    {
        $this->resetResult();
        $sp = $this->getScopeParamsForAffect([]);
        if (false
            || 1 < count($sp['column'])
            || 0 < count($sp['where'])
            || 0 < count($sp['orderBy'])
            || 0 < count($sp['limit'])
            || 0 < count($sp['groupBy'])
            || 0 < count($sp['having'])
        ) {
            return $this->database->insertSelect($this->tableName, $this->database->select(...array_values($sp)));
        }
        return $this->database->insert($this->tableName, ...func_get_args());
    }

    /**
     * 駆動表を省略できる <@uses Database::update()>
     *
     * @inheritdoc Database::update()
     */
    public function update($data, array $identifier = [])
    {
        $this->resetResult();
        return $this->database->update($this->_rewhere($identifier), $data, $identifier, ...array_slice(func_get_args(), 2));
    }

    /**
     * 駆動表を省略できる <@uses Database::delete()>
     *
     * @inheritdoc Database::delete()
     */
    public function delete(array $identifier = [])
    {
        $this->resetResult();
        return $this->database->delete($this->_rewhere($identifier), $identifier, ...array_slice(func_get_args(), 1));
    }

    /**
     * 駆動表を省略できる <@uses Database::remove()>
     *
     * @inheritdoc Database::remove()
     */
    public function remove(array $identifier = [])
    {
        $this->resetResult();
        return $this->database->remove($this->_rewhere($identifier), $identifier, ...array_slice(func_get_args(), 1));
    }

    /**
     * 駆動表を省略できる <@uses Database::destroy()>
     *
     * @inheritdoc Database::destroy()
     */
    public function destroy(array $identifier = [])
    {
        $this->resetResult();
        return $this->database->destroy($this->_rewhere($identifier), $identifier, ...array_slice(func_get_args(), 1));
    }

    /**
     * 駆動表を省略できる <@uses Database::reduce()>
     *
     * @inheritdoc Database::reduce()
     */
    public function reduce($limit = null, $orderBy = [], $groupBy = [], $identifier = [])
    {
        $this->resetResult();
        return $this->database->reduce($this->select(), $limit, $orderBy, $groupBy, $identifier, ...array_slice(func_get_args(), 4));
    }

    /**
     * 駆動表を省略できる <@uses Database::upsert()>
     *
     * @inheritdoc Database::upsert()
     */
    public function upsert($insertData, $updateData = [])
    {
        $this->resetResult();
        return $this->database->upsert($this->tableName, $insertData, $updateData, ...array_slice(func_get_args(), 2));
    }

    /**
     * 駆動表を省略できる <@uses Database::modify()>
     *
     * @inheritdoc Database::modify()
     */
    public function modify($insertData, $updateData = [])
    {
        $this->resetResult();
        return $this->database->modify($this->tableName, $insertData, $updateData, ...array_slice(func_get_args(), 2));
    }

    /**
     * 駆動表を省略できる <@uses Database::replace()>
     *
     * @inheritdoc Database::replace()
     */
    public function replace($insertData)
    {
        $this->resetResult();
        return $this->database->replace($this->tableName, $insertData, ...array_slice(func_get_args(), 1));
    }

    /**
     * 駆動表を省略できる <@uses Database::truncate()>
     *
     * @inheritdoc Database::truncate()
     */
    public function truncate($cascade = false)
    {
        $this->resetResult();
        return $this->database->truncate($this->tableName, $cascade);
    }

    /**
     * 最後に挿入した ID を返す
     *
     * Gateway 版の {@link Database::getLastInsertId()} 。
     *
     * @inheritdoc Database::getLastInsertId()
     */
    public function getLastInsertId($columnname = null)
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
    public function resetAutoIncrement($seq = 1)
    {
        return $this->database->resetAutoIncrement($this->tableName, $seq);
    }
}
