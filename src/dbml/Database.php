<?php

namespace ryunosuke\dbml;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Event\Listeners\SQLSessionInit;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Platforms;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Types\Type;
use ryunosuke\dbml\Entity\Entity;
use ryunosuke\dbml\Entity\Entityable;
use ryunosuke\dbml\Exception\NonAffectedException;
use ryunosuke\dbml\Exception\NonSelectedException;
use ryunosuke\dbml\Exception\TooManyException;
use ryunosuke\dbml\Gateway\TableGateway;
use ryunosuke\dbml\Generator\AbstractGenerator;
use ryunosuke\dbml\Generator\Yielder;
use ryunosuke\dbml\Metadata\CompatiblePlatform;
use ryunosuke\dbml\Metadata\Schema;
use ryunosuke\dbml\Mixin\OptionTrait;
use ryunosuke\dbml\Query\Expression\Alias;
use ryunosuke\dbml\Query\Expression\Expression;
use ryunosuke\dbml\Query\Expression\Operator;
use ryunosuke\dbml\Query\Expression\TableDescriptor;
use ryunosuke\dbml\Query\Queryable;
use ryunosuke\dbml\Query\QueryBuilder;
use ryunosuke\dbml\Query\Statement;
use ryunosuke\dbml\Query\SubqueryBuilder;
use ryunosuke\dbml\Transaction\Transaction;
use ryunosuke\dbml\Utility\Adhoc;

// @formatter:off
/**
 * データベースそのものを表すクラス
 *
 * すべての根幹となり、基本的に利用側はこのクラスのインスタンスしか利用しない（のが望ましい）。
 *
 * ### インスタンス作成
 *
 * ```php
 * # シングル環境
 * $dbconfig = [
 *     'driver'   => 'pdo_mysql',
 *     'host'     => '127.0.0.1',
 *     'port'     => '3306',
 *     'dbname'   => 'dbname',
 *     'user'     => 'user',
 *     'password' => 'password',
 *     'charset'  => 'utf8',
 * ];
 * $db = new \ryunosuke\dbml\Database($dbconfig, []);
 *
 * # レプリケーション環境
 * $dbconfig = [
 *     'driver'   => 'pdo_mysql',
 *     'host'     => ['master_host', 'slave_host'],
 *     'port'     => '3306',
 *     'dbname'   => 'dbname',
 *     'user'     => 'user',
 *     'password' => ['master_password', 'slave_password'],
 *     'charset'  => 'utf8',
 * ];
 * $db = new \ryunosuke\dbml\Database($dbconfig, []);
 * ```
 *
 * このようにして作成する。要するにコンストラクタの引数に \Doctrine\DBAL\DriverManager::getConnection と同じ配列を渡す。
 * 要素を配列にした場合はそれぞれ個別の指定として扱われる。
 *
 * 詳細は{@link __construct() コンストラクタ}を参照
 *
 * ### コネクション（マスター/スレーブ）
 *
 * 上記のようにマスター/スレーブ構成用に接続を分けることができる。
 * マスターは更新系クエリ、スレーブは参照系クエリという風に自動で使い分けられる。
 * またトランザクション系（begin, commit, rollback）はマスター側で実行される（一応引数で分けることができる）。
 *
 * マスター/スレーブモードは可能な限りマスターへ負荷をかけないような設計になっている。
 * つまり、テーブル定義の describe やデータベースバージョンの取得などは全てスレーブで行われ、マスターへは接続しない。
 * 理想的な状況の場合（更新系クエリが一切ない場合）、マスターへは接続すらしない。
 * ただし、その弊害としてマスター・スレーブは完全に同じ RDBMS である必要がある。また、スキーマ情報に差異があると予想外の動きをする可能性がある。
 *
 * ### 補助メソッド
 *
 * いくつかのメソッドは特定のサフィックスを付けることで異なる挙動を示すようになる。
 * 内部処理が黒魔術的なので、呼ぼうとすると無理やり呼べたりするが、基本的にコード補完に出ないメソッドは使用しないこと（テストしてないから）。
 *
 * **InShare/ForUpdate**
 *
 * 取得系メソッドに付与できる。
 * InShare を付与すると SELECT クエリに共有ロック構文が付与される（mysql なら LOCK IN SHARE MODE）。
 * ForUpdate を付与すると SELECT クエリに排他ロック構文が付与される（mysql なら FOR UPDATE）。
 *
 * **OrThrow**
 *
 * 通常の更新系/取得系メソッドに付与できる。
 * 作用行がなかったときに例外を投げたり、返り値として主キー配列を返すようになる。
 * これらの orThrow 系メソッドにより、「（詳細画面などで）行が見つからなかったら」「（何らかの原因により）行が insert されなかったら」の戻り値チェックを省くことができ、シンプルなコードを書くことができる。
 *
 * | メソッド                                       | 説明
 * |:--                                             |:--
 * | insert などの行追加系                          | affected row が 0 の時に例外を投げる。更に戻り値として主キー配列を返す
 * | update, delete などの行作用系                  | affected row が 0 の時に例外を投げる。更に戻り値として**可能な限り**主キー配列を返す（後述）
 * | upsert などの行置換系                          | affected row が 0 の時に例外を投げる。更に戻り値として**追加/更新した行の**主キー配列を返す（{@link upsert()}参照）
 * | fetchArray, fetchLists などの複数行を返す系    | フェッチ行数が 0 の時に例外を投げる
 * | fetchTuple などの単一行を返す系                | 行が見つからなかった時に例外を投げる
 * | fetchValue などのスカラー値を返す系            | 行が見つからなかった時に例外を投げる。 PostgreSQL の場合やカラムキャストが有効な場合は注意
 *
 * mysql の UPDATE は条件が一致しても値が変わらなければ affected rows として 0 を返すので OrThrow すると正常動作なのに例外を投げる、という事象が発生する。
 * この動作が望ましくない場合は `PDO::MYSQL_ATTR_FOUND_ROWS = true` を使用する。
 *
 * [update/delete]OrThrow の戻り値は主キーだが、複数行に作用した場合は未定義となる（['id' => 3] で update/delete した場合は 3 を返せるが、['create_at < ?' => '2011-12-34'] といった場合は返しようがないため）。
 * そもそも「更新/削除できなかったら例外」という挙動が必要なケースはほぼ無いためこれらの用途はほとんどなく、単に他のメソッドとの統一のために存在している。
 *
 * **Ignore**
 *
 * [insert, updert, modify, delete, remove, destroy] メソッドのみに付与できる。
 * RDBMS に動作は異なるが、 `INSERT IGNORE` のようなクエリが発行される。
 *
 * **Conditionally**
 *
 * [insert, upsert, modify] メソッドのみに付与できる。
 * 条件付き insert となり、「insert された場合にその主キー」を「されなかった場合に空配列」を返す。
 * 最も多いユースケースとしては「行がないなら insert」だろう。
 *
 * ### エスケープ
 *
 * 識別子のエスケープは一切面倒をみない。外部入力を識別子にしたい（テーブル・カラムを外部指定するとか）場合は自前でエスケープすること。
 * 値のエスケープに関しては基本的には安全側に倒しているが、 {@link Expression} を使用する場合はその前提が崩れる事がある（ `()` を含むエントリは自動で Expression 化されるので同じ）。
 * 原則的に外部入力を Expression 化したり、値以外の入力として使用するのは全く推奨できない。
 *
 * @method array                  getDefaultEntity()
 * @method $this                  setDefaultEntity(array $array) {
 *     デフォルトエンティティ兼コンストラクタ引数を指定する
 *
 *     entityMapper でマッピングしたテーブルはそのエンティティ名が使用されるが、未設定だったり null を返したりすると、この設定に応じてデフォルトエンティティを返す。
 *     キーがクラス名、値がコンストラクタ引数になる。
 * }
 * @method bool                   getNotableAsColumn()
 * @method $this                  setNotableAsColumn(bool $bool)
 * @method bool                   getInsertSet()
 * @method $this                  setInsertSet($bool)
 * @method bool                   getFilterNoExistsColumn()
 * @method $this                  setFilterNoExistsColumn($bool) {
 *     存在しないカラムをフィルタするか指定する
 *
 *     この設定を true にすると INSERT/UPDATE 時に「対象テーブルに存在しないカラム」が自動で伏せられるようになる。
 *     余計なキーが有るだけでエラーになるのは多くの場合めんどくさいだけなので true にするのは有用。
 *
 *     ただし、スペルミスなどでキーの指定を誤ると何も言わずに伏せてしまうので気づきにくい不具合になるので注意。
 *
 *     なお、デフォルトは true。
 *
 *     @param bool $bool 存在しないカラムをフィルタするなら true
 * }
 * @method bool                   getConvertEmptyToNull()
 * @method $this                  setConvertEmptyToNull($bool) {
 *     NULLABLE に空文字が来たときの挙動を指定する
 *
 *     この設定を true にすると、例えば `hoge_no: INTEGER NOT NULL` なカラムに空文字を与えて INSERT/UPDATE した場合に自動で NULL に変換されるようになる。
 *     Web システムにおいては空文字でパラメータが来ることが多いのでこれを true にしておくといちいち変換せずに済む。
 *
 *     よくあるのは「年齢」というカラムがあり、入力画面で必須ではない場合。
 *     未入力で空文字が飛んでくるので、設定にもよるがそのまま mysql に突っ込んでしまうと 0 になるかエラーになる。
 *     これはそういったケースで楽をするための設定。
 *
 *     なお、デフォルトは true。
 *
 *     @param bool $bool 空文字を NULL に変換するなら true
 * }
 * @method string                 getAutoCastSuffix()
 * @method $this                  setAutoCastSuffix($string) {
 *     自動キャストの目印文字列を設定する
 *
 *     自動キャストがなにかは {@link ryunosuke\dbml\ dbml} を参照。
 *     デフォルトは "@" なので 'hoge_id@integer' とすると int 型で返すようになる。
 *
 *     この設定はクエリビルダに依存しない。 fetchXXX 系メソッドで生クエリを指定しても変換を行うことができる。
 *
 *     @param string $string 自動キャストの目印文字列
 * }
 * @method callable               getYamlParser()
 * @method $this                  setYamlParser($callable) {
 *     yaml パーサを設定する
 *
 *     デフォルトは Symfony\Yaml を使用するようになっているが**依存には含まれていない**。
 *     よってそのまま使う場合は composer.json の require に "symfony/yaml" を追加する必要がある。
 *
 *     symfony ではなくエクステンションのパーサを使いたい場合は下記のようにする。
 *
 *     ```php
 *     # エクステンションの yaml_parse を使う
 *     $db->setYamlParser(function ($yamlstring) {
 *         return yaml_parse($yamlstring);
 *     });
 *
 *     # 所詮 callable 判定なので複雑な指定がないならこれでも良い
 *     $db->setYamlParser('yaml_parse');
 *
 *     # エクステンションでも symfony でもないサードパーティのものを使う
 *     $db->setYamlParser(function ($yamlstring) {
 *         return \third\vendor\Yaml::parse($yamlstring);
 *     });
 *     ```
 *
 *     @param callable $callable yaml パーサ
 * }
 * @method array                  getAutoCastType()
 * @nethod self                   setAutoCastType($array) 実際に定義している
 * @method string                 getInjectCallStack()
 * @method $this                  setInjectCallStack(string|array $string)
 * @method bool                   getMasterMode()
 * @method $this                  setMasterMode($bool)
 * @method string                 getCheckSameColumn()
 * @method $this                  setCheckSameColumn($string) {
 *     同名カラムをどのように扱うか設定する
 *
 *     | 指定          | 説明
 *     |:--            |:--
 *     | null          | 同名カラムに対して何もしない。PDO のデフォルトの挙動（後ろ優先）となる
 *     | "noallow"     | 同名カラムを検出したら即座に例外を投げるようになる
 *     | "strict"      | 同名カラムを検出したら値を確認し、全て同一値ならその値をカラム値とする。一つでも異なる値がある場合は例外を投げる
 *     | "loose"       | ↑と同じだが、比較は緩く行われる（文字列比較）。更に null は除外してチェックする
 *
 *     普通に PDO を使う分には SELECT 句の後ろにあるほど優先されて返ってくる（仕様で規約されているかは知らん）。
 *     それはそれで便利なんだが、例えばよくありそうな `name` カラムがある2つのテーブルを JOIN すると意図しない結果になることが多々ある。
 *
 *     ```php
 *     $db->fetchArray('select t_article.*, t_user.* from t_article join t_user using (user_id)');
 *     ```
 *
 *     このクエリは `t_user.name` と `t_article.name` というカラムが存在すると破綻する。大抵の場合は `t_user.name` が返ってくるが明確に意図した結果ではない。
 *     このオプションを指定するとそういった状況を抑止することができる。
 *
 *     ただし、このオプションはフェッチ結果の全行全列を確認する必要があるため**猛烈に遅い**。
 *     基本的には開発時に指定し、本運用環境では null を指定しておくと良い。
 *
 *     ただ開発時でも、 "noallow" の使用はおすすめできない。
 *     例えば↑のクエリは user_id で using しているように、name 以外に user_id カラムも重複している。したがって "noallow" を指定すると例外が飛ぶことになる。
 *     往々にして主キーと外部キーは同名になることが多いので、 "noallow" を指定しても実質的には使い物にならない。
 *
 *     これを避けるのが "strict" で、これを指定すると同名カラムの値が同値であればセーフとみなす。つまり動作に影響を与えずある程度良い感じにチェックしてくれるようになる。
 *     さらに "loose" を指定すると NULL を除外して重複チェックする。これは LEFT JOIN 時に効果を発揮する（LEFT 時は他方が NULL になることがよくあるため）。
 *     "loose" は文字列による緩い比較になってしまうが、型が異なる状況はそこまで多くないはず。
 *
 *     なお、フェッチ値のチェックであり、クエリレベルでは何もしないことに注意。
 *     例えば↑のクエリで "strict" のとき「**たまたま** `t_user.name` と `t_article.name` が同じ値だった」ケースは検出できない。また、「そもそもフェッチ行が0だった」場合も検出できない。
 *     このオプションはあくまで開発をサポートする機能という位置づけである。
 *
 *     @param string $string [null | "noallow" | "strict" | "loose"]
 * }
 * @method array                  getAnywhereOption()
 * @method $this                  setAnywhereOption($array)
 * @method array                  getExportClass()
 * @method $this                  setExportClass($array)
 *
 * @method string                 getDefaultIteration() {{@link TableGateway::getDefaultIteration()} 参照@inheritdoc TableGateway::getDefaultIteration()}
 * @method $this                  setDefaultIteration($iterationMode) {{@link TableGateway::setDefaultIteration()} 参照@inheritdoc TableGateway::setDefaultIteration()}
 * @method string                 getDefaultJoinMethod() {{@link TableGateway::getDefaultJoinMethod()} 参照@inheritdoc TableGateway::getDefaultJoinMethod()}
 * @method $this                  setDefaultJoinMethod($string) {{@link TableGateway::setDefaultJoinMethod()} 参照@inheritdoc TableGateway::setDefaultJoinMethod()}
 *
 * @method bool                   getAutoOrder() {{@link QueryBuilder::getAutoOrder()} 参照@inheritdoc QueryBuilder::getAutoOrder()}
 * @method $this                  setAutoOrder($bool) {{@link QueryBuilder::setAutoOrder()} 参照@inheritdoc QueryBuilder::setAutoOrder()}
 * @method string                 getPrimarySeparator() {{@link QueryBuilder::getPrimarySeparator()} 参照@inheritdoc QueryBuilder::getPrimarySeparator()}
 * @method $this                  setPrimarySeparator($string) {{@link QueryBuilder::setPrimarySeparator()} 参照@inheritdoc QueryBuilder::setPrimarySeparator()}
 * @method string                 getAggregationDelimiter() {{@link QueryBuilder::getAggregationDelimiter()} 参照@inheritdoc QueryBuilder::getAggregationDelimiter()}
 * @method $this                  setAggregationDelimiter($string) {{@link QueryBuilder::setAggregationDelimiter()} 参照@inheritdoc QueryBuilder::setAggregationDelimiter()}
 * @method bool                   getInjectChildColumn() {{@link QueryBuilder::getInjectChildColumn()} 参照@inheritdoc QueryBuilder::getInjectChildColumn()}
 * @method $this                  setInjectChildColumn($bool) {{@link QueryBuilder::setInjectChildColumn()} 参照@inheritdoc QueryBuilder::setInjectChildColumn()}
 *
 * @method bool                   getLazyClonable() {{@link SubqueryBuilder::getLazyClonable()} 参照@inheritdoc SubqueryBuilder::getLazyClonable()}
 * @method $this                  setLazyClonable($bool) {{@link SubqueryBuilder::setLazyClonable()} 参照@inheritdoc SubqueryBuilder::setLazyClonable()}
 * @method bool                   getPropagateLockMode() {{@link SubqueryBuilder::getPropagateLockMode()} 参照@inheritdoc SubqueryBuilder::getPropagateLockMode()}
 * @method $this                  setPropagateLockMode($bool) {{@link SubqueryBuilder::setPropagateLockMode()} 参照@inheritdoc SubqueryBuilder::setPropagateLockMode()}
 *
 * @method array|Entityable[]     fetchArrayOrThrow($sql, $params = []) {
 *     <@uses Database::fetchArray()> の例外送出版
 * }
 * @method array|Entityable[]     fetchAssocOrThrow($sql, $params = []) {
 *     <@uses Database::fetchAssoc()> の例外送出版
 * }
 * @method array                  fetchListsOrThrow($sql, $params = []) {
 *     <@uses Database::fetchLists()> の例外送出版
 * }
 * @method array                  fetchPairsOrThrow($sql, $params = []) {
 *     <@uses Database::fetchPairs()> の例外送出版
 * }
 * @method array|Entityable|false fetchTupleOrThrow($sql, $params = []) {
 *     <@uses Database::fetchTuple()> の例外送出版
 * }
 * @method mixed                  fetchValueOrThrow($sql, $params = []) {
 *     <@uses Database::fetchValue()> の例外送出版
 * }
 *
 * @method array|Entityable[]     selectArray($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::select()> の array 版（{@link fetchArray()} も参照）
 * }
 * @method array|Entityable[]     selectAssoc($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::select()> の assoc 版（{@link fetchAssoc()} も参照）
 * }
 * @method array                  selectLists($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::select()> の lists 版（{@link fetchLists()} も参照）
 * }
 * @method array                  selectPairs($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::select()> の pairs 版（{@link fetchPairs()} も参照）
 * }
 * @method array|Entityable|false selectTuple($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::select()> の tuple 版（{@link fetchTuple()} も参照）
 * }
 * @method mixed                  selectValue($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::select()> の value 版（{@link fetchValue()} も参照）
 * }
 *
 * @method array|Entityable[]     selectArrayInShare($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::selectArray()> の共有ロック版（{@link fetchArray()} も参照）
 * }
 * @method array|Entityable[]     selectAssocInShare($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::selectAssoc()> の共有ロック版（{@link fetchAssoc()} も参照）
 * }
 * @method array                  selectListsInShare($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::selectLists()> の共有ロック版（{@link fetchLists()} も参照）
 * }
 * @method array                  selectPairsInShare($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::selectPairs()> の共有ロック版（{@link fetchPairs()} も参照）
 * }
 * @method array|Entityable|false selectTupleInShare($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::selectTuple()> の共有ロック版（{@link fetchTuple()} も参照）
 * }
 * @method mixed                  selectValueInShare($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::selectValue()> の共有ロック版（{@link fetchValue()} も参照）
 * }
 *
 * @method array|Entityable[]     selectArrayForUpdate($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::selectArray()> の排他ロック版（{@link fetchArray()} も参照）
 * }
 * @method array|Entityable[]     selectAssocForUpdate($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::selectAssoc()> の排他ロック版（{@link fetchAssoc()} も参照）
 * }
 * @method array                  selectListsForUpdate($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::selectLists()> の排他ロック版（{@link fetchLists()} も参照）
 * }
 * @method array                  selectPairsForUpdate($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::selectPairs()> の排他ロック版（{@link fetchPairs()} も参照）
 * }
 * @method array|Entityable|false selectTupleForUpdate($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::selectTuple()> の排他ロック版（{@link fetchTuple()} も参照）
 * }
 * @method mixed                  selectValueForUpdate($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::selectValue()> の排他ロック版（{@link fetchValue()} も参照）
 * }
 *
 * @method array|Entityable[]     selectArrayOrThrow($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::selectArray()> の例外送出版（{@link fetchArray()} も参照）
 * }
 * @method array|Entityable[]     selectAssocOrThrow($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::selectAssoc()> の例外送出版（{@link fetchAssoc()} も参照）
 * }
 * @method array                  selectListsOrThrow($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::selectLists()> の例外送出版（{@link fetchLists()} も参照）
 * }
 * @method array                  selectPairsOrThrow($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::selectPairs()> の例外送出版（{@link fetchPairs()} も参照）
 * }
 * @method array|Entityable|false selectTupleOrThrow($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::selectTuple()> の例外送出版（{@link fetchTuple()} も参照）
 * }
 * @method mixed                  selectValueOrThrow($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::selectValue()> の例外送出版（{@link fetchValue()} も参照）
 * }
 *
 * @method array|Entityable[]     entityArray($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::entity()> の array 版
 * }
 * @method array|Entityable[]     entityAssoc($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::entity()> の assoc 版
 * }
 * @method array|Entityable|false entityTuple($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::entity()> の tuple 版
 * }
 *
 * @method array|Entityable[]     entityArrayInShare($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::entityArray()> の共有ロック版
 * }
 * @method array|Entityable[]     entityAssocInShare($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::entityAssoc()> の共有ロック版
 * }
 * @method array|Entityable|false entityTupleInShare($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::entityTuple()> の共有ロック版
 * }
 *
 * @method array|Entityable[]     entityArrayForUpdate($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::entityArray()> の排他ロック版
 * }
 * @method array|Entityable[]     entityAssocForUpdate($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::entityAssoc()> の排他ロック版
 * }
 * @method array|Entityable|false entityTupleForUpdate($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::entityTuple()> の排他ロック版
 * }
 *
 * @method array|Entityable[]     entityArrayOrThrow($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::entityArray()> の例外送出版
 * }
 * @method array|Entityable[]     entityAssocOrThrow($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::entityAssoc()> の例外送出版
 * }
 * @method array|Entityable       entityTupleOrThrow($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     <@uses Database::entityTuple()> の例外送出版
 * }
 *
 * @method Yielder                yieldArray($sql, $params = []) {
 *     <@uses Database::yield()> のレコードの配列版
 * }
 * @method Yielder                yieldAssoc($sql, $params = []) {
 *     <@uses Database::yield()> のレコードの連想配列版
 * }
 * @method Yielder                yieldLists($sql, $params = []) {
 *     <@uses Database::yield()> のレコード[1列目の配列]
 * }
 * @method Yielder                yieldPairs($sql, $params = []) {
 *     <@uses Database::yield()> のレコード[1列目=>2列目]の連想配列
 * }
 *
 * @method int                    exportArray($sql, $params = [], array $config = [], $file = null) {
 *     <@uses Database::export()> の ARRAY 版
 * }
 * @method int                    exportCsv($sql, $params = [], array $config = [], $file = null) {
 *     <@uses Database::export()> の CSV 版
 * }
 * @method int                    exportJson($sql, $params = [], array $config = [], $file = null) {
 *     <@uses Database::export()> の JSON 版
 * }
 *
 * @method SubqueryBuilder        subselectArray($child_columns, $tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     結合列を指定して子供レコード（array）を表すサブビルダを返す（<@uses Database::subselect()> を参照）
 * }
 * @method SubqueryBuilder        subselectAssoc($child_columns, $tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     結合列を指定して子供レコード（assoc）を表すサブビルダを返す（<@uses Database::subselect()> を参照）
 * }
 * @method SubqueryBuilder        subselectLists($child_columns, $tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     結合列を指定して子供レコード（lists）を表すサブビルダを返す（<@uses Database::subselect()> を参照）
 * }
 * @method SubqueryBuilder        subselectPairs($child_columns, $tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     結合列を指定して子供レコード（pairs）を表すサブビルダを返す（<@uses Database::subselect()> を参照）
 * }
 * @method SubqueryBuilder        subselectTuple($child_columns, $tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     結合列を指定して子供レコード（tuple）を表すサブビルダを返す（<@uses Database::subselect()> を参照）
 * }
 * @method SubqueryBuilder        subselectValue($child_columns, $tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     結合列を指定して子供レコード（value）を表すサブビルダを返す（<@uses Database::subselect()> を参照）
 * }
 *
 * @method SubqueryBuilder        subArray($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     外部キーを利用して子供レコード（array）を表すサブビルダを返す（<@uses Database::subselect()> を参照）
 * }
 * @method SubqueryBuilder        subAssoc($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     外部キーを利用して子供レコード（assoc）を表すサブビルダを返す（<@uses Database::subselect()> を参照）
 * }
 * @method SubqueryBuilder        subLists($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     外部キーを利用して子供レコード（lists）を表すサブビルダを返す（<@uses Database::subselect()> を参照）
 * }
 * @method SubqueryBuilder        subPairs($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     外部キーを利用して子供レコード（pairs）を表すサブビルダを返す（<@uses Database::subselect()> を参照）
 * }
 * @method SubqueryBuilder        subTuple($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     外部キーを利用して子供レコード（tuple）を表すサブビルダを返す（<@uses Database::subselect()> を参照）
 * }
 * @method SubqueryBuilder        subValue($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     外部キーを利用して子供レコード（value）を表すサブビルダを返す（<@uses Database::subselect()> を参照）
 * }
 *
 * @method SubqueryBuilder        subtableArray($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     親キーをテーブル指定とみなして子供レコード（array）を表すサブビルダを返す（<@uses Database::subtable()> を参照）
 * }
 * @method SubqueryBuilder        subtableAssoc($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     親キーをテーブル指定とみなして子供レコード（assoc）を表すサブビルダを返す（<@uses Database::subtable()> を参照）
 * }
 * @method SubqueryBuilder        subtableLists($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     親キーをテーブル指定とみなして子供レコード（lists）を表すサブビルダを返す（<@uses Database::subtable()> を参照）
 * }
 * @method SubqueryBuilder        subtablePairs($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     親キーをテーブル指定とみなして子供レコード（pairs）を表すサブビルダを返す（<@uses Database::subtable()> を参照）
 * }
 * @method SubqueryBuilder        subtableTuple($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     親キーをテーブル指定とみなして子供レコード（tuple）を表すサブビルダを返す（<@uses Database::subtable()> を参照）
 * }
 * @method SubqueryBuilder        subtableValue($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     親キーをテーブル指定とみなして子供レコード（value）を表すサブビルダを返す（<@uses Database::subtable()> を参照）
 * }
 *
 * @method QueryBuilder           subcount($column, $where = []) {
 *     相関サブクエリの COUNT を表すビルダを返す（<@uses Database::subaggregate()> を参照）
 * }
 * @method QueryBuilder           submin($column, $where = []) {
 *     相関サブクエリの MIN を表すビルダを返す（<@uses Database::subaggregate()> を参照）
 * }
 * @method QueryBuilder           submax($column, $where = []) {
 *     相関サブクエリの MAX を表すビルダを返す（<@uses Database::subaggregate()> を参照）
 * }
 * @method QueryBuilder           subsum($column, $where = []) {
 *     相関サブクエリの SUM を表すビルダを返す（<@uses Database::subaggregate()> を参照）
 * }
 * @method QueryBuilder           subavg($column, $where = []) {
 *     相関サブクエリの AVG を表すビルダを返す（<@uses Database::subaggregate()> を参照）
 * }
 *
 * @method int|float              count($column, $where = [], $groupBy = [], $having = []) {
 *     COUNT クエリを実行する（<@uses Database::aggregate()> を参照）
 * }
 * @method int|float              min($column, $where = [], $groupBy = [], $having = []) {
 *     MIN クエリを実行する（<@uses Database::aggregate()> を参照）
 * }
 * @method int|float              max($column, $where = [], $groupBy = [], $having = []) {
 *     MAX クエリを実行する（<@uses Database::aggregate()> を参照）
 * }
 * @method int|float              sum($column, $where = [], $groupBy = [], $having = []) {
 *     SUM クエリを実行する（<@uses Database::aggregate()> を参照）
 * }
 * @method int|float              avg($column, $where = [], $groupBy = [], $having = []) {
 *     AVG クエリを実行する（<@uses Database::aggregate()> を参照）
 * }
 *
 * @method QueryBuilder           selectCount($column, $where = [], $groupBy = [], $having = []) {
 *     COUNT クエリを返す（<@uses Database::selectAggregate()> を参照）
 * }
 * @method QueryBuilder           selectMin($column, $where = [], $groupBy = [], $having = []) {
 *     MIN クエリを返す（<@uses Database::selectAggregate()> を参照）
 * }
 * @method QueryBuilder           selectMax($column, $where = [], $groupBy = [], $having = []) {
 *     MAX クエリを返す（<@uses Database::selectAggregate()> を参照）
 * }
 * @method QueryBuilder           selectSum($column, $where = [], $groupBy = [], $having = []) {
 *     SUM クエリを返す（<@uses Database::selectAggregate()> を参照）
 * }
 * @method QueryBuilder           selectAvg($column, $where = [], $groupBy = [], $having = []) {
 *     AVG クエリを返す（<@uses Database::selectAggregate()> を参照）
 * }
 *
 * @method array                  insertOrThrow($tableName, $data) {
 *     <@uses Database::insert()> の例外送出版
 * }
 * @method array                  updateOrThrow($tableName, $data, array $identifier = []) {
 *     <@uses Database::update()> の例外送出版
 * }
 * @method array                  deleteOrThrow($tableName, array $identifier = []) {
 *     <@uses Database::delete()> の例外送出版
 * }
 * @method array                  removeOrThrow($tableName, array $identifier = []) {
 *     <@uses Database::remove()> の例外送出版
 * }
 * @method array                  destroyOrThrow($tableName, array $identifier = []) {
 *     <@uses Database::destroy()> の例外送出版
 * }
 * @method array                  reduceOrThrow($tableName, $limit = null, $orderBy = [], $groupBy = [], $identifier = []) {
 *     <@uses Database::reduce()> の例外送出版
 * }
 * @method array                  upsertOrThrow($tableName, $insertData, $updateData = null) {
 *     <@uses Database::upsert()> の例外送出版
 * }
 * @method array                  modifyOrThrow($tableName, $insertData, $updateData = null) {
 *     <@uses Database::modify()> の例外送出版
 * }
 * @method array                  replaceOrThrow($tableName, $data) {
 *     <@uses Database::replace()> の例外送出版
 * }
 *
 * @method array                  insertIgnore($tableName, $data) {
 *     IGNORE 付き <@uses Database::insert()>
 * }
 * @method array                  updateIgnore($tableName, $data, array $identifier = []) {
 *     IGNORE 付き <@uses Database::update()>
 * }
 * @method array                  deleteIgnore($tableName, array $identifier = []) {
 *     IGNORE 付き <@uses Database::delete()>
 * }
 * @method array                  removeIgnore($tableName, array $identifier = []) {
 *     IGNORE 付き <@uses Database::remove()>
 * }
 * @method array                  destroyIgnore($tableName, array $identifier = []) {
 *     IGNORE 付き <@uses Database::destroy()>
 * }
 * @method array                  modifyIgnore($tableName, $insertData, $updateData = null) {
 *     IGNORE 付き <@uses Database::modify()>
 * }
 *
 * @method array                  insertConditionally($tableName, $condition, $data) {
 *     条件付き <@uses Database::insert()>
 *
 *     $condition が WHERE 的判定され、合致しないときは insert が行われない。
 *     $condition が配列の場合は $tableName で selectNotExists する。つまり「存在しないとき実行」となる。
 *
 *     実行したら主キー配列を返し、されなかったら空配列を返す。
 *
 *     @param QueryBuilder|string|array $condition WHERE 条件 or Select オブジェクト
 * }
 * @method array                  upsertConditionally($tableName, $condition, $insertData, $updateData = null) {
 *     条件付き <@uses Database::upsert()>
 *
 *     $condition が WHERE 的判定され、合致しないときは upsert が行われない。
 *     $condition が配列の場合は $tableName で selectNotExists する。つまり「存在しないとき実行」となる。
 *
 *     実行したら主キー配列を返し、されなかったら空配列を返す。
 *
 *     @param QueryBuilder|string|array $condition WHERE 条件 or Select オブジェクト
 * }
 * @method array                  modifyConditionally($tableName, $condition, $insertData, $updateData = null) {
 *     条件付き <@uses Database::modify()>
 *
 *     $condition が WHERE 的判定され、合致しないときは modify が行われない。
 *     $condition が配列の場合は $tableName で selectNotExists する。つまり「存在しないとき実行」となる。
 *
 *     実行したら主キー配列を返し、されなかったら空配列を返す。
 *
 *     @param QueryBuilder|string|array $condition WHERE 条件 or Select オブジェクト
 * }
 *
 * @method Statement              prepareSelect($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []) {
 *     クエリビルダ構文で SELECT 用プリペアドステートメント取得する（<@uses Database::prepare()>, <@uses Database::select()> も参照）
 * }
 * @method Statement              prepareInsert($tableName, $data) {
 *     クエリビルダ構文で INSERT 用プリペアドステートメント取得する（<@uses Database::prepare()>, <@uses Database::insert()> も参照）
 * }
 * @method Statement              prepareUpdate($tableName, $data, array $identifier = []) {
 *     クエリビルダ構文で UPDATE 用プリペアドステートメント取得する（<@uses Database::prepare()>, <@uses Database::update()> も参照）
 * }
 * @method Statement              prepareDelete($tableName, array $identifier = []) {
 *     クエリビルダ構文で DELETE 用プリペアドステートメント取得する（<@uses Database::prepare()>, <@uses Database::delete()> も参照）
 * }
 * @method Statement              prepareModify($tableName, $insertData, $updateData = null) {
 *     クエリビルダ構文で MODIFY 用プリペアドステートメント取得する（<@uses Database::prepare()>, <@uses Database::modify()> も参照）
 * }
 * @method Statement              prepareReplace($tableName, $data) {
 *     クエリビルダ構文で REPLACE 用プリペアドステートメント取得する（<@uses Database::prepare()>, <@uses Database::replace()> も参照）
 * }
 */
// @formatter:on
class Database
{
    use OptionTrait;

    /// 内部的に自動付加されるカラム名
    public const AUTO_PRIMARY_KEY = '__dbml_auto_pk';
    public const AUTO_CHILD_KEY   = '__dbml_auto_ck';

    /** @var array 内部的に自動付加されるカラム名 */
    public const AUTO_KEYS = [
        self::AUTO_PRIMARY_KEY,
        self::AUTO_CHILD_KEY,
    ];

    /// perform メソッド
    public const METHOD_ARRAY = 'array';
    public const METHOD_ASSOC = 'assoc';
    public const METHOD_LISTS = 'lists';
    public const METHOD_PAIRS = 'pairs';
    public const METHOD_TUPLE = 'tuple';
    public const METHOD_VALUE = 'value';

    /// perform メソッド配列
    public const METHODS = [
        self::METHOD_ARRAY => [],
        self::METHOD_ASSOC => [],
        self::METHOD_LISTS => [],
        self::METHOD_PAIRS => [],
        self::METHOD_TUPLE => [],
        self::METHOD_VALUE => [],
    ];

    /** @var array JOIN 記号のマッピング */
    public const JOIN_MAPPER = [
        'AUTO'  => '~',
        'INNER' => '+',
        'LEFT'  => '<',
        'RIGHT' => '>',
        'CROSS' => '*',
    ];

    /** @var Connection[] */
    private $connections;

    /** @var Connection */
    private $txConnection;

    /** @var \ArrayObject 「未初期化なら生成して返す」系のメソッドのキャッシュ */
    private $cache;

    public static function getDefaultOptions()
    {
        $default_options = [
            // キャッシュオブジェクト
            'cacheProvider'        => new ArrayCache(),
            // 初期化後の SQL コマンド（mysql@PDO でいう MYSQL_ATTR_INIT_COMMAND）
            'initCommand'          => null,
            // デフォルトエンティティクラス名
            'defaultEntity'        => [Entity::class => function ($database) { return [$database]; }],
            // 存在しないテーブル名指定をカラム名とみなすか（QB::column を参照）
            'notableAsColumn'      => false, // このオプションは互換性維持であり将来的にデフォルト true になるか削除される
            // 拡張 INSERT SET 構文を使うか否か（mysql 以外は無視される）
            'insertSet'            => false,
            // insert 時などにテーブルに存在しないカラムを自動でフィルタするか否か
            'filterNoExistsColumn' => true,
            // insert 時などに NULLABLE NUMERIC カラムは 空文字を null として扱うか否か
            'convertEmptyToNull'   => true,
            // 取得時にサフィックス(columnname@integer)で自動キャストする時の区切り文字
            'autoCastSuffix'       => null,
            // 埋め込み条件の yaml パーサ
            'yamlParser'           => (function () {
                // for compatible 1.0
                if (class_exists('\\Symfony\\Component\\Yaml\\Yaml')) {
                    return '\\Symfony\\Component\\Yaml\\Yaml::parse'; // @codeCoverageIgnoreStart
                }
                return function ($yaml) { return paml_import($yaml)[0]; };
            })(),
            // DB型で自動キャストする型設定。select,affect 要素を持つ（多少無駄になるがサンプルも兼ねて冗長に記述してある）
            'autoCastType'         => [
                // 正式な与え方。select は取得（SELECT）時、affect は設定（INSERT/UPDATE）時を表す
                // 個人的には DATETIME で設定したい。出すときは DateTime で返ってくれると便利だけど、入れるときは文字列で入れたい
                'hoge'         => [
                    'select' => true,
                    'affect' => false,
                ],
                // 短縮記法。select/affect が両方 true ならこのように true だけでも良い
                'fuga'         => true,
                // 共に false。実質的に与えていないのと同じで単に明示するだけ
                Type::DATE     => [
                    'select' => false,
                    'affect' => false,
                ],
                // 共に false の短縮記法。やはり単に明示するだけ
                Type::DATETIME => false,
                // このようにクロージャを与えると Type::convertTo(PHP|Database)Value の代わりのこれらが呼ばれるようになる
                // なお、クロージャの $this は「その Type」でバインドされる
                'piyo'         => [
                    'select' => function ($value, AbstractPlatform $platform) { return Type::getType('string')->convertToPHPValue($value, $platform); },
                    'affect' => function ($value, AbstractPlatform $platform) { return Type::getType('string')->convertToDatabaseValue($value, $platform); }
                ],
                // いずれにせよ上記の hoge,fuga,piyo のようにそもそも Type::hasType ではないものは無視される
            ],
            // 同名カラムがあった時どう振る舞うか[null, 'noallow', 'strict', 'loose']
            'checkSameColumn'      => null,
            // SQL 実行時にコールスタックを埋め込むか(パスを渡す。!プレフィックスで否定、 null で無効化)
            'injectCallStack'      => null,
            // 更新クエリを実行せずクエリ文字列を返すようにするか
            'dryrun'               => false,
            // 更新クエリを実行せずプリペアされたステートメントを返すようにするか
            'preparing'            => false,
            // 参照系クエリをマスターで実行するか(「スレーブに書き込みたい」はまずあり得ないが「マスターから読み込みたい」はままある)
            'masterMode'           => false,
            // anywhere のデフォルトオプション
            'anywhereOption'       => [
                // そもそも有効か否か
                'enable'         => true,
                // 強欲にマッチさせるか（false にすると文字列 LIKE されづらくなる）
                'greedy'         => true,
                // 数値マッチをキー系に限定するか（true にすると主キー・外部キーカラムしか見なくなる）
                'keyonly'        => false,
                // 文字列 LIKE 時の collate
                'collate'        => '',
                // 一意化のためのコメント文字列（false 相当でコメントが埋め込まれなくなる）
                'comment'        => 'anywhere',
                // マッチタイプ
                'type'           => null,
                // テーブルごとの設定（サンプルも兼ねるのでマッチしないであろうテーブル名を記述してある）
                "\0hoge-table\0" => [
                    'enable'          => true,
                    'greedy'          => true,
                    'keyonly'         => false,
                    'collate'         => '',
                    'comment'         => 'anywhere',
                    'type'            => null,
                    // カラムごとの設定（サンプルも兼ねるのでマッチしないであろうカラム名を記述してある）
                    "\0hoge-column\0" => [
                        'enable'  => true,
                        'collate' => '',
                        'comment' => 'anywhere',
                        'type'    => 'integer',
                    ],
                ],
            ],
            // exportXXX 呼び出し時にどのクラスを使用するか
            'exportClass'          => [
                'array' => '\\ryunosuke\\dbml\\Generator\\ArrayGenerator',
                'csv'   => '\\ryunosuke\\dbml\\Generator\\CsvGenerator',
                'json'  => '\\ryunosuke\\dbml\\Generator\\JsonGenerator',
            ],
            // ロギングオブジェクト（SQLLogger）
            'logger'               => null,
            // テーブル名 => Entity クラス名のコンバータ
            'entityMapper'         => null,
            // テーブル名 => Gateway クラス名のコンバータ
            'gatewayMapper'        => null,
        ];

        // 他クラスのオプションをマージ
        $default_options += TableGateway::getDefaultOptions();
        $default_options += QueryBuilder::getDefaultOptions();
        $default_options += SubqueryBuilder::getDefaultOptions();
        $default_options += array_each(Transaction::getDefaultOptions(), function (&$carry, $v, $k) {
            // Transaction のオプション名は簡易すぎるので "transaction" を付与する
            $carry['transaction' . ucfirst($k)] = $v;
        }, []);

        return $default_options;
    }

    /**
     * コンストラクタ
     *
     * 設定配列 or \Doctrine\DBAL\Connection を与えてインスタンスを生成する。
     * 設定配列は \Doctrine\DBAL\DriverManager::getConnection に与える配列に準拠するが、いずれかの要素を配列にすると 0 がマスター、1 がスレーブとなる。
     * コネクションは配列で与えることができる。配列を与えた場合、 0 がマスター、1 がスレーブとなる。
     *
     * 基本的には配列を推奨する。コネクション指定は手元に \Doctrine\DBAL\Connection のインスタンスがあるなど、いかんともしがたい場合に使用する。
     *
     * {@link https://www.doctrine-project.org/projects/doctrine-dbal/en/2.7/reference/configuration.html 設定配列はドライバによって異なる}が、 mysql で例を挙げると下記。
     *
     * ```php
     * # mysql のよくありそうな例
     * $db = new Database([
     *     'driver'        => 'pdo_mysql',
     *     'host'          => '127.0.0.1',
     *     'port'          => 3306,
     *     'user'          => 'username',
     *     'password'      => 'password',
     *     'dbname'        => 'test_dbml',
     *     'charset'       => 'utf8',
     *     'driverOptions' => [
     *         \PDO::ATTR_EMULATE_PREPARES  => false,
     *         \PDO::ATTR_STRINGIFY_FETCHES => false,
     *     ],
     * ]);
     * ```
     *
     * いくつかのパターンを混じえた指定例は下記。
     *
     * ```php
     * # 単純な配列を与えた場合（単一コネクション）
     * $db = new Database([
     *     'driver' => 'pdo_mysql',
     *     'host'   => '127.0.0.1',
     *     'port'   => 3306,
     *     'dbname' => 'example',
     * ]);
     * // mysql://127.0.0.1:3306/example の単一コネクションが生成される
     *
     * # 設定配列のいずれかの要素が配列の場合（マスター/スレーブ構成）
     * $db = new Database([
     *     'driver' => 'pdo_mysql',
     *     'host'   => ['127.0.0.1', '127.0.0.2'],
     *     'port'   => [3306, 3307],
     *     'dbname' => 'example',
     * ]);
     * // 下記の2コネクションが生成される（つまり、配列で複数指定したものは個別指定が活き、していないものは共通として扱われる）
     * // - master: mysql://127.0.0.1:3306/example
     * // - slave:  mysql://127.0.0.2:3307/example
     *
     * # コネクションを与える場合
     * $db = new Database($connection);
     * // 単一コネクションが使用される
     *
     * $db = new Database([$connection1, $connection2]);
     * // $connection1 がマスター、$connection2 がスレーブとして使用される
     * ```
     *
     * 第2引数のオプションは getDefaultOptions で与えるものを指定する。
     * 基本的には未指定でもそれなりに動作するが、 cacheProvider だけは明示的に与えたほうが良い。
     *
     * さらに、このクラスのオプションは少し特殊で、 {@link QueryBuilder} や {@link TableGateway} のオプションも複合で与えることができる。
     * その場合、**そのクラスのインスタンスが生成されたときのデフォルト値**として作用する。
     *
     * ```php
     * # autoOrder は本来 QueryBuilder のオプションだが、 Database のオプションとしても与えることができる
     * $db = new Database($dbconfig, [
     *     'autoOrder' => false,
     * ]);
     * $db->selectArray('tablename'); // 上記で false を設定してるので自動で `ORDER BY 主キー` は付かない
     * ```
     *
     * つまり実質的には「本ライブラリの設定が全て可能」となる。あまり「この設定はどこのクラスに係るのか？」は気にしなくて良い。
     *
     * @param array|Connection|Connection[] $dbconfig 設定配列
     * @param array $options オプション配列
     */
    public function __construct($dbconfig, $options = [])
    {
        if ($dbconfig instanceof Connection) {
            $connections = array_fill(0, 2, $dbconfig);
        }
        elseif (!is_array($dbconfig)) {
            throw new \DomainException('$dbconfig must be Connection or Database config array.');
        }
        elseif (array_all($dbconfig, function ($v) { return $v instanceof Connection; })) {
            $connections = array_pad($dbconfig, 2, reset($dbconfig));
        }
        else {
            $master = $slave = $dbconfig;
            foreach ($dbconfig as $key => $value) {
                if (is_array($value) && isset($value[0], $value[1])) {
                    $master[$key] = $value[0];
                    $slave[$key] = $value[1];
                }
            }
            if ($master === $slave) {
                $connections = array_fill(0, 2, DriverManager::getConnection($dbconfig));
            }
            else {
                $connections = [DriverManager::getConnection($master), DriverManager::getConnection($slave)];
            }
        }

        if (count(array_unique(array_maps($connections, func_method('getDriver'), func_method('getName')))) !== 1) {
            throw new \DomainException('master and slave connection are must be same platform.');
        }
        $this->connections = array_combine(['master', 'slave'], $connections);
        $this->txConnection = $this->getMasterConnection();
        $this->cache = new \ArrayObject();

        $this->setDefault($options);

        /** @var Connection[] $cons $connections は同一インスタンスが混ざっていて複数設定されてしまうので unique する */
        $cons = array_each($this->connections, function (&$carry, $v) { $carry[spl_object_hash($v)] = $v; }, []);
        foreach ($cons as $con) {
            $logger = $this->getUnsafeOption('logger');
            if ($logger) {
                $con->getConfiguration()->setSQLLogger($logger);
            }

            $commands = (array) $this->getUnsafeOption('initCommand');
            foreach ($commands as $command) {
                if ($command) {
                    $con->getEventManager()->addEventListener(Events::postConnect, new SQLSessionInit($command));
                }
            }
        }

        // デフォルト兼サンプルの正規化の必要があるので無駄に呼んでおく
        $this->setAutoCastType($this->getAutoCastType());
    }

    /**
     * ゲートウェイオブジェクトがあるかを返す
     *
     * @param string $name テーブル名
     * @return bool ゲートウェイオブジェクトがあるなら true
     */
    public function __isset($name)
    {
        return $this->$name !== null;
    }

    /**
     * ゲートウェイオブジェクトを伏せる
     *
     * @param string $name テーブル名
     * @return void
     */
    public function __unset($name)
    {
        unset($this->cache['gateway'][$name]);
    }

    /**
     * ゲートウェイオブジェクトを返す
     *
     * テーブル名 or （設定されているなら）エンティティ名で {@link TableGateway} を取得する。
     *
     * ```php
     * # t_article の全レコードを取得する
     * $db->t_article->array();
     * ```
     *
     * @param string $name ゲートウェイ名（テーブル名 or エンティティ名）
     * @return TableGateway ゲートウェイオブジェクト
     */
    public function __get($name)
    {
        $tablename = $this->convertTableName($name);
        if (!$this->getSchema()->hasTable($tablename)) {
            return null;
        }

        if (!isset($this->cache['gateway'][$name])) {
            $gatewayMapper = $this->getUnsafeOption('gatewayMapper');
            $classname = $gatewayMapper ? $gatewayMapper($tablename) : null ?: TableGateway::class;
            $this->cache['gateway'][$name] = new $classname($this, $tablename, $tablename === $name ? null : $name);
        }
        return $this->cache['gateway'][$name];
    }

    /**
     * サポートされない
     *
     * 将来のために予約されており、呼ぶと無条件で例外を投げる。
     *
     * phpstorm が `$db->tablename[1]['title'] = 'hoge';` のような式で警告を出すのでそれを抑止する目的もある。
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) { throw new \DomainException(__METHOD__ . ' is not supported.'); }

    /**
     * @ignore
     *
     * @param string $name メソッド名
     * @param array $arguments 引数配列
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        // OptionTrait へ移譲
        $result = $this->OptionTrait__callGetSet($name, $arguments, $called);
        if ($called) {
            return $result;
        }

        // aggregate 系
        if (in_array(strtolower($name), ['count', 'min', 'max', 'sum', 'avg'], true)) {
            return $this->aggregate($name, ...$arguments);
        }
        // subaggregate 系
        if (in_array(strtolower($name), ['subcount', 'submin', 'submax', 'subsum', 'subavg'], true)) {
            return $this->subaggregate(str_lchop($name, 'sub', true), ...$arguments);
        }
        // selectAggregate 系
        if (preg_match('/^select(count|min|max|sum|avg)$/ui', $name, $matches)) {
            return $this->selectAggregate($matches[1], ...$arguments);
        }
        // prepare 系
        if (preg_match('/^prepare(.+)$/ui', $name, $matches)) {
            // SELECT 系のみ実装が違うので分岐
            $method = $matches[1];
            if (strtolower($method) === 'select') {
                return $this->prepare($this->select(...$arguments));
            }
            $restorer = $this->storeOptions(['preparing' => true]);
            return try_finally([$this, $method], $restorer, ...$arguments);
        }
        // fetch～OrThrow 系
        if (preg_match('/^fetch(.+?)OrThrow$/ui', $name, $matches)) {
            $method = 'fetch' . $matches[1];
            $result = $this->$method(...$arguments);
            // Value, Tuple は [] を返し得ないし、複数行系も false を返し得ない
            if ($result === [] || $result === false) {
                throw new NonSelectedException('record is not found.');
            }
            return $result;
        }
        // select|entity～ 系
        if (preg_match('/^(select|entity)(.+?)(ForUpdate|InShare)?(OrThrow)?$/ui', $name, $matches)) {
            list(, $mode, $perform, $lockmode, $orthrow) = array_map('strtolower', $matches + [3 => '', 4 => '']);
            $select = $this->$mode(...$arguments);
            if ($lockmode) {
                $select->{"lock$lockmode"}();
            }
            $method = 'fetch' . $perform . $orthrow;
            return $this->$method($select);
        }
        // yield～ 系
        if (preg_match('/^yield(.+?)$/ui', $name, $matches)) {
            return $this->yield(...$arguments)->setFetchMethod(strtolower($matches[1]));
        }
        // export～ 系
        if (preg_match('/^export(.+?)$/ui', $name, $matches)) {
            return $this->export($matches[1], ...$arguments);
        }
        // sub～ 系
        if (preg_match('/^(sub(table|select)?)(.+?)$/ui', $name, $matches)) {
            list(, $submethod, , $perform) = array_map('strtolower', $matches);
            if ($submethod === 'subtable') {
                $subselect = $this->subtable(...$arguments);
            }
            else {
                $subselect = $this->subselect($submethod === 'subselect' ? array_shift($arguments) : null, ...$arguments);
            }
            return $subselect->$perform();
        }
        // affect～OrThrow 系
        if (preg_match('/^(insert|update|delete|remove|destroy|reduce|upsert|modify|replace)OrThrow$/ui', $name, $matches)) {
            $method = strtolower($matches[1]);
            Adhoc::reargument($arguments, [$this, $method], []);
            $arguments[] = ['primary' => 1, 'throw' => true];
            return $this->$method(...$arguments);
        }
        // affect～Ignore 系
        if (preg_match('/^(insert|update|delete|remove|destroy|modify)Ignore$/ui', $name, $matches)) {
            $method = strtolower($matches[1]);
            Adhoc::reargument($arguments, [$this, $method], []);
            $arguments[] = ['primary' => 2, 'ignore' => true];
            return $this->$method(...$arguments);
        }
        // affect～Conditionally 系
        if (preg_match('/^(insert|upsert|modify)Conditionally$/ui', $name, $matches)) {
            $method = strtolower($matches[1]);
            $opt = Adhoc::reargument($arguments, [$this, $method], [1 => 'where']);
            $arguments[] = ['primary' => 2] + $opt;
            return $this->$method(...$arguments);
        }

        // Gateway 取得
        if ($gateway = $this->$name) {
            if ($arguments) {
                if (filter_var($arguments[0], \FILTER_VALIDATE_INT) !== false) {
                    return $gateway->find($arguments[0]);
                }

                $gateway = $gateway->scoping(...$arguments);
            }
            return $gateway;
        }

        throw new \BadMethodCallException("'$name' is undefined.");
    }

    /**
     * __debugInfo
     *
     * いろいろ統括していて情報量が多すぎるので出力を絞る。
     *
     * @see http://php.net/manual/ja/language.oop5.magic.php#object.debuginfo
     *
     * @return array var_dump されるプロパティ
     */
    public function __debugInfo()
    {
        $classname = __CLASS__;
        $properties = (array) $this;
        unset($properties["\0$classname\0connections"]); // 旨味が少ない（有益な情報があまりない）
        unset($properties["\0$classname\0txConnection"]); // connections と同じ
        unset($properties["\0$classname\0cache"]); // 不要（個別に見れば良い）
        return $properties;
    }

    private function _entityMap()
    {
        $entityMapper = $this->getUnsafeOption('entityMapper');
        if (!is_callable($entityMapper)) {
            return null;
        }

        /** @var CacheProvider $cacher */
        $cacher = $this->getUnsafeOption('cacheProvider');
        $map = $cacher->fetch('@entityMap');
        if ($map === false) {
            $map = [
                'class' => [],
                'TtoE'  => [],
                'EtoT'  => [],
            ];
            foreach ($this->getSchema()->getTableNames() as $tablename) {
                $entityclass = $entityMapper($tablename);
                if ($entityclass === null) {
                    continue;
                }
                $entityname = class_shorten($entityclass);
                $map['class'][$entityname] = $entityclass;

                // テーブル名とエンティティ名が一致してはならない
                if (isset($map['TtoE'][$tablename]) || isset($map['EtoT'][$tablename])) {
                    throw new \DomainException("'$tablename' is already defined.");
                }
                $map['TtoE'][$tablename] = $entityname;

                // 同じエンティティ名があってはならない
                if (isset($map['EtoT'][$entityname]) || isset($map['TtoE'][$entityname])) {
                    throw new \DomainException("'$entityname' is already defined.");
                }
                $map['EtoT'][$entityname] = $tablename;
            }
            $cacher->save('@entityMap', $map);
        }

        return $map;
    }

    private function _doFetch($sql, $params, $method)
    {
        $sub_flg = ($sql instanceof SubqueryBuilder && $sql->isRequireUnsetSubcolumn());
        $converter = $this->_getConverter($sql);
        $revert = $this->_toTablePrefix($sql);
        try {
            $stmt = $this->_sqlToStmt($sql, $params, $this->getSlaveConnection());
            return $this->perform($stmt, $method, $converter, $sub_flg);
        }
        finally {
            $revert();
        }
    }

    private function _sqlToStmt($sql, array $params, Connection $connection)
    {
        if ($sql instanceof Statement) {
            $stmt = $sql->executeQuery($params, $connection);
        }
        elseif ($sql instanceof QueryBuilder) {
            $stmt = $sql->getPreparedStatement();
            if ($stmt) {
                $stmt = $stmt->executeQuery($params, $connection);
            }
            else {
                $stmt = $this->executeQuery($this->_builderToSql($sql, $params), $params);
            }
        }
        else {
            $stmt = $this->executeQuery($sql, $params);
        }
        $stmt->setFetchMode($this->getCheckSameColumn() ? \PDO::FETCH_NAMED : \PDO::FETCH_ASSOC);

        return $stmt;
    }

    private function _builderToSql($builder, array &$fetch_params)
    {
        // QueryBuilder なら文字列化 && $params を置換
        if ($builder instanceof QueryBuilder) {
            $builder_params = $builder->getParams();
            // $builder も params を持っていて fetch の引数も指定されていたらどっちを使えばいいのかわからない
            if (count($fetch_params) > 0 && count($builder_params) > 0) {
                throw new \UnexpectedValueException('specified parameter both $builder and fetch argument.');
            }
            if ($builder_params) {
                $fetch_params = $builder_params;
            }

            $builder->detectAutoOrder(true);
            return (string) $builder;
        }

        return $builder;
    }

    private function _getConverter($data_source)
    {
        $platform = $this->getPlatform();
        $cast_type = $this->getUnsafeOption('autoCastType');
        $cast_suffix = $this->getUnsafeOption('autoCastSuffix');
        $samecheck_method = $this->getUnsafeOption('checkSameColumn');
        $samecheck = function ($c, $vv) use ($samecheck_method) {
            if ($samecheck_method === 'noallow') {
                throw new \UnexpectedValueException("columns '$c' is same column or alias (cause $samecheck_method).");
            }
            elseif ($samecheck_method === 'strict') {
                $v = array_pop($vv);
                if (!in_array($v, $vv, true)) {
                    throw new \UnexpectedValueException("columns '$c' is same column or alias (cause $samecheck_method).");
                }
                return $v;
            }
            elseif ($samecheck_method === 'loose') {
                if (count(array_unique(array_filter($vv, function ($s) { return $s !== null; }))) > 1) {
                    throw new \UnexpectedValueException("columns '$c' is same column or alias (cause $samecheck_method).");
                }
                return end($vv);
            }
            else {
                throw new \DomainException("checkSameColumn is invalid.");
            }
        };

        // フェッチモードを変えるのでこの段階で取得しておかないと describe にまで影響が出てしまう
        /** @var Type[][] $table_columns */
        $table_columns = $cast_type ? array_each($this->getSchema()->getTableNames(), function (&$carry, $tname) {
            $carry[$tname] = [];
            foreach ($this->getSchema()->getTableColumns($tname) as $cname => $column) {
                $carry[$tname][$cname] = $column->getType();
            }
            foreach ($this->$tname->virtualColumn(null, 'type') as $cname => $column) {
                $carry[$tname][$cname] = $column;
            }
        }, []) : [];

        /** @var QueryBuilder $data_source */
        $data_source = optional($data_source, QueryBuilder::class);
        $alias_table = array_lookup($data_source->getFromPart() ?: [], 'table');
        $rowconverter = $data_source->getCaster();

        return function ($row) use ($platform, $cast_suffix, $cast_type, $table_columns, $alias_table, $rowconverter, $samecheck_method, $samecheck) {
            $newrow = [];
            foreach ($row as $c => $v) {
                if ($samecheck_method && is_array($v)) {
                    $v = $samecheck($c, $v);
                }

                if ($cast_suffix) {
                    $parts = explode($cast_suffix, $c, 2);
                    if (count($parts) === 2) {
                        list($ct, $type) = $parts;
                        if (Type::hasType($type)) {
                            $c = $ct;
                            $v = Type::getType($type)->convertToPHPValue($v, $platform);
                        }
                    }
                }

                if ($cast_type) {
                    $parts = explode('.', $c, 3);
                    $pcount = count($parts);
                    if ($pcount >= 2) {
                        // mysql の場合は3個来ることがある（暗黙＋明示＋列名）。その場合は最初を捨てて明示を優先する
                        if ($pcount === 3) {
                            list(, $table, $c) = $parts;
                        }
                        else {
                            list($table, $c) = $parts;
                        }

                        // クエリビルダ経由でエイリアスマップが得られるなら変換ができる
                        if (isset($alias_table[$table])) {
                            $table = $alias_table[$table];
                        }

                        // カラムが存在するなら convert
                        if (isset($table_columns[$table][$c])) {
                            $type = $table_columns[$table][$c];
                            $typename = $type->getName();
                            if (isset($cast_type[$typename]['select'])) {
                                $converter = $cast_type[$typename]['select'];
                                if ($converter instanceof \Closure) {
                                    $v = $converter($v, $platform);
                                }
                                else {
                                    $v = $type->convertToPHPValue($v, $platform);
                                }
                            }
                        }

                        if ($samecheck_method && array_key_exists($c, $newrow)) {
                            $v = $samecheck($c, [$newrow[$c], $v]);
                        }
                    }
                }

                $newrow[$c] = $v;
            }
            if ($rowconverter) {
                $newrow = $rowconverter($newrow);
            }
            return $newrow;
        };
    }

    private function _toTablePrefix($sql)
    {
        // そういうモードではないならそもそも何もしない
        if (!$this->getUnsafeOption('autoCastType')) {
            return function () { };
        }

        // @codeCoverageIgnoreStart
        // \PDO::ATTR_FETCH_TABLE_NAMES をサポートしてるならそれで（そっちのほうが汎用性が高い）
        if ($this->getCompatiblePlatform()->supportsTableNameAttribute()) {
            $pdo = $this->getSlavePdo();
            $pdo->setAttribute(\PDO::ATTR_FETCH_TABLE_NAMES, true);
            return function () use ($pdo) {
                $pdo->setAttribute(\PDO::ATTR_FETCH_TABLE_NAMES, false);
            };
        }
        // @codeCoverageIgnoreEnd

        // \PDO::ATTR_FETCH_TABLE_NAMES が対応していなくても QueryBuilder 経由ならそれっぽいことはできる
        if ($sql instanceof QueryBuilder) {
            $sql->setAutoTablePrefix(true);
            return function () use ($sql) {
                $sql->setAutoTablePrefix(false);
            };
        }

        // どうしようもない
        return function () { };
    }

    private function _getCallStack($filter_path)
    {
        // パスでフィルタするクロージャ
        $filter_paths = arrayize($filter_path);
        $match_path = function ($path) use ($filter_paths) {
            foreach ($filter_paths as $filter_path) {
                // クロージャ
                if ($filter_path instanceof \Closure) {
                    if (!$filter_path($path)) {
                        return false;
                    }
                }
                // パスの1文字目が!の場合は否定
                elseif ($filter_path[0] === '!') {
                    if (strpos($path, substr($filter_path, 1)) !== false) {
                        return false;
                    }
                }
                elseif (strpos($path, $filter_path) === false) {
                    return false;
                }
            }
            return true;
        };

        $cplatform = $this->getCompatiblePlatform();

        // mysql のクエリログは trim してから行われるようなので位置揃えのためにそれっぽい文字列を入れておく
        $traces = [$cplatform->commentize("callstack:")];
        foreach (debug_backtrace() as $trace) {
            if (isset($trace['file'], $trace['line'])) {
                if ($match_path($trace['file'])) {
                    $traces[] = $cplatform->commentize($trace['file'] . '#' . $trace['line']);
                }
            }
        }

        // magic call が多く、同ファイル同行が頻出するため unique する
        return array_unique($traces);
    }

    private function _normalize($table, $row)
    {
        // これはメソッド冒頭に記述し、決して場所を移動しないこと
        $columns = $this->getSchema()->getTableColumns($table);
        $autocolumn = optional($this->getSchema()->getTableAutoIncrement($table))->getName();

        if ($row instanceof Entityable) {
            $row = $row->arrayize();
        }

        if ($this->getUnsafeOption('preparing')) {
            $row = array_each($row, function (&$carry, $v, $k) {
                // for compatible. もともと "name" で ":name" のような bind 形式になる仕様だったが ":name" で明示する仕様になった
                if (is_int($k) && is_string($v)) {
                    $k = ltrim($v, ':'); // substr($v, 1);
                    $v = $this->raw(":$k");
                }
                $carry[$k] = $v;
            }, []);
        }

        if ($this->getUnsafeOption('filterNoExistsColumn')) {
            $row = array_intersect_key($row, $columns);
        }

        if ($this->getUnsafeOption('convertEmptyToNull')) {
            // 対象としない型（要するに文字列系）
            $targets = [Type::STRING => true, Type::TEXT => true, Type::BINARY => true, Type::BLOB => true];
            foreach ($columns as $cname => $column) {
                // NULLABLE のみ対象で・・・
                if (!$column->getNotnull()) {
                    // 値が空文字で・・・
                    if (array_key_exists($cname, $row) && $row[$cname] === '') {
                        // 文字列系以外の型なら null にする
                        if (!isset($targets[$column->getType()->getName()])) {
                            $row[$cname] = null;
                        }
                    }
                }
            }
        }

        if ($types = $this->getUnsafeOption('autoCastType')) {
            $vtypes = $this->$table->virtualColumn(null, 'type');
            foreach ($columns as $cname => $column) {
                $type = $vtypes[$cname] ?? $column->getType();
                $typename = $type->getName();
                if (isset($types[$typename]['affect'])) {
                    if ($converter = $types[$typename]['affect']) {
                        if (array_key_exists($cname, $row)) {
                            if ($converter instanceof \Closure) {
                                $row[$cname] = $converter($row[$cname], $this->getPlatform());
                            }
                            else {
                                $row[$cname] = $type->convertToDatabaseValue($row[$cname], $this->getPlatform());
                            }
                        }
                    }
                }
            }
        }

        // mysql は null を指定すれば自動採番されるが、他の RDBMS では伏せないと採番されないようだ
        if ($autocolumn && !isset($row[$autocolumn])) {
            unset($row[$autocolumn]);
        }

        return $row;
    }

    private function _normalizes($table, $rows, $unique_cols = null)
    {
        $columns = $this->getSchema()->getTableColumns($table);
        if ($unique_cols === null) {
            $unique_cols = $this->getSchema()->getTablePrimaryColumns($table);
        }
        else {
            $unique_cols = array_flip($unique_cols);
        }
        $singleuk = count($unique_cols) === 1 ? first_key($unique_cols) : null;

        $params = array_fill_keys(array_keys($columns), []);
        $pvals = [];
        $result = [];
        foreach ($rows as $n => $row) {
            if (!is_array($row)) {
                throw new \InvalidArgumentException('$data\'s element must be array.');
            }

            foreach ($unique_cols as $pcol => $dummy) {
                if (!isset($row[$pcol])) {
                    throw new \InvalidArgumentException('$data\'s must be contain primary key.');
                }
                if (!is_scalar($row[$pcol])) {
                    throw new \InvalidArgumentException('$data\'s primary key must be scalar value.');
                }
            }

            $row = $this->_normalize($table, $row);

            foreach ($columns as $col => $val) {
                if (!array_key_exists($col, $row)) {
                    continue;
                }
                if (isset($unique_cols[$col])) {
                    $pvals[$col][] = $row[$col];
                    continue;
                }

                if ($singleuk) {
                    $pv = $this->bindInto($row[$singleuk], $params[$col]);
                }
                else {
                    $pv = [];
                    foreach ($unique_cols as $pcol => $dummy) {
                        $pv[] = $pcol . ' = ' . $this->bindInto($row[$pcol], $params[$col]);
                    }
                    $pv = implode(' AND ', $pv);
                }
                $tv = $this->bindInto($row[$col], $params[$col]);
                $result[$col][] = "WHEN $pv THEN $tv";
            }
        }

        $cols = [];
        foreach ($result as $column => $exprs) {
            $cols[$column] = $this->raw('CASE ' . concat($singleuk ?: '', ' ') . implode(' ', $exprs) . " ELSE $column END", $params[$column]);
        }

        return $pvals + $cols;
    }

    private function _preaffect($tableName, $data)
    {
        if (is_callable($data)) {
            $data = $data();
            if (!$data instanceof \Generator) {
                throw new \InvalidArgumentException('"$data" must return Generator instance.');
            }
            return [$tableName => $data];
        }

        if (is_string($tableName) && str_contains($tableName, array_merge(Database::JOIN_MAPPER, [',', '.']))) {
            $tableName = array_each(TableDescriptor::forge($this, $tableName, []), function (&$carry, TableDescriptor $td) {
                $carry[$td->descriptor] = $td->column;
            }, []);
        }
        if (is_string($tableName) && str_contains($tableName, TableDescriptor::META_CHARACTORS)) {
            $tableName = TableDescriptor::forge($this, $tableName, []);
        }
        if (is_array($tableName)) {
            $data = arrayize($data);
            if ($data && !is_hasharray($data)) {
                if (count($tableName) !== 1) {
                    throw new \InvalidArgumentException('don\'t specify multiple table.');
                }
                list($tableName, $columns) = first_keyvalue($tableName);
                if (count($columns) !== count($data)) {
                    throw new \InvalidArgumentException('specified column and data array are difference.');
                }
                return [$tableName => array_combine($columns, $data)];
            }

            return $this->select($tableName);
        }

        return $tableName;
    }

    private function _postaffect($tableName, $data)
    {
        foreach ($data as $k => $v) {
            $kk = str_lchop($k, "$tableName.");
            if (!isset($data[$kk])) {
                $data[$kk] = $v;
            }
        }
        $pcols = $this->getSchema()->getTablePrimaryColumns($tableName);
        $primary = array_intersect_key($data, $pcols);
        $autocolumn = optional($this->getSchema()->getTableAutoIncrement($tableName))->getName();
        if ($autocolumn && !isset($primary[$autocolumn])) {
            $primary[$autocolumn] = $this->getLastInsertId($tableName, $autocolumn);
        }
        // Expression や QueryBuilder はどうしようもないのでクエリを投げて取得
        // 例えば modify メソッドの列に↑のようなオブジェクトが来てかつ UPDATE された場合、lastInsertId は得られない
        foreach ($primary as $cname => $val) {
            if (is_object($val)) {
                return $this->selectTuple([$tableName => array_keys($pcols)], $primary);
            }
        }
        return $primary;
    }

    private function _prewhere($tableName, $identifier)
    {
        $tableAlias = null;
        if (is_array($tableName)) {
            list($tableAlias, $tableName) = first_keyvalue($tableName);
        }

        return array_map(function ($cond) use ($tableName, $tableAlias) {
            if ($cond instanceof QueryBuilder && $cond->getSubmethod() !== null) {
                $cond->setSubwhere($tableName, $tableAlias);
            }
            return $cond;
        }, arrayize($identifier));
    }

    /**
     * スキーマオブジェクトを返す
     *
     * 「スキーマオブジェクト」とは \Doctrine\DBAL\Schema\Schema だけのことではなく、一般的な「スキーマオブジェクト」を表す。
     * （もっとも、それらのオブジェクトを返すので言うなれば「スキーマオブジェクトオブジェクト」か）。
     *
     * 返し得るオブジェクトは5種類。下記のサンプルを参照。
     *
     * ```php
     * # \Doctrine\DBAL\Schema\Schema を返す
     * $schema = $db->describe(); // 引数省略時は Schema オブジェクト
     *
     * # \Doctrine\DBAL\Schema\Table を返す
     * $table = $db->describe('tablename'); // テーブル名を与えると Table オブジェクト
     * $view = $db->describe('viewname'); // ビュー名を与えても Table オブジェクト
     *
     * # \Doctrine\DBAL\Schema\ForeignKeyConstraint を返す
     * $fkey = $db->describe('fkeyname'); // 外部キー名を与えると ForeignKeyConstraint オブジェクト
     *
     * # \Doctrine\DBAL\Schema\Column を返す
     * $column = $db->describe('tablename.columnname'); // テーブル名.カラム名を与えると Column オブジェクト
     *
     * # \Doctrine\DBAL\Schema\Index を返す
     * $index = $db->describe('tablename.indexname'); // テーブル名.インデックス名を与えると Index オブジェクト
     * ```
     *
     * オブジェクト名が競合している場合は何が返ってくるか未定義。
     *
     * @param string $objectname オブジェクト名
     * @return AbstractAsset スキーマオブジェクト
     */
    public function describe($objectname = null)
    {
        $schema = $this->getSchema();

        if (!strlen($objectname)) {
            return $this->getSlaveConnection()->getSchemaManager()->createSchema();
        }

        list($parentname, $childname) = explode('.', $objectname) + [1 => null];
        if (isset($childname)) {
            if ($schema->hasTable($parentname)) {
                $table = $schema->getTable($parentname);
                if ($table->hasColumn($childname)) {
                    return $table->getColumn($childname);
                }
                if ($table->hasIndex($childname)) {
                    return $table->getIndex($childname);
                }
            }
        }
        else {
            if ($schema->hasTable($parentname)) {
                return $schema->getTable($parentname);
            }
            $fkeys = $schema->getForeignKeys();
            if (isset($fkeys[$parentname])) {
                return $fkeys[$parentname];
            }
        }

        throw new \InvalidArgumentException("undefined schema object '$objectname'");
    }

    /**
     * コード補完用のアノテーションコメントを取得する
     *
     * 存在するテーブル名や entityMapper、gatewayMapper などを利用してアノテーションコメントを作成する。
     * このメソッドで得られたコメントを基底クラスなどに貼り付ければ補完が効くようになる。
     *
     * @param string|array 除外テーブル名（fnmatch で除外される）
     * @return string アノテーションコメント
     */
    public function getAnnotation($ignore = [])
    {
        $classess = [];
        foreach ($this->getSchema()->getTableNames() as $tname) {
            $ename = $this->convertEntityName($tname);
            if ($ignore && fnmatch_or($ignore, $tname)) {
                continue;
            }
            $classess[$tname] = '\\' . get_class($this->$tname);
            if ($ignore && fnmatch_or($ignore, $ename)) {
                continue;
            }
            $classess[$ename] = '\\' . get_class($this->$ename);
        }
        if (!$classess) {
            return null;
        }
        $maxlen = max(array_map('strlen', $classess));
        $result = [];
        foreach ($classess as $name => $class) {
            $result[] = sprintf("* @property %-{$maxlen}s \$%s", $class, $name);
            $result[] = sprintf("* @method   %-{$maxlen}s %s", $class, $name) . '($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])';
        }
        return implode("\n", $result) . "\n";
    }

    /**
     * コード補完用のアノテーショントレイトを取得する
     *
     * 存在するテーブル名や entityMapper、gatewayMapper などを利用して mixin 用のトレイトを作成する。
     * このメソッドが吐き出したトレイトを `@ mixin Hogera` などとすると補完が効くようになる。
     *
     * @param string $namespace トレイト群の名前空間。未指定だとグローバル
     * @param string $filename ファイルとして吐き出す先
     * @return string アノテーションコメント
     */
    public function echoAnnotation($namespace = null, $filename = null)
    {
        $args1 = '$tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []';
        $args2 = '$variadic_primary, $tableDescriptor = []';

        $typeMap = [
            Type::TARRAY               => 'array|string',
            Type::SIMPLE_ARRAY         => 'array|string',
            Type::JSON_ARRAY           => 'array|string',
            Type::JSON                 => 'array|string',
            Type::OBJECT               => 'object|string',
            Type::BOOLEAN              => 'bool',
            Type::INTEGER              => 'int',
            Type::SMALLINT             => 'int',
            Type::BIGINT               => 'int|string',
            Type::DECIMAL              => 'float',
            Type::FLOAT                => 'float',
            Type::STRING               => 'string',
            Type::TEXT                 => 'string',
            Type::BINARY               => 'string',
            Type::BLOB                 => 'string',
            Type::GUID                 => 'string',
            Type::DATETIME             => '\\DateTime|string',
            Type::DATETIME_IMMUTABLE   => '\\DateTimeImmutable|string',
            Type::DATETIMETZ           => '\\DateTime|string',
            Type::DATETIMETZ_IMMUTABLE => '\\DateTimeImmutable|string',
            Type::DATE                 => '\\DateTime|string',
            Type::DATE_IMMUTABLE       => '\\DateTimeImmutable|string',
            Type::TIME                 => '\\DateTime|string',
            Type::TIME_IMMUTABLE       => '\\DateTimeImmutable|string',
            Type::DATEINTERVAL         => '\\DateInterval|string',
        ];

        $database = [];
        $gateway = [];
        $gateways = [];
        $entities = [];
        foreach ($this->getSchema()->getTableNames() as $tname) {
            $ename = $this->convertEntityName($tname);
            $eclass = ltrim($this->getEntityClass($tname), '\\');
            $classess = [
                $tname => '\\' . get_class($this->$tname),
                $ename => '\\' . get_class($this->$ename),
            ];
            foreach ($classess as $name => $class) {
                $database[] = sprintf(" * @property %s \$%s", $class, $name);
                $database[] = sprintf(" * @method   %s %s", $class, $name) . "($args1)";

                $gateway[] = sprintf(" * @property %s \$%s", $class, $name);
                $gateway[] = sprintf(" * @method   %s %s", '$this', $name) . "($args1)";
            }
            if ($ename !== $tname) {
                $gateways["{$ename}TableGateway"] = [
                    " * @mixin TableGateway
 * @method array|\\{$eclass}[]     array($args1)
 * @method array|\\{$eclass}[]     assoc($args1)
 * @method array|\\{$eclass}|false tuple($args1)
 * @method array|\\{$eclass}|false find($args2)
 * @method array|\\{$eclass}[]     arrayInShare($args1)
 * @method array|\\{$eclass}[]     assocInShare($args1)
 * @method array|\\{$eclass}|false tupleInShare($args1)
 * @method array|\\{$eclass}|false findInShare($args2)
 * @method array|\\{$eclass}[]     arrayForUpdate($args1)
 * @method array|\\{$eclass}[]     assocForUpdate($args1)
 * @method array|\\{$eclass}|false tupleForUpdate($args1)
 * @method array|\\{$eclass}|false findForUpdate($args2)
 * @method array|\\{$eclass}[]     arrayOrThrow($args1)
 * @method array|\\{$eclass}[]     assocOrThrow($args1)
 * @method array|\\{$eclass}       tupleOrThrow($args1)
 * @method array|\\{$eclass}       findOrThrow($args2)",
                ];
                $entities["{$ename}Entity"] = array_map(function (Column $column) use ($typeMap) {
                    $typename = $column->getType()->getName();
                    return sprintf(" * @property %s \$%s", $typeMap[$typename] ?? $typename, $column->getName());
                }, $this->getSchema()->getTableColumns($tname));
            }
        }

        $gen = function ($comments, $name) {
            $comments = implode("\n", $comments);
            return "/**\n$comments\n */\ntrait $name{}\n";
        };
        $namespace = $namespace ? "\nnamespace $namespace;\n" : '';
        $result = "<?php\n$namespace\n// this code auto generated.\n\n// @formatter:off\n\n";
        $result .= $gen($database, "Database") . "\n";
        $result .= $gen($gateway, "TableGateway") . "\n";
        $result .= array_sprintf($gateways, $gen, "\n") . "\n";
        $result .= array_sprintf($entities, $gen, "\n");

        if ($filename) {
            file_set_contents($filename, $result);
        }
        return $result;
    }

    /**
     * カラムの型に応じた自動変換処理を登録する
     *
     * 自動変換がなにかは {@link ryunosuke\dbml\ dbml} を参照。
     *
     * ```php
     * $db->setAutoCastType([
     *     // DATETIME 型は「取得時は変換」「更新時はそのまま」となるように設定
     *     Type::DATETIME => [
     *         'select' => true,
     *         'affect' => false,
     *     ],
     *     // SARRAY 型は「取得時も更新時も変換」となるように設定（単一 bool を与えると select,affect の両方を意味する）
     *     Type::SIMPLE_ARRAY => true,
     *     // STRING 型はクロージャで変換する
     *     Type::String => [
     *         'select' => function ($colval) {
     *             // $colval に SELECT 時の値が渡ってくる
     *         },
     *         'affect' => function ($colval) {
     *             // $colval に AFFECT 時の値が渡ってくる
     *         },
     *     ],
     * ]);
     * ```
     *
     * @param array $array キャストタイプ配列
     * @return $this 自分自身
     */
    public function setAutoCastType($array)
    {
        $types = [];
        foreach ($array as $type => $opt) {
            if (!Type::hasType($type)) {
                continue;
            }
            // false はシカト（設定されていると見なされてしまうので代入すらしない）
            if ($opt === false) {
                continue;
            }
            // true は両方 true
            if ($opt === true) {
                $opt = [
                    'select' => $opt,
                    'affect' => $opt,
                ];
            }
            // 配列でかつ select,affect を含まなければならない
            if (!is_array($opt) || !isset($opt['select'], $opt['affect'])) {
                throw new \InvalidArgumentException("autoCastType's element must contain ['select', 'affect'] key.");
            }
            // 共に false もシカト
            if (!$opt['select'] && !$opt['affect']) {
                continue;
            }
            // クロージャは $this をバインド（Type は static や引数ベースであり、状態を持たないのでこの段階でバインドできる）
            $typeclass = Type::getType($type);
            if ($opt['select'] instanceof \Closure) {
                $opt['select'] = $opt['select']->bindTo($typeclass, $typeclass);
            }
            if ($opt['affect'] instanceof \Closure) {
                $opt['affect'] = $opt['affect']->bindTo($typeclass, $typeclass);
            }
            $types[$type] = $opt;
        }
        return $this->setOption('autoCastType', $types);
    }

    /**
     * 接続(Connection)を強制的に設定する
     *
     * マスター/スレーブの切り替えにも使用する（bool 値を与えると切り替えとなる）。
     *
     * ```php
     * // 接続をマスターに切り替える
     * $db->setConnection(true);
     * // 接続をスレーブに切り替える
     * $db->setConnection(false);
     * // 全く別個のコネクションに切り替える
     * $db->setConnection($connection);
     * ```
     *
     * @param Connection|bool $connection コネクション or bool（true ならマスター、 false ならスレーブ）
     * @return $this 自分自身
     */
    public function setConnection($connection)
    {
        // bool は特別扱いで true: master, false: slave として扱う
        if (is_bool($connection)) {
            $connection = $connection ? $this->getMasterConnection() : $this->getSlaveConnection();
        }

        // トランザクション中にコネクションの切り替えは事故を招くので禁止する
        if ($this->txConnection !== $connection && $this->txConnection->getTransactionNestingLevel() > 0) {
            throw new \UnexpectedValueException("can't switch connection in duaring transaction.");
        }
        $this->txConnection = $connection;
        return $this;
    }

    /**
     * 現在のトランザクション接続（Connection）を返す
     *
     * トランザクション接続とは基本的に「マスター接続」を指す。
     * シングルコネクション環境なら気にしなくて良い。
     *
     * @return Connection コネクション
     */
    public function getConnection()
    {
        return $this->txConnection;
    }

    /**
     * マスター接続（Connection）を返す
     *
     * @return Connection コネクション
     */
    public function getMasterConnection()
    {
        // Master はマスターを返す
        return $this->connections['master'];
    }

    /**
     * スレーブ接続（Connection）を返す
     *
     * @return Connection コネクション
     */
    public function getSlaveConnection()
    {
        // Slaveは「マスターから読みたい」ことがなくはないので設定ベース
        if ($this->getMasterMode()) {
            return $this->getMasterConnection();
        }
        return $this->connections['slave'];
    }

    /**
     * トランザクション接続の PDO を返す
     *
     * トランザクション接続とは基本的に「マスター接続」を指す。
     * シングルコネクション環境なら気にしなくて良い。
     *
     * @return \PDO PDO オブジェクト
     */
    public function getPdo()
    {
        /** @var \PDO $pdo */
        $pdo = $this->getConnection()->getWrappedConnection();
        return $pdo;
    }

    /**
     * マスター接続の PDO を返す
     *
     * @return \PDO PDO オブジェクト
     */
    public function getMasterPdo()
    {
        /** @var \PDO $pdo */
        $pdo = $this->getMasterConnection()->getWrappedConnection();
        return $pdo;
    }

    /**
     * スレーブ接続の PDO を返す
     *
     * @return \PDO PDO オブジェクト
     */
    public function getSlavePdo()
    {
        /** @var \PDO $pdo */
        $pdo = $this->getSlaveConnection()->getWrappedConnection();
        return $pdo;
    }

    /**
     * PDO の属性を変更する
     *
     * 返り値として「元に戻すためのクロージャ」を返す。
     * この返り値をコールすれば変更した属性を元に戻すことができる。
     *
     * 属性によってはコンストラクタでしか受け付けてくれないものがあるので注意。
     *
     * ```php
     * # 一時的に PDO のエラーモードを SILENT にする
     * $restore = $db->setPdoAttribute([\PDO::ATTR_ERRMODE => \PDO::ERRMODE_SILENT]);
     *
     * # 返り値のクロージャを呼ぶと元に戻る
     * $restore();
     * ```
     *
     * @param array $attributes 設定する属性のペア配列
     * @param array|string $target "master" か "slave" でそちら側のみ変更する。未指定/null で両方変更する
     * @return \Closure 元に戻すためのクロージャ
     */
    public function setPdoAttribute($attributes, $target = null)
    {
        if ($target === null) {
            $target = ['master', 'slave'];
        }
        $target = (array) $target;

        $masterPdo = $this->connections['master']->getWrappedConnection();
        $slavePdo = $this->connections['slave']->getWrappedConnection();

        /** @var \PDO[] $pdos */
        $pdos = [];
        if ($masterPdo === $slavePdo) {
            $pdos['master'] = $masterPdo;
        }
        else {
            $pdos['master'] = $masterPdo;
            $pdos['slave'] = $slavePdo;
        }

        $backup = [];
        foreach ($target as $type) {
            if (isset($pdos[$type])) {
                foreach ($attributes as $name => $value) {
                    $backup[$type][$name] = $pdos[$type]->getAttribute($name);
                    $pdos[$type]->setAttribute($name, $value);
                }
            }
        }

        return function () use ($pdos, $backup) {
            foreach ($backup as $type => $attrs) {
                foreach ($attrs as $name => $value) {
                    $pdos[$type]->setAttribute($name, $value);
                }
            }
        };
    }

    /**
     * {@link AbstractPlatform dbal のプラットフォーム}を取得する
     *
     * @return AbstractPlatform dbal プラットフォーム
     */
    public function getPlatform()
    {
        return $this->getSlaveConnection()->getDatabasePlatform();
    }

    /**
     * {@link CompatiblePlatform 互換用プラットフォーム}を取得する
     *
     * @return CompatiblePlatform 本ライブラリの互換用プラットフォーム
     */
    public function getCompatiblePlatform()
    {
        return $this->cache['compatiblePlatform'] ?? $this->cache['compatiblePlatform'] = new CompatiblePlatform($this->getPlatform());
    }

    /**
     * {@link Schema スキーマオブジェクト}を取得する
     *
     * @return Schema スキーマオブジェクト
     */
    public function getSchema()
    {
        return $this->cache['schema'] ?? $this->cache['schema'] = new Schema($this->connections['slave']->getSchemaManager(), $this->getUnsafeOption('cacheProvider'));
    }

    /**
     * テーブル名からエンティティクラス名を取得する
     *
     * @param string|array $tablename テーブル名
     * @param bool $use_default 見つからなかった場合にデフォルトエンティティクラスを使うか
     * @return string|false エンティティクラス名
     */
    public function getEntityClass($tablename, $use_default = false)
    {
        foreach ((array) $tablename as $tn) {
            $map = $this->_entityMap()['class'];
            $tn = $this->convertEntityName($tn);
            if (isset($map[$tn]) && class_exists($map[$tn])) {
                return $map[$tn];
            }
        }
        if ($use_default) {
            return first_key($this->getDefaultEntity());
        }
        return false;
    }

    /**
     * エンティティクラスのコンストラクタ引数を取得する
     *
     * @return array コンストラクタ引数
     */
    public function getEntityArgument()
    {
        $args = first_value($this->getDefaultEntity());
        if (is_callable($args)) {
            $args = $args($this);
        }
        return $args;
    }

    /**
     * エンティティ名からテーブル名へ変換する
     *
     * @param string $entityname エンティティ名
     * @return string テーブル名
     */
    public function convertTableName($entityname)
    {
        $map = $this->_entityMap()['EtoT'];
        return $map[$entityname] ?? $entityname;
    }

    /**
     * テーブル名からエンティティ名へ変換する
     *
     * @param string $tablename テーブル名
     * @return string エンティティ名
     */
    public function convertEntityName($tablename)
    {
        $map = $this->_entityMap()['TtoE'];
        return $map[$tablename] ?? $tablename;
    }

    /**
     * 外部キーをまとめて追加する
     *
     * addForeignKey を複数呼ぶのとほぼ等しい。
     *
     * ```php
     * # 下記のような配列を与える
     * $db->addRelation([
     *     'ローカルテーブル名' => [
     *         '外部テーブル名' => [
     *             '外部キー名' => [
     *                 'ローカルカラム名1' => '外部カラム名1',
     *                 'ローカルカラム名2' => '外部カラム名2',
     *             ],
     *             // 別キー名に対して上記の構造の繰り返しができる
     *         ],
     *         // 別外部テーブル名に対して上記の構造の繰り返しができる
     *     ],
     *     // 別ローカルテーブル名に対して上記の構造の繰り返しができる
     * ]);
     * ```
     *
     * @param array $relations 外部キー定義
     * @return ForeignKeyConstraint[] 追加した外部キーオブジェクト
     */
    public function addRelation($relations)
    {
        $result = [];
        foreach ($relations as $localTable => $foreignTables) {
            foreach ($foreignTables as $foreignTable => $relation) {
                foreach ($relation as $fkname => $columnsMap) {
                    $fkey = $this->addForeignKey($localTable, $foreignTable, $columnsMap, is_int($fkname) ? null : $fkname);
                    $result[$fkey->getName()] = $fkey;
                }
            }
        }
        return $result;
    }

    /**
     * 外部キーを追加する
     *
     * 簡易性や ForeignKeyConstraint を隠蔽するために用意されている。
     * ForeignKeyConstraint 指定で追加する場合は {@link Schema::addForeignKey()} を呼ぶ。
     *
     * @param string $localTable 外部キー定義テーブル名
     * @param string $foreignTable 参照先テーブル名
     * @param string|array $columnsMap 外部キーカラム
     * @param string|null $fkname 外部キー名。省略時は自動命名
     * @return ForeignKeyConstraint 追加した外部キーオブジェクト
     */
    public function addForeignKey($localTable, $foreignTable, $columnsMap, $fkname = null)
    {
        $columnsMap = Adhoc::to_hash($columnsMap);

        // 省略時は自動命名
        if (!$fkname) {
            $fkname = $localTable . '_' . $foreignTable . '_' . count($this->getSchema()->getTableForeignKeys($localTable));
        }

        // 外部キーオブジェクトの生成
        $fk = new ForeignKeyConstraint(array_keys($columnsMap), $foreignTable, array_values($columnsMap), $fkname);
        $fk->setLocalTable($this->getSchema()->getTable($localTable));

        return $this->getSchema()->addForeignKey($fk);
    }

    /**
     * 外部キーを無効にする
     *
     * 簡易性や ForeignKeyConstraint を隠蔽するために用意されている。
     * ForeignKeyConstraint 指定で無効にする場合は {@link Schema::ignoreForeignKey()}  を呼ぶ。
     *
     * @param string $localTable 外部キー定義テーブル名
     * @param string $foreignTable 参照先テーブル名
     * @param string|array $columnsMap 外部キーカラム
     * @return ForeignKeyConstraint 無効にした外部キーオブジェクト
     */
    public function ignoreForeignKey($localTable, $foreignTable, $columnsMap)
    {
        $columnsMap = Adhoc::to_hash($columnsMap);

        // 外部キーオブジェクトの生成
        $fk = new ForeignKeyConstraint(array_keys($columnsMap), $foreignTable, array_values($columnsMap));
        $fk->setLocalTable($this->getSchema()->getTable($localTable));

        return $this->getSchema()->ignoreForeignKey($fk);
    }

    /**
     * begin
     *
     * {@link Connection::beginTransaction()} の移譲。
     *
     * @return int ネストレベル
     */
    public function begin()
    {
        $this->txConnection->beginTransaction();
        return $this->txConnection->getTransactionNestingLevel();
    }

    /**
     * commit
     *
     * {@link Connection::commit()} の移譲。
     *
     * @return int ネストレベル
     */
    public function commit()
    {
        $this->txConnection->commit();
        return $this->txConnection->getTransactionNestingLevel();
    }

    /**
     * rollback
     *
     * {@link Connection::rollBack()} の移譲。
     *
     * @return int ネストレベル
     */
    public function rollback()
    {
        $this->txConnection->rollBack();
        return $this->txConnection->getTransactionNestingLevel();
    }

    /**
     * コールバックをトランザクションブロックで実行する
     *
     * $options は {@link Transaction} を参照。
     *
     * $throwable は catch で代替可能なので近い将来削除される。
     *
     * ```php
     * // このクロージャ内の処理はトランザクション内で処理される
     * $return = $db->transact(function ($db) {
     *     return $db->insertOrThrow('t_table', ['data array']);
     * });
     * ```
     *
     * @param callable $main メイン処理
     * @param callable $catch 例外発生時の処理。ただし後方互換性のため $options を与えても良い
     * @param array $options トランザクションオプション
     * @param bool $throwable 例外を投げるか返すか
     * @return mixed メイン処理の返り値
     */
    public function transact($main, $catch = null, $options = [], $throwable = true)
    {
        // for compatible
        if (is_bool($options)) {
            $throwable = $options;
            $options = $catch;
        }

        return $this->transaction($main, $catch, $options)->perform($throwable);
    }

    /**
     * トランザクションオブジェクトを返す
     *
     * $options は {@link Transaction} を参照。
     *
     * @param callable $main メイン処理
     * @param callable $catch 例外発生時の処理。ただし後方互換性のため $options を与えても良い
     * @param array $options トランザクションオプション
     * @return Transaction トランザクションオブジェクト
     */
    public function transaction($main = null, $catch = null, $options = [])
    {
        // for compatible
        if (!$catch instanceof \Closure) {
            $options = (array) $catch;
            $catch = null;
        }

        $tx = new Transaction($this, $options);
        if ($main) {
            $tx->main($main);
        }
        if ($catch) {
            $tx->catch($catch);
        }
        return $tx;
    }

    /**
     * トランザクションをプレビューする（実行クエリを返す）
     *
     * $options は {@link Transaction} を参照。
     *
     * この処理は「実際にクエリを投げてロールバックしてログを取る」という機構で実装されている。
     * つまり、トランザクション未対応の RDBMS だと実際にクエリが実行されるし、RDBMS 管轄外の事を行っても無かったことにはならない。
     * RDBMS によっては連番が飛ぶかもしれない。
     *
     * あくまで、開発のサポート（「このメソッドを呼ぶとどうなるんだろう/どういうクエリが投げられるんだろう」など）に留めるべきである。
     *
     * ```php
     * // $logs に実際に投げたクエリが格納される。
     * $logs = $db->preview(function ($db) {
     *     $pk = $db->insertOrThrow('t_table', ['data array']);
     *     $db->update('t_table', ['data array'], $pk);
     * });
     * ```
     *
     * @param callable $main メイン処理
     * @param array|int $options トランザクションオプション
     * @return array トランザクションログ
     */
    public function preview($main, $options = null)
    {
        $tx = $this->transaction($main, $options);
        $tx->preview($logs);
        return $logs;
    }

    /**
     * new {@link Expression} するだけのメソッド
     *
     * 可能なら直接 new Expression せずにこのメソッド経由で生成したほうが良い（MUST ではない）。
     *
     * @param mixed $expr クエリ文
     * @param array $params bind パラメータ
     * @return Expression クエリ表現オブジェクト
     */
    public function raw($expr, $params = [])
    {
        return new Expression($expr, $params);
    }

    /**
     * 引数内では AND、引数間では OR する Expression を返す
     *
     * 得られる結果としては {@link QueryBuilder::where()}とほぼ同じ。
     * ただし、あちらはクエリビルダで WHERE 専用であるのに対し、こちらは Expression を返すだけなので SELECT 句に埋め込むことができる。
     *
     * ```php
     * $db->select([
     *     't_article' => [
     *         'contain_hoge' => $db->operator('article_title:%LIKE%', 'hoge'),
     *     ],
     * ]);
     * // SELECT (article_title LIKE ?) AS contain_hoge FROM t_article: ['%hoge%']
     *
     * $db->select([
     *     't_article' => [
     *         'contain_misc' => $db->operator([
     *             'colA' => 1,
     *             'colB' => 2,
     *         ], [
     *             'colC' => 3,
     *             'colD' => 4,
     *             [
     *                 'colE1' => 5,
     *                 'colE2' => 6,
     *             ]
     *         ]),
     *     ],
     * ]);
     * // SELECT (((colA = ?) AND (colB = ?)) OR ((colC = ?) AND (colD = ?) AND ((colE1 = ?) OR (colE2 = ?)))) AS contain_misc FROM t_article: [1, 2, 3, 4, 5, 6]
     * ```
     *
     * @param array|string $cond クエリ文
     * @param array $params bind パラメータ
     * @return Expression クエリ表現オブジェクト
     */
    public function operator($cond, $params = [])
    {
        $ps = [];
        if (is_array($cond)) {
            $conds = array_map(function ($arg) use (&$ps) {
                return implode(' AND ', Adhoc::wrapParentheses($this->whereInto(arrayize($arg), $ps)));
            }, func_get_args());
            $glue = " OR ";
        }
        else {
            $conds = $this->whereInto([$cond => $params], $ps);
            $glue = " AND ";
        }
        return new Expression('(' . implode($glue, Adhoc::wrapParentheses($conds)) . ')', $ps);
    }

    /**
     * 値をクオートする
     *
     * null を quote すると '' ではなく NULL になる。
     * bool を quote すると文字ではなく int になる。
     *
     * それ以外は {@link Connection::quote()} と同じ。
     *
     * @param mixed $value クオートする値
     * @param string|null $type クオート型
     * @return string|null クオートされた値
     */
    public function quote($value, $type = null)
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return (int) $value;
        }

        return $this->getSlaveConnection()->quote($value, $type);
    }

    /**
     * 識別子をクオートする
     *
     * {@link Connection::quoteIdentifier()} を参照。
     *
     * @param string $identifier 識別子
     * @return string クオートされた識別子
     */
    public function quoteIdentifier($identifier)
    {
        return $this->getSlaveConnection()->quoteIdentifier($identifier);
    }

    /**
     * SQL とパラメータを指定してクエリを構築する
     *
     * ```php
     * # 素の文字列
     * $db->queryInto('SELECT ?', ['xxx']);
     * // SELECT 'xxx'
     *
     * # Expression を与えると保持しているパラメータが使用される
     * $db->queryInto(new Expression('UPPER(?)', ['yyy']));
     * // UPPER('yyy')
     *
     * # Expression というか Queryable 全般がそうなる
     * $db->queryInto($db->select('tablename', ['id' => 1]));
     * // (SELECT tablename.* FROM tablename WHERE id = '1')
     * ```
     *
     * @param string $sql SQL
     * @param array $params bind 配列
     * @return string 値が埋め込まれた実行可能なクエリ
     */
    public function queryInto($sql, $params = [])
    {
        if ($sql instanceof Queryable) {
            $sql = $sql->merge($params);
        }

        // 順次置換
        $posed = $named = [];
        $sql = preg_replace_callback('/(\?)|(:([a-z_][a-z_0-9]*))/ui', function ($m) use ($params, &$posed, &$named) {
            if ($m[1] === '?') {
                $name = count($posed);
                $posed[$name] = true;
            }
            else {
                $name = $m[3];
                $named[$name] = true;
            }
            if (!array_key_exists($name, $params)) {
                throw new \InvalidArgumentException('parameter length is short.');
            }
            return $this->quote($params[$name]);
        }, $sql);

        // 未使用（元の奴から使用した奴を差っ引いて）があるなら例外
        if (array_diff_key($params, $posed + $named)) {
            throw new \InvalidArgumentException('parameter length is long.');
        }

        return $sql;
    }

    /**
     * ? 込みのキー名を正規化する
     *
     * 具体的には引数 $params に bind 値を格納して返り値として（? を含んだ）クエリ文字列を返す。
     *
     * ```php
     * # 単純に文字列で渡す（あまり用途はない）
     * $db->bindInto('col', $params);
     * // results: "?", $params: ['col']
     *
     * # Queryable も渡せる
     * $db->bindInto(new Expression('col', [1]), $params);
     * // results: ['col1'], $params: [1]
     *
     * # 配列で渡す（混在可能。メイン用途）
     * $db->bindInto(['col1' => new Expression('UPPER(?)', [1]), 'col2' => 2], $params);
     * // results: ['col1' => 'UPPER(?)', 'col2' => '?'], $params: [1, 2]
     * ```
     *
     * @param mixed $data ? が含まれている bind 配列
     * @param array $params bind 値が渡される
     * @return mixed ? が埋め込まれた正規化されたクエリ文字列
     */
    public function bindInto($data, array &$params)
    {
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

    /**
     * where を正規化する
     *
     * 基本的に配列を与えることが多いが、値はエスケープされるがキーは一切触らずスルーされるため**キーには決してユーザ由来の値を渡してはならない**。
     * また、トップレベル以下の下層に配列が来ても連想配列とはみなされない（キーを使用しない or 連番で振り直す）。
     *
     * ```php
     * # bad（トップレベルのキーにユーザ入力が渡ってしまう）
     * $db->whereInto($_GET);
     *
     * # better（少なくともトップレベルのキーにユーザ入力が渡ることはない）
     * $db->whereInto([
     *     'colA' => $_GET['colA'],
     *     'colB' => $_GET['colB'],
     * ]);
     * ```
     *
     * | No | where                                          | result                             | 説明
     * | --:|:--                                             |:--                                 |:--
     * |  0 | `''`                                           | -                                  | 値が(phpで)空判定される場合、その条件はスルーされる。空とは `null || '' || [] || 全てが!で除外された QueryBuilder` のこと
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
     * | 25 | `[QueryBuilder]`                               | 略                                 | QueryBuilder の文字列表現をそのまま埋め込む。EXISTS などでよく使用されるが、使い方を誤ると「Operand should contain 1 column(s)」とか「Subquery returns more than 1 row」とか言われるので注意
     * | 26 | `['hoge' => QueryBuilder]`                     | 略                                 | キー付きで QueryBuilder を渡すとサブクエリで条件演算される。左記の場合は `hoge IN (QueryBuilder)` となる
     * | 27 | `[Operator]`                                   | 略                                 | 条件式の代わりに `Operator` インスタンスを渡すことができるが、難解なので説明は省略
     * | 28 | `['hoge' => function () {}]`                   | 略                                 | クロージャを渡すとクロージャの実行結果が「あたかもそこにあるかのように」振る舞う
     *
     * No.9,10 の演算子は `LIKE` や `BETWEEN` 、 `IS NULL` 、範囲指定できる独自の `[~]` 演算子などがある。
     * 組み込みの演算子は {@link Operator} を参照。
     *
     * このメソッドは内部で頻繁に使用される。
     * 具体的には QueryBuilder::select の第2引数、 JOIN の ON 指定、 update/delete などの WHERE 条件など。
     * これらの箇所ではすべて上記の記法が使用できる。
     *
     * ```php
     * # No.22（検索画面などの http 経由(文字列)でパラメータが流れてくる状況で便利）
     * if ($id) {
     *     $wheres['id'] = $id;
     * }
     * $wheres['!id'] = $id; // 上記コードとほぼ同義
     * // 空の定義には「全ての条件が!で除外されたQueryBuilder」も含むので、下記のコードは空の WHERE になる
     * $wheres['!subid IN(?)'] = $db->select('subtable.id', ['!name' => ''])->exists();
     *
     * # No.9,10（ややこしいことをしないで手軽に演算子が埋め込める）
     * $wheres['name:%LIKE%'] = 'hoge';  // WHERE name LIKE "%hoge%"
     * $wheres['period:(~]'] = [0, 100]; // WHERE period > 0 AND period <= 100
     *
     * # No.11（:以降がない場合は No.5～8 になる）
     * $wheres['id'] = 1;        // WHERE id = 1
     * $wheres['id:'] = 1;       // ↑と同じ
     * $wheres['id:!'] = 1;      // 用途なしに見えるが、このように:!とすると WHERE NOT (id = 1) になり、否定が行える
     * $wheres['id:!'] = [1, 2]; // No.5～8 相当なので配列を与えれば WHERE NOT (id IN (1, 2)) になり、IN の否定が行える
     *
     * # No.15（:hoge は hoge = :hoge になる。頻度は低いが下記のように prepare 化するときに指定が楽になる）
     * $stmt = $db->prepareDelete('table_name', ['id = :id']);    // prepare するときは本来ならこのように指定すべきだが・・・
     * $stmt = $db->prepareDelete('table_name', ['id' => ':id']); // このようなミスがよくある（これは id = ":id" に展開されるのでエラーになる）
     * $stmt = $db->prepareDelete('table_name', ':id');           // このように指定したほうが平易で良い。そしてこの時点で id = :id になるので・・・
     * $stmt->executeUpdate(['id' => 1]);                         // WHERE id = 1 で実行できる
     * $stmt->executeUpdate(['id' => 2]);                         // WHERE id = 2 で実行できる
     *
     * # No.23（最高にややこしいが、実用上は「OR する場合は配列で包む」という認識でまず事足りるはず）
     * # 原則として配列間は AND で結合される。しかし、要素を配列で包むと、現在のコンテキストとは逆（AND なら OR、OR なら AND）の演算子で結合させることができる
     * $wheres = [
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
     * $wheres = [
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
     * $wheres = [
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
     * $wheres = [
     *     'delete_flg' => 0,
     *     'NOT' => [
     *         'sei:%LIKE%' => 'hoge',
     *         'mei:%LIKE%' => 'hoge',
     *     ],
     * ];
     * // WHERE (delete_flg = '0') AND (NOT ((sei LIKE '%hoge%') AND (mei LIKE '%hoge%')))
     *
     * # No.25,26（クエリビルダを渡すとそれらしく埋め込まれる）
     * $wheres = [
     *     // ただの EXSISTS クエリになる
     *     $db->select('subtable')->exists(),
     *     // ? なしのデフォルトではサブクエリの IN になる
     *     'subid1' => $db->select('subtable.subid'),
     *     // ? 付きだとそのとおりになる（ここでは = だが <> とか BETWEEN でも同じ。埋め込み演算子も使える）
     *     'subid2 = ?' => $db->select('subtable.subid')->limit(1),
     * ];
     * // WHERE EXISTS(SELECT * FROM subtable) AND (subid1 IN (SELECT subid FROM subtable)) AND (subid2 = (SELECT subid FROM subtable))
     *
     * # No.28（クロージャを使うと三項演算子を駆使する必要はない上、スコープを閉じ込めることができる）
     * $wheres = [
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
     * @param array $identifier where 配列
     * @param array $params bind 値が格納される
     * @param string $andor 結合演算子（内部向け引数なので気にしなくて良い）
     * @param bool $filterd 条件が全て ! などでフィルタされたら true が格納される（内部向け引数なので気にしなくて良い）
     * @return array where 配列
     */
    public function whereInto(array $identifier, array &$params, $andor = 'OR', &$filterd = null)
    {
        $orand = $andor === 'AND' ? 'OR' : 'AND';
        $criteria = [];

        foreach ($identifier as $cond => $value) {
            if ($value instanceof \Closure) {
                $value = $value($this);
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
                        $ors = $this->whereInto([$op => $vs], $params, $orand, $filterd);
                        Adhoc::array_push($cds, implode(" $andor ", Adhoc::wrapParentheses($ors)));
                    }
                    Adhoc::array_push($criteria, implode(" $andor ", Adhoc::wrapParentheses($cds)));
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
                if (isset($cond[0]) && $cond[0] === '!') {
                    if (Adhoc::is_empty($value)) {
                        if ($filterd === null) {
                            $filterd = true;
                        }
                        $filterd = $filterd && true;
                        continue;
                    }
                    $filterd = false;
                    $cond = substr($cond, 1);
                }

                // AND,OR だけは特例処理（カラム指定と曖昧だが "OR" なんて識別子は作れないし指定できないのでOK）
                // 仮に指定するにしても "`OR`" になるはずなので文字列的には一致しない
                $CANDOR = strtoupper($cond);
                if ($CANDOR === 'AND' || $CANDOR === 'OR') {
                    $ors = $this->whereInto(arrayize($value), $params, $CANDOR === 'AND' ? 'OR' : 'AND', $filterd);
                    Adhoc::array_push($criteria, implode(" $CANDOR ", Adhoc::wrapParentheses($ors)));
                    continue;
                }
                // 同じく、NOT も特別扱い
                if ($CANDOR === 'NOT') {
                    $nots = $this->whereInto(arrayize($value), $params, $andor, $filterd);
                    Adhoc::array_push($criteria, 'NOT (' . implode(" $orand ", Adhoc::wrapParentheses($nots)) . ')');
                    continue;
                }
                // Queryable はマージしたものを
                if ($value instanceof Queryable) {
                    if (strpos($cond, '?') === false) {
                        $cond .= $value instanceof QueryBuilder ? ' IN ?' : ' = ?'; // IN のカッコはビルダが付けてくれる
                    }
                    $criteria[] = str_replace('?', $value->merge($params), $cond);
                    continue;
                }
                // 同上。配列の中に Queryable が紛れている場合
                if (Adhoc::containQueryable($value)) {
                    $subquerys = [];
                    $subvalues = [];
                    foreach ($value as $k => $v) {
                        if ($v instanceof Queryable) {
                            $subparams = [];
                            $subquerys[$k] = $v->merge($subparams);
                            foreach ($subparams as $sp) {
                                $subvalues[] = $sp;
                            }
                        }
                        else {
                            $subvalues[] = $v;
                        }
                    }

                    $cond = str_subreplace($cond, '?', $subquerys);
                    $value = $subvalues;
                    $ope = Operator::RAW;
                }
                // :区切りで演算子指定モード
                elseif (strpos($cond, ':') !== false) {
                    list($cond, $ope) = array_map('trim', explode(':', $cond, 2));
                }
                // ? が無いなら column OPERATOR value モード（OPERATOR は型に応じる）
                elseif (strpos($cond, '?') === false) {
                    $ope = Operator::COLVAL;
                }
                // それ以外は素（colA = ? and colB = ? or colC in (?, ?, ?) のような複雑な文字列）
                else {
                    $ope = Operator::RAW;
                }
                $operator = new Operator($this->getCompatiblePlatform(), $ope, $cond, $value);
                if ($operator->getQuery()) {
                    $criteria[] = $operator->merge($params);
                }
            }
        }

        return $criteria;
    }

    /**
     * テーブル名とワードを与えると「なんとなくよしなに検索してくれるだろう」where 配列を返す
     *
     * 具体的には基本的に下記。
     * - 数値は数値カラムで =
     * - 日時っぽいなら日時カラムで BETWEEN
     *     - 足りない部分は最大の範囲で補完
     * - 文字列なら文字列カラムで LIKE
     *     - スペースは%に変換（順序維持 LIKE）
     *
     * 検索オプションは下記。
     * - hoge: テーブルに紐付かないグローバルオプション
     *     - e.g. collate LIKE 時の照合順序
     * - tablename.columnname: テーブルのカラム毎のオプション（使用時のエイリアスではなくテーブル名を指定）
     *     - `['enable' => false]`: 何もしないで無視する
     *     - `['type' => 'datetime']`: このカラムの型名
     *     - `['collate' => 'hoge']`: このカラムの照合順序
     *
     * オプションの優先順位は下記（下に行くほど高い）。
     * - テーブルコメントパース結果
     * - カラムコメントパース結果
     * - Database の anywhereOption オプション
     *
     * ```php
     * # テーブル定義は下記とする
     * # - tablename
     * #   - id: int
     * #   - parent_id: int(外部キー)
     * #   - title: string
     * #   - content: text
     * #   - create_date: datetime
     *
     * # 全ての数値カラムの完全一致検索
     * $db->whereInto($db->anywhere('tablename', 123), $params);
     * // WHERE (id = '123') OR (parent_id = '123')
     *
     * # 全ての文字列カラムの包含検索（スペースは%に変換される）
     * $db->whereInto($db->anywhere('tablename', 'ho ge'), $params);
     * // WHERE (title LIKE '%ho%ge%') OR (content LIKE '%ho%ge%')
     *
     * # 全ての日時カラムの範囲検索
     * $db->whereInto($db->anywhere('tablename', '2000/12/04'), $params);
     * // WHERE (create_date BETWEEN '2000-12-04 00:00:00' AND '2000-12-04 23:59:59')
     *
     * # 上記で 00:00:00 が補完されているのは指定が年月日だからであり、 2000/12 だけを指定すると下記のようになる
     * $db->whereInto($db->anywhere('tablename', '2000/12'), $params);
     * // WHERE (create_date BETWEEN '2000-12-01 00:00:00' AND '2000-12-31 23:59:59')
     * ```
     *
     * 上記のようにまさに「よしなに」検索してくれる機能で、画面の右上に1つの検索窓を配置するような場合に適している。
     * ただし、想像の通り恐ろしく重いクエリとなりがちなので使い所を見極めるのが肝要。
     * 一応少しカラムを減らせるオプションを用意してあるが、説明は省く（そんなに多くないのでソースを直確認を推奨）。
     *
     * もっとも、このメソッド自体を明示的に使うことは少ないと思われる。もっぱら {@link QueryBuilder::where()} で自動的に使われる。
     *
     * @param string $table テーブル名
     * @param string $word 検索ワード
     * @return array where 配列
     */
    public function anywhere($table, $word)
    {
        // クオートの判定（json_decode を使ってるのは手抜きだけど別段問題ないはず）
        $json = json_decode((string) $word);
        $quoted = is_string($json);
        if ($quoted) {
            $word = $json;
        }

        // ! を付けるまでもなく空値は何もしない仕様とする（「よしなに」の定義に"!"も含まれている）
        if (Adhoc::is_empty($word)) {
            return [];
        }

        $schema = $this->getSchema();

        // テーブル名の正規化（tablename as aliasname を受け付ける）
        list($alias, $tname) = Alias::split($table);
        $tname = $this->convertTableName($tname);
        $alias = $alias ?: $tname;

        // オプションを取得しておく
        $goptions = $this->getUnsafeOption('anywhereOption');
        $toptions = $schema->getTableColumnMetadata($tname);

        // リレーションを漁る
        $keys = array_fill_keys($schema->getTablePrimaryKey($tname)->getColumns(), true);
        foreach ($schema->getForeignKeys($tname, null) as $fkey) {
            $keys += array_fill_keys($fkey->getForeignColumns(), true);
        }
        foreach ($schema->getForeignKeys(null, $tname) as $fkey) {
            $keys += array_fill_keys($fkey->getLocalColumns(), true);
        }

        // 検索ワードの正規化
        $is_numeric = $quoted ? false : is_numeric($word);
        $ymdhis = $quoted ? false : Adhoc::parseYmdHis($word);
        if ($quoted) {
            $inwords = '%' . $this->getCompatiblePlatform()->escapeLike($word) . '%';
        }
        else {
            $inwords = '%' . preg_replace('#[\s　]+#u', '%', $this->getCompatiblePlatform()->escapeLike($word)) . '%';
        }

        $where = [];
        foreach ($schema->getTableColumns($tname) as $cname => $column) {
            $voptions = $this->$tname->virtualColumn($cname);
            $coptions = $schema->getTableColumnMetadata($tname, $cname);
            $coptions = array_replace_recursive(
                $goptions,
                $toptions['anywhere'] ?? [],
                $coptions['anywhere'] ?? [],
                $goptions[$tname] ?? [],
                $goptions[$tname][$cname] ?? [],
                $voptions['anywhere'] ?? []
            );
            if (!$coptions['enable']) {
                continue;
            }
            /** @noinspection PhpUndefinedMethodInspection */
            $vtype = optional($voptions['type'])->getName() ?: null;
            $type = $vtype ?: $coptions['type'] ?: $column->getType()->getName();
            $comment = $coptions['comment'] ? $this->getCompatiblePlatform()->commentize($coptions['comment'], true) . ' ' : '';
            $key = $alias . '.' . $cname;
            switch ($type) {
                // 完全一致系
                case Type::BOOLEAN:
                case Type::BIGINT:
                case Type::INTEGER:
                case Type::SMALLINT:
                case Type::FLOAT:
                case Type::DECIMAL:
                    if ($is_numeric) {
                        if ($coptions['keyonly'] && (!isset($keys[$cname]))) {
                            break;
                        }
                        $where[$comment . $key . ' = ?'] = $word;
                    }
                    break;

                // 範囲系
                case Type::DATETIME:
                case Type::DATETIMETZ:
                case Type::DATE:
                    if ($ymdhis) {
                        if (!$coptions['greedy'] && $is_numeric) {
                            break;
                        }
                        $format = '%04d-%02d-%02d';
                        if ($type !== Type::DATE) {
                            $format .= ' %02d:%02d:%02d';
                        }
                        $from = Adhoc::fillYmdHis($ymdhis, true);
                        $to = Adhoc::fillYmdHis($ymdhis, false);
                        $where[$comment . $key . " BETWEEN ? AND ?"] = [vsprintf($format, $from), vsprintf($format, $to)];
                    }
                    break;

                // 包含系
                case Type::STRING:
                case Type::TEXT:
                    if (!$coptions['greedy'] && ($is_numeric || $ymdhis)) {
                        break;
                    }
                    $collate = '';
                    if ($coptions['collate']) {
                        $collate = ' collate ' . $coptions['collate'];
                    }
                    $where[$comment . $key . $collate . " LIKE ?"] = $inwords;
                    break;

                // いかんともしがたいので無視（TIME くらいはなんとかできそうだが…）
                default:
                case Type::GUID:
                case Type::OBJECT:
                case Type::TARRAY:
                case Type::SIMPLE_ARRAY:
                case Type::JSON_ARRAY:
                case Type::BINARY:
                case Type::BLOB:
                case Type::TIME:
            }
        }
        return $where;
    }

    /**
     * クエリビルダを生成して返す
     *
     * 極力 new QueryBuilder せずにこのメソッドを介すこと。
     *
     * @return QueryBuilder クエリビルダオブジェクト
     */
    public function createQueryBuilder()
    {
        return new QueryBuilder($this);
    }

    /**
     * サブクエリビルダを生成して返す
     *
     * 極力 new SubqueryBuilder せずにこのメソッドを介すこと。
     *
     * @return SubqueryBuilder サブクエリビルダオブジェクト
     */
    public function createSubqueryBuilder()
    {
        return new SubqueryBuilder($this);
    }

    /**
     * レコードの配列を返す
     *
     * ```php
     * $db->fetchArray('SELECT id, name FROM tablename');
     * // results:
     * [
     *     [
     *         'id'   => 1,
     *         'name' => 'name1',
     *     ],
     *     [
     *         'id'   => 2,
     *         'name' => 'name2',
     *     ],
     * ];
     * ```
     *
     * @used-by fetchArrayOrThrow()
     *
     * @param string|QueryBuilder $sql クエリ
     * @param array $params bind パラメータ
     * @return array|Entityable[] クエリ結果
     */
    public function fetchArray($sql, array $params = [])
    {
        $result = $this->_doFetch($sql, $params, self::METHOD_ARRAY);
        if ($sql instanceof QueryBuilder) {
            $result = $sql->postselect($result);
        }
        return $result;
    }

    /**
     * レコードの連想配列を返す
     *
     * ```php
     * $db->fetchAssoc('SELECT id, name FROM tablename');
     * // results:
     * [
     *     1 => [
     *         'id'   => 1,
     *         'name' => 'name1',
     *     ],
     *     2 => [
     *         'id'   => 2,
     *         'name' => 'name2',
     *     ],
     * ];
     * ```
     *
     * @used-by fetchAssocOrThrow()
     *
     * @param string|QueryBuilder $sql クエリ
     * @param array $params bind パラメータ
     * @return array|Entityable[] クエリ結果
     */
    public function fetchAssoc($sql, array $params = [])
    {
        $result = $this->_doFetch($sql, $params, self::METHOD_ASSOC);
        if ($sql instanceof QueryBuilder) {
            $result = $sql->postselect($result);
        }
        return $result;
    }

    /**
     * レコード[1列目]の配列を返す
     *
     * ```php
     * $db->fetchLists('SELECT name FROM tablename');
     * // results:
     * [
     *     'name1',
     *     'name2',
     * ];
     * ```
     *
     * @used-by fetchListsOrThrow()
     *
     * @param string|QueryBuilder $sql クエリ
     * @param array $params bind パラメータ
     * @return array|Entityable[] クエリ結果
     */
    public function fetchLists($sql, array $params = [])
    {
        $result = $this->_doFetch($sql, $params, self::METHOD_LISTS);
        if ($sql instanceof QueryBuilder) {
            $result = $sql->postselect($result);
        }
        return $result;
    }

    /**
     * レコード[1列目=>2列目]の連想配列を返す
     *
     * ```php
     * $db->fetchPairs('SELECT id, name FROM tablename');
     * // results:
     * [
     *     1 => 'name1',
     *     2 => 'name2',
     * ];
     * ```
     *
     * @used-by fetchPairsOrThrow()
     *
     * @param string|QueryBuilder $sql クエリ
     * @param array $params bind パラメータ
     * @return array|Entityable[] クエリ結果
     */
    public function fetchPairs($sql, array $params = [])
    {
        $result = $this->_doFetch($sql, $params, self::METHOD_PAIRS);
        if ($sql instanceof QueryBuilder) {
            $result = $sql->postselect($result);
        }
        return $result;
    }

    /**
     * レコードを返す
     *
     * このメソッドはフェッチ結果が2件以上だと**例外を投げる**。
     * これは
     *
     * - 1行を期待しているのに WHERE や LIMIT がなく、無駄なクエリになっている
     * - {@link whereInto()} の仕様により意図せず配列を与えて WHERE IN になっている
     *
     * のを予防的に阻止するため必要な仕様である。
     *
     * ```php
     * $db->fetchTuple('SELECT id, name FROM tablename LIMIT 1');
     * // results:
     * [
     *     'id'   => 1,
     *     'name' => 'name1',
     * ];
     * ```
     *
     * @used-by fetchTupleOrThrow()
     *
     * @param string|QueryBuilder $sql クエリ
     * @param array $params bind パラメータ
     * @return array|Entityable|false クエリ結果
     */
    public function fetchTuple($sql, array $params = [])
    {
        $result = $this->_doFetch($sql, $params, self::METHOD_TUPLE);
        if ($result === false) {
            return false;
        }
        if ($sql instanceof QueryBuilder) {
            $result = $sql->postselect([$result])[0];
        }
        return $result;
    }

    /**
     * レコード[1列目]を返す
     *
     * このメソッドはフェッチ結果が2件以上だと**例外を投げる**。
     * これは
     *
     * - 1行を期待しているのに WHERE や LIMIT がなく、無駄なクエリになっている
     * - {@link whereInto()} の仕様により意図せず配列を与えて WHERE IN になっている
     *
     * のを予防的に阻止するために必要な仕様である。
     *
     * ```php
     * $db->fetchValue('SELECT name FROM tablename LIMIT 1');
     * // results:
     * 'name1';
     * ```
     *
     * @used-by fetchValueOrThrow()
     *
     * @param string|QueryBuilder $sql クエリ
     * @param array $params bind パラメータ
     * @return mixed クエリ結果
     */
    public function fetchValue($sql, array $params = [])
    {
        $result = $this->_doFetch($sql, $params, self::METHOD_VALUE);
        if ($result === false) {
            return false;
        }
        if ($sql instanceof QueryBuilder) {
            $result = $sql->postselect([$result])[0];
        }
        return $result;
    }

    /**
     * foreach で回せる何かとサブメソッド名で結果を構築する
     *
     * @ignore 難解過ぎる上内部でしか使われない
     *
     * @param \Traversable|array $row_provider foreach で回せる何か
     * @param string|array $fetch_mode Database::METHOD__XXX
     * @param \Closure $converter 行ごとの変換クロージャ
     * @param bool $sub_flg サブクエリフラグ
     * @return array|bool|mixed クエリ結果
     */
    public function perform($row_provider, $fetch_mode, $converter = null, $sub_flg = false)
    {
        switch ($fetch_mode) {
            default:
                throw new \BadMethodCallException("unknown fetch method '$fetch_mode'.");

            /// 配列の配列系
            case self::METHOD_ARRAY:
                $result = [];
                foreach ($row_provider as $n => $row) {
                    if ($sub_flg) {
                        foreach (self::AUTO_KEYS as $ukey) {
                            if (array_key_exists($ukey, $row)) {
                                unset($row[$ukey]);
                            }
                        }
                    }
                    $result[] = $converter ? $converter($row) : $row;
                }
                return $result;
            case self::METHOD_ASSOC:
                $result = [];
                foreach ($row_provider as $n => $row) {
                    foreach ($row as $e) {
                        $key = $e;
                        break;
                    }
                    if ($sub_flg) {
                        foreach (self::AUTO_KEYS as $ukey) {
                            if (array_key_exists($ukey, $row)) {
                                unset($row[$ukey]);
                            }
                        }
                    }
                    /** @noinspection PhpUndefinedVariableInspection */
                    $result[$key] = $converter ? $converter($row) : $row;
                }
                return $result;

            /// 配列系
            case self::METHOD_LISTS:
                $result = [];
                foreach ($row_provider as $n => $row) {
                    $row = $converter ? $converter($row) : $row;
                    foreach ($row as $e) {
                        $val = $e;
                        break;
                    }
                    /** @noinspection PhpUndefinedVariableInspection */
                    $result[] = $val;
                }
                return $result;
            case self::METHOD_PAIRS:
                $result = [];
                foreach ($row_provider as $n => $row) {
                    foreach ($row as $e) {
                        $key = $e;
                        break;
                    }
                    $i = 0;
                    $row = $converter ? $converter($row) : $row;
                    foreach ($row as $e) {
                        if ($i++ === 1) {
                            $val = $e;
                            break;
                        }
                    }
                    /** @noinspection PhpUndefinedVariableInspection */
                    $result[$key] = $val;
                }
                return $result;

            /// シンプル系
            case self::METHOD_TUPLE:
                $result = false;
                $first = true;
                foreach ($row_provider as $n => $row) {
                    if ($sub_flg) {
                        foreach (self::AUTO_KEYS as $ukey) {
                            if (array_key_exists($ukey, $row)) {
                                unset($row[$ukey]);
                            }
                        }
                    }
                    if ($first) {
                        $first = false;
                        $result = $converter ? $converter($row) : $row;
                    }
                    else {
                        throw new TooManyException('record is too many.');
                    }
                }
                return $result;
            case self::METHOD_VALUE:
                $result = false;
                $first = true;
                foreach ($row_provider as $n => $row) {
                    if ($first) {
                        $first = false;
                        $row = $converter ? $converter($row) : $row;
                        foreach ($row as $e) {
                            $result = $e;
                            break;
                        }
                    }
                    else {
                        throw new TooManyException('record is too many.');
                    }
                }
                return $result;
        }
    }

    /**
     * 各句を指定してクエリビルダを生成する
     *
     * ```php
     * // 単純にクエリビルダオブジェクトを取得する
     * $qb = $db->select('tablename', ['create_date < ?' => '2000-12-23 12:34:56']);
     *
     * // array/assoc などのプロキシメソッドで直接結果を取得する
     * $results = $db->selectArray('tablename', ['create_date < ?' => '2000-12-23 12:34:56']); // 結果形式は fetchArray と同じ
     * $results = $db->selectAssoc('tablename', ['create_date < ?' => '2000-12-23 12:34:56']); // 結果形式は fetchAssoc と同じ
     * $results = $db->selectLists('tablename', ['create_date < ?' => '2000-12-23 12:34:56']); // 結果形式は fetchLists と同じ
     * $results = $db->selectPairs('tablename', ['create_date < ?' => '2000-12-23 12:34:56']); // 結果形式は fetchPairs と同じ
     * $results = $db->selectTuple('tablename', ['create_date < ?' => '2000-12-23 12:34:56']); // 結果形式は fetchTuple と同じ
     * $results = $db->selectValue('tablename', ['create_date < ?' => '2000-12-23 12:34:56']); // 結果形式は fetchValue と同じ
     * ```
     *
     * $tableDescriptor, $where はかなり多彩な指定が可能。下記のメソッドも参照。
     *
     * - see {@link QueryBuilder::column()}
     * - see {@link QueryBuilder::where()}
     * - see {@link QueryBuilder::orderBy()}
     * - see {@link QueryBuilder::limit()}
     * - see {@link QueryBuilder::groupBy()}
     * - see {@link QueryBuilder::having()}
     * - see {@link fetchArray()}
     * - see {@link fetchAssoc()}
     * - see {@link fetchLists()}
     * - see {@link fetchPairs()}
     * - see {@link fetchTuple()}
     * - see {@link fetchValue()}
     *
     * @used-by selectArray()
     * @used-by selectArrayInShare()
     * @used-by selectArrayForUpdate()
     * @used-by selectArrayOrThrow()
     * @used-by selectAssoc()
     * @used-by selectAssocInShare()
     * @used-by selectAssocForUpdate()
     * @used-by selectAssocOrThrow()
     * @used-by selectLists()
     * @used-by selectListsInShare()
     * @used-by selectListsForUpdate()
     * @used-by selectListsOrThrow()
     * @used-by selectPairs()
     * @used-by selectPairsInShare()
     * @used-by selectPairsForUpdate()
     * @used-by selectPairsOrThrow()
     * @used-by selectTuple()
     * @used-by selectTupleInShare()
     * @used-by selectTupleForUpdate()
     * @used-by selectTupleOrThrow()
     * @used-by selectValue()
     * @used-by selectValueInShare()
     * @used-by selectValueForUpdate()
     * @used-by selectValueOrThrow()
     *
     * @param array|string $tableDescriptor 取得テーブルとカラム（{@link TableDescriptor}）
     * @param array|string $where WHERE 条件（{@link QueryBuilder::where()}）
     * @param array|string $orderBy 並び順（{@link QueryBuilder::orderBy()}）
     * @param array|int $limit 取得件数（{@link QueryBuilder::limit()}）
     * @param array|string $groupBy グルーピング（{@link QueryBuilder::groupBy()}）
     * @param array|string $having HAVING 条件（{@link QueryBuilder::having()}）
     * @return QueryBuilder クエリビルダオブジェクト
     */
    public function select($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        $builder = $this->createQueryBuilder();
        return $builder->build(array_combine(QueryBuilder::CLAUSES, [$tableDescriptor, $where, $orderBy, $limit, $groupBy, $having]), true);
    }

    /**
     * 各句を指定してエンティティ用クエリビルダを生成する
     *
     * エンティティクラスは駆動表で決まる。
     *
     * ```php
     * // 単純にクエリビルダオブジェクトを取得する
     * $qb = $db->entity('tablename', ['create_date < ?' => '2000-12-23 12:34:56']);
     *
     * // array/assoc などのプロキシメソッドで直接結果を取得する
     * $results = $db->entityArray('tablename', ['create_date < ?' => '2000-12-23 12:34:56']); // エンティティインスタンスの配列を返す
     * $results = $db->entityAssoc('tablename', ['create_date < ?' => '2000-12-23 12:34:56']); // エンティティインスタンスの連想配列（キーは最初のカラム）を返す
     * $results = $db->entityTuple('tablename', ['create_date < ?' => '2000-12-23 12:34:56']); // エンティティインスタンスを返す
     * ```
     *
     * $tableDescriptor, $where はかなり多彩な指定が可能。下記のメソッドも参照。
     *
     * - see {@link QueryBuilder::cast()}
     * - see {@link QueryBuilder::column()}
     * - see {@link QueryBuilder::where()}
     * - see {@link QueryBuilder::orderBy()}
     * - see {@link QueryBuilder::limit()}
     * - see {@link QueryBuilder::groupBy()}
     * - see {@link QueryBuilder::having()}
     *
     * @used-by entityArray()
     * @used-by entityArrayInShare()
     * @used-by entityArrayForUpdate()
     * @used-by entityArrayOrThrow()
     * @used-by entityAssoc()
     * @used-by entityAssocInShare()
     * @used-by entityAssocForUpdate()
     * @used-by entityAssocOrThrow()
     * @used-by entityTuple()
     * @used-by entityTupleInShare()
     * @used-by entityTupleForUpdate()
     * @used-by entityTupleOrThrow()
     *
     * @inheritdoc select()
     */
    public function entity($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->select(...func_get_args())->cast(null);
    }

    /**
     * 子供レコード配列を取得する SubqueryBuilder を返す
     *
     * このメソッドを使うと自身のレコード配列に子供レコードを生やすことができる。
     * この処理はクエリを2回投げることで実現される。つまり 1 + N 問題は起こらない（tuple だけではなく array/assoc でも同様）。
     *
     * 第1引数 $child_columns で結合するカラムを指定できるが、実際は null を指定して外部キーカラムで結合することが多いはず。
     * （そもそも生メソッドを使わず `subArray` のようなプロキシメソッドが前提）。
     *
     * WHERE や ORDER などの条件も完全に活かすことができるが、LIMIT だけは扱いが異なる（下記のサンプルコードを参照）。
     * これを利用するといわゆる「グループ内の上位N件取得」も簡単に実現できる。
     *
     * ```php
     * # t_parent に紐づく t_child レコードを引っ張る
     * $qb = $db->selectTuple([
     *     't_parent P' => [
     *         'parent_id',
     *         // 結合キーとして [子供カラム => 親カラム] を指定する（複合可）
     *         'childarray'  => $db->subselectArray(['child_id' => 'parent_id'], 't_child'),
     *         // もし仮にカラム名が同じなら単配列でもOK
     *         'childtuple1' => $db->subselectTuple(['id'], 't_child'),
     *         // というか単配列で要素が1つなら文字列でもOK
     *         'childtuple2' => $db->subselectTuple('id', 't_child'),
     *         // さらに外部キーがあるなら null でもOK（ただしその場合、後述の subTuple の方が適している）
     *         'childtuple3' => $db->subselectTuple(null, 't_child'),
     *     ],
     * ]);
     *
     * # 上記の外部キー版
     * $qb = $db->selectTuple([
     *     't_parent P' => [
     *         'parent_id',
     *         // 上記のように結合カラムを指定する必要はない
     *         'childarray' => $db->subArray('t_child'),
     *         'childtuple' => $db->subTuple('t_child'),
     *     ],
     * ]);
     *
     * # サブの limit は各行に対して作用する
     * $qb = $db->selectArray([
     *     't_parent P' => [
     *         'parent_id',
     *         // 各行に紐づく t_child の最新5件を取得する
     *         'latestchild' => $db->subArray('t_child', [], ['update_time' => 'DESC'], 5),
     *     ],
     * ]);
     * ```
     *
     * @used-by subselectArray()
     * @used-by subselectAssoc()
     * @used-by subselectLists()
     * @used-by subselectPairs()
     * @used-by subselectTuple()
     * @used-by subselectValue()
     * @used-by subArray()
     * @used-by subAssoc()
     * @used-by subLists()
     * @used-by subPairs()
     * @used-by subTuple()
     * @used-by subValue()
     *
     * @inheritdoc select()
     *
     * @param array|string $child_columns [子供カラム => 親カラム] あるいは [共通カラム]。 null を与えると外部キーカラム
     * @return SubqueryBuilder サブクエリビルダオブジェクト
     */
    public function subselect($child_columns, $tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        $builder = $this->createSubqueryBuilder();
        $builder->lazy('select', $child_columns);
        return $builder->build(array_combine(QueryBuilder::CLAUSES, [$tableDescriptor, $where, $orderBy, $limit, $groupBy, $having]));
    }

    /**
     * 親キーを見て遅延ビルドする子供レコード配列を取得する SubqueryBuilder を返す
     *
     * このメソッドを使うと自身のレコード配列に子供レコードを生やすことができる。
     * この処理はクエリを2回投げることで実現される。つまり 1+ N 問題は起こらない（tuple だけではなく array/assoc でも同様）。
     *
     * シンプルな宣言的記法を目指して実装されたメソッドなので結合カラムは外部キー固定。
     *
     * ```php
     * # t_parent に紐づく t_child レコードを引っ張る
     * $qb = $db->selectTuple([
     *     't_parent P' => [
     *         'parent_id',
     *         // 親のキーがテーブル名（エイリアス）の役目を果たすので subtable 自体にはテーブル名は不要。カラムのみ記述する
     *         't_child AS childarray1' => $db->subtableAssoc('*'),
     *         // 実際にはメソッド呼び出しを伴わずに配列で指定することが多い（これは↑のメソッド呼び出しと全く等価な糖衣構文）
     *         't_child AS childarray2' => ['*'],
     *     ],
     * ]);
     *
     * # ネストもできる（t_ancestor に紐づく t_parent に紐づく t_child レコードを引っ張る）
     * $qb = $db->selectTuple([
     *     't_ancestor AS A' => [
     *         't_parent AS P' => [
     *             't_child AS C' => ['*'],
     *         ],
     *     ],
     * ]);
     * ```
     *
     * @used-by subtableArray()
     * @used-by subtableAssoc()
     * @used-by subtableLists()
     * @used-by subtablePairs()
     * @used-by subtableTuple()
     * @used-by subtableValue()
     *
     * @inheritdoc subselect()
     */
    public function subtable($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        $builder = $this->createSubqueryBuilder();
        $builder->delay()->lazy('select', null);
        return $builder->build(array_combine(QueryBuilder::CLAUSES, [$tableDescriptor, $where, $orderBy, $limit, $groupBy, $having]));
    }

    /**
     * 相関サブクエリ表すビルダを返す
     *
     * 単純に相関のあるテーブルとの外部キーを追加するだけの動作となる。
     * subexists や subcount, submin などはこのメソッドの特殊化と言える。
     *
     * ```php
     * // SELECT 句での使用例
     * $db->select([
     *     't_article' => [
     *         // 各 t_article に紐づく t_comment の ID を結合する
     *         'comment_ids' => $db->subquery('t_comment.GROUP_CONCAT(comment_id)'),
     *     ],
     * ]);
     * // SELECT
     * //   (SELECT GROUP_CONCAT(comment_id) FROM t_comment WHERE t_comment.article_id = t_article.article_id) AS comment_ids
     * // FROM t_article
     *
     * // WHERE 句での使用例
     * $db->select('t_article', [
     *     // active な t_comment を持つ t_article を取得する（ただし、この例なら EXISTS で十分）
     *     'article_id' => $db->subquery('t_comment', ['status' => 'active']),
     * ]);
     * // SELECT
     * //   t_article.*
     * // FROM t_article
     * // WHERE
     * //   article_id IN(
     * //     SELECT t_comment.article_id FROM t_comment WHERE
     * //       t_comment.status = 'active' AND
     * //       t_comment.article_id = t_article.article_id
     * //   )
     * ```
     *
     * @param array|string $tableDescriptor 取得テーブルとカラム（{@link TableDescriptor}）
     * @param array|string $where WHERE 条件（{@link QueryBuilder::where()}）
     * @param array|string $orderBy 並び順（{@link QueryBuilder::orderBy()}）
     * @param array|int $limit 取得件数（{@link QueryBuilder::limit()}）
     * @param array|string $groupBy グルーピング（{@link QueryBuilder::groupBy()}）
     * @param array|string $having HAVING 条件（{@link QueryBuilder::having()}）
     * @return QueryBuilder クエリビルダオブジェクト
     */
    public function subquery($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        // build 前にあらかじめ setSubmethod して分岐する必要がある
        $builder = $this->createQueryBuilder();
        $builder->setSubmethod('query');
        return $builder->build(array_combine(QueryBuilder::CLAUSES, [$tableDescriptor, $where, $orderBy, $limit, $groupBy, $having]), true);
    }

    /**
     * 相関サブクエリの EXISTS を表すビルダを返す
     *
     * ```php
     * // SELECT 句での使用例
     * $db->select([
     *     't_article' => [
     *         // 各 t_article に紐づく t_comment にレコードを持つなら true が返される
     *         'has_comment'     => $db->subexists('t_comment'),
     *         // 各 t_article に紐づく t_comment delete_flg = 0 なレコードを持たないなら true が返される
     *         'has_not_comment' => $db->notSubexists('t_comment', ['delete_flg' => 0]),
     *     ],
     * ]);
     * // SELECT
     * //   EXISTS (SELECT * FROM t_comment WHERE t_comment.article_id = t_article.article_id) AS has_comment,
     * //   NOT EXISTS (SELECT * FROM t_comment WHERE (delete_flg = '0') AND (t_comment.article_id = t_article.article_id)) AS has_not_comment
     * // FROM t_article
     *
     * // WHERE 句での使用例
     * $db->select('t_article', [
     *     // 「各記事でコメントを持つ記事」を表す WHERE EXISTS になる
     *     $db->subexists('t_comment'),
     * ]);
     * // SELECT
     * //   t_article.*
     * // FROM t_article
     * // WHERE (EXISTS (SELECT * FROM t_comment WHERE t_comment.article_id = t_article.article_id))
     *
     * // JOIN も含めて複数テーブルがあり、明確に「t_article と t_comment で」結びたい場合はキーで明示する
     * $db->select('t_article, t_something', [
     *     // 「何と？」をキーで明示できる
     *     't_article'          => $db->subexists('t_comment'),
     *     // これだと t_something と t_comment での結合となる（外部キーがあれば、だが）
     *     't_something'        => $db->subexists('t_comment'),
     *     // さらに t_something に複数の外部キーがある場合は:で明示できる
     *     't_something:fkname' => $db->subexists('t_comment'),
     * ]);
     * ```
     *
     * @param array|string $tableDescriptor 取得テーブルとカラム（{@link TableDescriptor}）
     * @param array|string $where WHERE 条件（{@link QueryBuilder::where()}）
     * @return QueryBuilder クエリビルダオブジェクト
     */
    public function subexists($tableDescriptor, $where = [])
    {
        return $this->select($tableDescriptor, $where)->setSubmethod(true);
    }

    /**
     * {@link subexists()} の否定版
     *
     * @inheritdoc subexists()
     */
    public function notSubexists($tableDescriptor, $where = [])
    {
        return $this->select($tableDescriptor, $where)->setSubmethod(false);
    }

    /**
     * 相関サブクエリの aggaregate を表すビルダを返す
     *
     * 下記のような subXXX のために存在しているので、このメソッドを直接呼ぶような状況はあまり無い。
     *
     * ```php
     * # SELECT 句での使用例
     * $db->select([
     *     't_article' => [
     *         // t_article に紐づく t_comment の数を返す
     *         'subcount' => $db->subcount('t_comment'),
     *         // t_article に紐づく t_comment.comment_id の最小値を返す
     *         'submin'   => $db->submin('t_comment.comment_id'),
     *         // t_article に紐づく t_comment.comment_id の最大値を返す
     *         'submax'   => $db->submax('t_comment.comment_id'),
     *         // t_article に紐づく t_comment.comment_id の合計値を返す
     *         'subsum'   => $db->subsum('t_comment.comment_id'),
     *         // t_article に紐づく t_comment.comment_id の平均値を返す
     *         'subavg'   => $db->subavg('t_comment.comment_id'),
     *     ],
     * ]);
     * // SELECT
     * //   (SELECT COUNT(*) AS `*@count` FROM t_comment WHERE t_comment.article_id = t_article.article_id) AS subcount,
     * //   (SELECT MIN(t_comment.comment_id) AS `t_comment.comment_id@min` FROM t_comment WHERE t_comment.article_id = t_article.article_id) AS submin,
     * //   (SELECT MAX(t_comment.comment_id) AS `t_comment.comment_id@max` FROM t_comment WHERE t_comment.article_id = t_article.article_id) AS submax,
     * //   (SELECT SUM(t_comment.comment_id) AS `t_comment.comment_id@sum` FROM t_comment WHERE t_comment.article_id = t_article.article_id) AS subsum,
     * //   (SELECT AVG(t_comment.comment_id) AS `t_comment.comment_id@avg` FROM t_comment WHERE t_comment.article_id = t_article.article_id) AS subavg
     * // FROM t_article
     *
     * # WHERE 句での使用例1
     * $db->select('t_article A', [
     *     // 「各記事でコメントが3件以上」を表す
     *     '3 < ?' => $db->subcount('t_comment'),
     * ]);
     * // SELECT A.*
     * // FROM t_article A
     * // WHERE
     * //   3 < (
     * //     SELECT COUNT(*) AS `*@count`
     * //     FROM t_comment
     * //     WHERE t_comment.article_id = A.article_id
     * //   )
     *
     * # WHERE 句での使用例2
     * $db->select('t_article A+t_comment C', [
     *     // 「各記事で最新のコメント1件と結合」を表す
     *     'C.comment_id' => $db->submax('t_comment.comment_id'),
     * ]);
     * // SELECT A.*, C.*
     * // FROM t_article A
     * // INNER JOIN t_comment C ON C.article_id = A.article_id
     * // WHERE C.comment_id IN (
     * //   SELECT MAX(t_comment.comment_id) AS `t_comment.comment_id@max`
     * //   FROM t_comment
     * //   WHERE t_comment.article_id = A.article_id
     * // )
     * ```
     *
     * @used-by subcount()
     * @used-by submin()
     * @used-by submax()
     * @used-by subsum()
     * @used-by subavg()
     *
     * @param array|string $aggregate 集約関数名
     * @param array|string $column サブテーブル名
     * @param array $where WHERE 条件
     * @return QueryBuilder クエリビルダオブジェクト
     */
    public function subaggregate($aggregate, $column, $where = [])
    {
        return $this->select($column, $where)->aggregate($aggregate, 1)->setSubmethod($aggregate);
    }

    /**
     * UNION する
     *
     * FROM が $unions 節のサブクエリになり、$column や $where はそのサブクエリに対して適用される。
     *
     * ```php
     * $db->union(['SELECT "a"', 'SELECT "b"']);
     * // → シンプルに `SELECT "a" UNION SELECT "b"` と解釈される
     *
     * $db->union(['SELECT "a1" AS c1, "a2" AS c2', 'SELECT "b1" AS c1, "b2" AS c2'], ['c1']);
     * // → UNION 部が FROM 句に飲み込まれ `SELECT c1 FROM (SELECT "a1" AS c1, "a2" AS c2 UNION SELECT "b1" AS c1, "b2" AS c2) AS T` と解釈される
     *
     * $db->union(['SELECT "a1" AS c1, "a2" AS c2', 'SELECT "b1" AS c1, "b2" AS c2'], ['c1'], ['c2' => 'b1']);
     * // → UNION 部が FROM 句に飲み込まれ `SELECT c1 FROM (SELECT "a1" AS c1, "a2" AS c2 UNION SELECT "b1" AS c1, "b2" AS c2) AS T WHERE c2 = "b1"` と解釈される
     *
     * $db->unionAll([$db->select('t_article'), $db->select('t_article')]);
     * // → クエリビルダも使える（倍の行を取得できる。あくまで例なので意味はない）
     * ```
     *
     * @param array|string|QueryBuilder $unions union サブクエリ
     * @param array|string $column 取得カラム [column]
     * @param array|string $where 条件
     * @param array|string $orderBy 単カラム名か[column=>asc/desc]な連想配列
     * @param array|int $limit 単数値か[offset=>count]な連想配列
     * @param array|string $groupBy カラム名かその配列
     * @param array|string $having 条件
     * @return QueryBuilder クエリビルダオブジェクト
     */
    public function union($unions, $column = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->select(['' => $column], $where, $orderBy, $limit, $groupBy, $having)->union($unions);
    }

    /**
     * UNION ALL する
     *
     * ALL で UNION される以外は {@link union()} と全く同じ。
     *
     * @param array|string|QueryBuilder $unions union サブクエリ
     * @param array|string $column 取得カラム [column]
     * @param array|string $where 条件
     * @param array|string $orderBy 単カラム名か[column=>asc/desc]な連想配列
     * @param array|int $limit 単数値か[offset=>count]な連想配列
     * @param array|string $groupBy カラム名かその配列
     * @param array|string $having 条件
     * @return QueryBuilder クエリビルダオブジェクト
     */
    public function unionAll($unions, $column = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->select(['' => $column], $where, $orderBy, $limit, $groupBy, $having)->unionAll($unions);
    }

    /**
     * レコードの存在を返す
     *
     * ```php
     * # 単純に t_article が存在するか bool で返す
     * $db->exists('t_article');
     * // SELECT EXISTS (SELECT * FROM t_article)
     *
     * # 有効な t_article が存在するか bool で返す
     * $db->exists('t_article', ['delete_flg' => 0]);
     * // SELECT EXISTS (SELECT * FROM t_article WHERE t_article.delete_flg = 0)
     *
     * # 有効な t_article が存在するかロックしつつ bool で返す
     * $db->exists('t_article', ['delete_flg' => 0], true);
     * // SELECT EXISTS (SELECT * FROM t_article WHERE t_article.delete_flg = 0 FOR UPDATE)
     * ```
     *
     * @inheritdoc select()
     *
     * @param bool $for_update EXISTS チェックはしばしばロックを伴うのでそのフラグ
     * @return bool レコードが存在するなら true
     */
    public function exists($tableDescriptor, $where = [], $for_update = false)
    {
        $builder = $this->selectExists($tableDescriptor, $where, $for_update);
        $exists = $this->getCompatiblePlatform()->convertSelectExistsQuery($builder);
        return !!$this->fetchValue("SELECT $exists", $exists->getParams());
    }

    /**
     * EXISTS クエリビルダを返す
     *
     * ```php
     * // EXISTS (SELECT * FROM t_table)
     * $db->selectExists('t_table');
     *
     * // NOT EXISTS (SELECT * FROM t_table WHERE delete_flg = 0)
     * $db->selectNotExists('t_table', ['delete_flg' => 0]);
     * ```
     *
     * @inheritdoc exists()
     *
     * @return QueryBuilder EXISTS クエリビルダ
     */
    public function selectExists($tableDescriptor, $where = [], $for_update = false)
    {
        $builder = $this->select($tableDescriptor, $where)->exists();

        if ($for_update) {
            $builder->lockForUpdate();
        }

        return $builder;
    }

    /**
     * {@link selectExists()} の NOT 版
     *
     * @inheritdoc exists()
     *
     * @return QueryBuilder NOT EXISTS クエリビルダ
     */
    public function selectNotExists($tableDescriptor, $where = [], $for_update = false)
    {
        $builder = $this->select($tableDescriptor, $where)->notExists();

        if ($for_update) {
            $builder->lockForUpdate();
        }

        return $builder;
    }

    /**
     * 集約クエリビルダを返す
     *
     * {@link selectCount()} などのために存在し、明示的に呼ぶことはほとんど無い。
     *
     * ```php
     * // SELECT COUNT(group_id) FROM t_table
     * $db->selectCount('t_table.group_id');
     *
     * // SELECT MAX(id) FROM t_table WHERE delete_flg = 0 GROUP BY group_id
     * $db->selectMax('t_table.id', ['delete_flg' => 0], 'group_id');
     * ```
     *
     * @used-by selectCount()
     * @used-by selectMin()
     * @used-by selectMax()
     * @used-by selectSum()
     * @used-by selectAvg()
     *
     * @param string|array $aggregation 集約関数名
     * @param array|string $column 取得テーブルとカラム
     * @param array|string $where 条件
     * @param array|string $groupBy カラム名かその配列
     * @param array|string $having 条件
     * @return QueryBuilder 集約クエリビルダ
     */
    public function selectAggregate($aggregation, $column, $where = [], $groupBy = [], $having = [])
    {
        return $this->select($column, $where, [], [], $groupBy, $having)->aggregate($aggregation);
    }

    /**
     * 特定レコードの前後のレコードを返す
     *
     * {@link QueryBuilder::neighbor()} へのプロキシメソッド。
     *
     * @inheritdoc QueryBuilder::neighbor()
     *
     * @param array|string $tableDescriptor 取得テーブルとカラム（{@link TableDescriptor}）
     */
    public function neighbor($tableDescriptor, $predicates, $limit = 1)
    {
        return $this->select($tableDescriptor)->neighbor($predicates, $limit);
    }

    /**
     * 集約を実行する
     *
     * - 集約列が0個
     *   - 取得列が1個のみ：value 相当（スカラー値を返す）
     *   - 取得列が2個以上：tuple 相当（連想配列を返す）
     * - 集約列が1個
     *   - 取得列が1個のみ：pairs 相当（キーペアを返す）
     *   - 取得列が2個以上：assoc 相当（連想配列の連想配列を返す）
     * - 上記以外： array 相当（連想配列の配列を返す）
     *
     * ```php
     * // t_table.group_id の COUNT がスカラー値で得られる
     * $db->aggregate('count', 't_table.group_id');
     *
     * // t_table.group_id の AVG がキーペアで得られる
     * $db->aggregate('avg', 't_table.group_id', [], 't_table.group_id');
     *
     * // t_table.group_id の MIN,MAX が連想配列で得られる
     * $db->aggregate(['min', 'max'], 't_table.group_id', [], 't_table.group_id');
     * ```
     *
     * が、グループのキーが SELECT されたり、順番が大事だったりするので、実用上の利点はほとんどない。
     * 同じ条件、グループで MIN, MAX を一回で取りたい、のような状況で使う程度で、どちらかと言えば下記の集約関数の個別メソッドのために存在している。
     *
     * ```php
     * // t_table の件数をスカラー値で返す
     * $db->count('t_table');
     *
     * // id 10 未満の t_table.id の最小値をスカラー値で返す
     * $db->min('t_table.id', ['id < 10']);
     *
     * // id 10 未満の group_id でグルーピングした t_table.id の最大値を `[group_id => max]` 形式で返す
     * $db->max('t_table.id', ['id < 10'], ['group_id']);
     *
     * // id 10 未満の group_id でグルーピングした t_table.score の合計値が 5 以上のものを `[group_id => [id@sum => id@sum, score@sum => score@sum]]` 形式で返す
     * $db->sum('t_table.id,score', ['id < 10'], ['group_id'], ['score@sum >= 5']);
     * ```
     *
     * 特殊な使い方として $aggregate に連想配列を渡すとクロス集計ができる。
     * これはこのメソッドのかなり特異な使い方で、そういったことがしたい場合は普通にクエリビルダや生クエリでも実行できるはず。
     *
     * ```php
     * # t_login テーブルから user_id ごとの2016～2018年度月次集計を返す
     * $db->aggregate([
     *     'year_2016' => 'SUM(YEAR(login_at) = "2016")',              // 文字列でも良いがインジェクションの危険アリ
     *     'year_2017' => $db->raw('SUM(YEAR(login_at) = ?)', '2017'), // 普通は raw で Expression を渡す
     *     'year_2018' => ['SUM(YEAR(login_at) = ?)' => '2018'],       // 配列を渡すと自動で Expression 化される
     * ], 't_login', ['login_at:[~)' => ['2016-01-01', '2019-01-01']], 'user_id');
     * // SELECT
     * //     user_id,
     * //     SUM(YEAR(login_at) = "2016") AS `year_2016`,
     * //     SUM(YEAR(login_at) = "2017") AS `year_2017`,
     * //     SUM(YEAR(login_at) = "2018") AS `year_2018`
     * // FROM
     * //     t_login
     * // WHERE
     * //     login_at >= "2016-01-01" AND login_at < "2019-01-01"
     * // GROUP BY
     * //     user_id
     *
     * # 上記は式が同じで値のみ異なるので省略記法が存在する
     * $db->aggregate([
     *     'SUM(YEAR(login_at) = ?)' => ['2016', '2017', '2018'],
     * ], 't_login', ['login_at:[~)' => ['2016-01-01', '2019-01-01']], 'user_id');
     * // 生成される SQL は同じ
     * ```
     *
     * @used-by count()
     * @used-by min()
     * @used-by max()
     * @used-by sum()
     * @used-by avg()
     *
     * @param string|array $aggregation 集約関数名
     * @param array|string $column 取得テーブルとカラム
     * @param array|string $where 条件
     * @param array|string $groupBy カラム名かその配列
     * @param array|string $having 条件
     * @return int|array 集約結果
     */
    public function aggregate($aggregation, $column, $where = [], $groupBy = [], $having = [])
    {
        $builder = $this->selectAggregate($aggregation, $column, $where, $groupBy, $having);

        $stmt = $this->executeQuery($builder, $builder->getParams());

        $cast = function ($var) {
            if ((!is_int($var) && !is_float($var)) && preg_match('#^-?([1-9]\d*|0)(\.\d+)?$#u', (string) $var, $match)) {
                return isset($match[2]) ? (float) $var : (int) $var;
            }
            return $var;
        };

        $selectCount = count($builder->getQueryPart('select'));
        $groupCount = count($builder->getQueryPart('groupBy'));
        if ($groupCount === 0 && $selectCount === 1) {
            return var_apply($stmt->fetch(\PDO::FETCH_COLUMN), $cast); // value
        }
        elseif ($groupCount === 0 && $selectCount >= 2) {
            return var_apply($stmt->fetch(\PDO::FETCH_ASSOC), $cast); // tuple
        }
        elseif ($groupCount === 1 && $selectCount === 2) {
            return var_apply($stmt->fetchAll(\PDO::FETCH_COLUMN | \PDO::FETCH_UNIQUE), $cast); // pairs
        }
        elseif ($groupCount === 1 && $selectCount >= 3) {
            return var_apply($stmt->fetchAll(\PDO::FETCH_ASSOC | \PDO::FETCH_UNIQUE), $cast); // assoc
        }
        else {
            return var_apply($stmt->fetchAll(\PDO::FETCH_ASSOC), $cast); // array
        }
    }

    /**
     * 行を少しずつ返してくれるオブジェクトを返す
     *
     * 返却されたオブジェクトは foreach で回すことができ、かつ `PDO::fetch` で実装されていて省メモリで動作する。
     * さらにいくつか特殊な事ができるが {@link Yielder::setBufferMode()}, {@link Yielder::setEmulationUnique()} あたりを参照。
     *
     * ```php
     * # シンプルな例
     * foreach ($db->yieldArray('SELECT * FROM very_many_heavy_table') as $row) {
     *     // 一気に取得ではなく、逐次処理ができる
     * }
     *
     * # クエリビルダも渡せる
     * foreach ($db->yieldArray($db->select('very_many_heavy_table')) as $row) {
     *     // 一気に取得ではなく、逐次処理ができる
     * }
     * ```
     *
     * @used-by yieldArray()
     * @used-by yieldAssoc()
     * @used-by yieldLists()
     * @used-by yieldPairs()
     *
     * @param string|QueryBuilder $sql SQL
     * @param array $params SQL パラメータ
     * @return Yielder foreach で回せるオブジェクト
     */
    public function yield($sql, $params = [])
    {
        $converter = $this->_getConverter($sql);
        $callback = $sql instanceof QueryBuilder ? function ($row) use ($sql, $converter) {
            return $sql->postselect([$converter($row)], true)[0];
        } : $converter;
        return new Yielder(function ($connection) use ($sql, $params) {
            return $this->_sqlToStmt($sql, $params, $connection);
        }, $this->getSlaveConnection(), null, $callback);
    }

    /**
     * テーブルレコードをエクスポートする
     *
     * このメソッドは {@link yield()} を用いて省メモリで動作するように実装されているので、ある程度巨大な結果セットになるクエリでも実行できる。
     *
     * このメソッドを直接呼ぶことはほとんど無い。下記の例のように exportXXX 形式で呼び出すことが大半である。
     *
     * ```php
     * // 標準出力に php 配列を書き出す（全部省略のシンプル版）
     * $db->exportArray('SELECT * FROM tablename');
     *
     * // /tmp/tablename.csv に CSV を書き出す（ファイル指定）
     * $db->exportCsv('SELECT * FROM tablename', [], [
     *     'bom'       => false,
     *     'encoding'  => 'SJIS_win',
     * ], '/tmp/tablename.csv');
     *
     * // 標準出力に JSON を書き出す（クエリビルダで親子関係を指定）
     * $db->exportJson($db->select('t_parent/t_child'), [], [
     *     'assoc'  => false,
     *     'option' => JSON_UNESCAPED_UNICODE,
     * ], null);
     * ```
     *
     * @used-by exportArray()
     * @used-by exportCsv()
     * @used-by exportJson()
     *
     * @param string|AbstractGenerator $generator 出力タイプ
     * @param string|QueryBuilder $sql SQL
     * @param array $params SQL パラメータ
     * @param array $config 出力パラメータ
     * @param string|resource $file 出力先。 null を与えると標準出力に書き出される
     * @return int 書き込みバイト数
     */
    public function export($generator, $sql, $params = [], $config = [], $file = null)
    {
        if (is_string($generator)) {
            $generator = strtolower($generator);
            $class = $this->getExportClass();
            if (!isset($class[$generator])) {
                throw new \BadMethodCallException("export type '$generator' is undefined.");
            }
            $generator = new $class[$generator]($config);
        }
        return $generator->generate($file, $this->yield($sql, $params));
    }

    /**
     * レコード情報をかき集める
     *
     * 特定のレコードと関連したレコードを再帰的に処理して主キーの配列で返す。
     * 運用的な使用ではなく、保守的な使用を想定（運用でも使えなくはないが、おそらく速度的に実用に耐えない）。
     *
     * ```php
     * # t_article: 1 に関連するレコードをざっくりと返す（t_article -> t_comment -> t_comment_file のようなリレーションの場合）
     * $db->gather('t_article', ['article_id' => 1]);
     * // results:
     * [
     *     't_article' => [
     *         ['article_id' => 1],
     *     ],
     *     't_comment' => [
     *         ['comment_id' => 1, 'article_id' => 1],
     *         ['comment_id' => 2, 'article_id' => 1],
     *     ],
     *     't_comment_file' => [
     *         ['file_id' => 1, 'comment_id' => 1],
     *         ['file_id' => 2, 'comment_id' => 1],
     *         ['file_id' => 3, 'comment_id' => 2],
     *     ],
     * ];
     *
     * # $other_wheres で他のテーブルの条件が指定できる
     * $db->gather('t_article', ['article_id' => 1], [
     *     't_comment'      => ['comment_id' => 2],
     *     't_comment_file' => '0',
     * ]);
     * // results:
     * [
     *     't_article' => [
     *         ['article_id' => 1],
     *     ],
     *     't_comment' => [
     *         ['comment_id' => 2, 'article_id' => 1],
     *     ],
     * ];
     *
     * # $parentive: true で親方向に辿れる
     * $db->gather('t_comment_file', ['file_id' => 1], [], true);
     * // results:
     * [
     *     't_comment_file' => [
     *         ['file_id' => 1, 'comment_id' => 1],
     *     ],
     *     't_comment' => [
     *         ['comment_id' => 1, 'article_id' => 1],
     *     ],
     *     't_article' => [
     *         ['article_id' => 1],
     *     ],
     * ];
     * ```
     *
     * @param string $tablename 対象テーブル名
     * @param array $wheres 対象テーブルの条件
     * @param array $other_wheres その他の条件
     * @param bool $parentive 親方向にたどるか子方向に辿るか
     * @return array かき集めたレコード情報（[テーブル名 => [主キー配列1, 主キー配列2, ...]]）
     */
    public function gather($tablename, $wheres = [], $other_wheres = [], $parentive = false)
    {
        $schema = $this->getSchema();
        $cplatform = $this->getCompatiblePlatform();
        $pksep = $this->getPrimarySeparator();
        $allfkeys = $schema->getForeignKeys();

        $array_rekey = static function ($array, $map) {
            $result = [];
            foreach ($array as $k => $v) {
                if (isset($map[$k])) {
                    $result[$map[$k]] = $v;
                }
            }
            return $result;
        };

        $result = [];
        $processed = [];
        ($f = static function (Database $that, $tablename, $wheres) use (&$f, &$result, &$processed, $schema, $cplatform, $pksep, $allfkeys, $other_wheres, $parentive, $array_rekey) {
            $pkcol = $schema->getTablePrimaryColumns($tablename);
            $cols = $pkcol;
            foreach ($allfkeys as $fk) {
                if ($fk->getLocalTableName() === $tablename) {
                    $cols += array_flip($fk->getColumns());
                }
                if ($fk->getForeignTableName() === $tablename) {
                    $cols += array_flip($fk->getForeignColumns());
                }
            }

            $fkcols = [];
            $select = $that->select([$tablename => array_keys($cols)], $wheres)->andWhere($other_wheres[$tablename] ?? []);
            foreach ((array) $select->array() as $row) {
                $pval = array_intersect_key($row, $pkcol);
                $key = implode($pksep, $pval);
                $result[$tablename][$key] = $pval;

                foreach ($allfkeys as $fk) {
                    $fkname = $fk->getName() . '-' . $key;
                    if (isset($processed[$fkname])) {
                        continue;
                    }
                    $processed[$fkname] = true;

                    if ($parentive && $fk->getLocalTableName() === $tablename) {
                        $fkcols[$fk->getName()][$fk->getForeignTableName()][] = $array_rekey($row, array_combine($fk->getLocalColumns(), $fk->getForeignColumns()));
                    }
                    if (!$parentive && $fk->getForeignTableName() === $tablename) {
                        $fkcols[$fk->getName()][$fk->getLocalTableName()][] = $array_rekey($row, array_combine($fk->getForeignColumns(), $fk->getLocalColumns()));
                    }
                }
            }

            foreach ($fkcols as $fcols) {
                foreach ($fcols as $tname => $fkcol) {
                    $f($that, $tname, $cplatform->getPrimaryCondition($fkcol));
                }
            }
        })($this, $tablename, $wheres);
        return $result;
    }

    /**
     * prepare されたステートメントを取得する
     *
     * ほぼ内部メソッドであり、実際は下記のように暗黙のうちに使用され、明示的に呼び出す必要はあまりない。
     *
     * ```php
     * # プリペアドステートメントを実行する
     * // UPDATE
     * // prepare した地点で疑問符パラメータである name は固定される
     * $stmt = $db->prepare('UPDATE t_table SET name = ? WHERE id = :id', ['hoge']);
     * // あとから id パラメータを与えて実行することができる
     * $stmt->executeUpdate(['id' => 1]); // UPDATE t_table SET name = 'hoge' WHERE id = 1
     * $stmt->executeUpdate(['id' => 2]); // UPDATE t_table SET name = 'hoge' WHERE id = 2
     *
     * // SELECT
     * // 得られた Statement は fetchXXX に与えることができる
     * $stmt = $db->prepare('SELECT * FROM t_table WHERE id = :id');
     * $db->fetchTuple($stmt, ['id' => 1]); // SELECT * FROM t_table WHERE id = 1
     * $db->fetchTuple($stmt, ['id' => 2]); // SELECT * FROM t_table WHERE id = 2
     *
     * # 実際は DML のプロキシメソッドがあるのでそっちを使うことが多い（":id" のような省略記法を使っている。詳細は Statement の方を参照）
     * // SELECT
     * $stmt = $db->prepareSelect('t_table', ':id');
     * $db->fetchTuple($stmt, ['id' => 1]); // SELECT * FROM t_table WHERE id = 1
     * $db->fetchTuple($stmt, ['id' => 2]); // SELECT * FROM t_table WHERE id = 2
     * // INSERT
     * $stmt = $db->prepareInsert('t_table', [':id', ':name']);
     * $stmt->executeUpdate(['id' => 101, 'name' => 'hoge']);
     * $stmt->executeUpdate(['id' => 102, 'name' => 'fuga']);
     * // UPDATE
     * $stmt = $db->prepareUpdate('t_table', [':name'], [':id']);
     * $stmt->executeUpdate(['id' => 101, 'name' => 'HOGE']);
     * $stmt->executeUpdate(['id' => 102, 'name' => 'FUGA']);
     * // DELETE
     * $stmt = $db->prepareDelete('t_table', [':id']);
     * $stmt->executeUpdate(['id' => 101]);
     * $stmt->executeUpdate(['id' => 102]);
     * ```
     *
     * @used-by prepareSelect()
     * @used-by prepareInsert()
     * @used-by prepareUpdate()
     * @used-by prepareDelete()
     * @used-by prepareModify()
     * @used-by prepareReplace()
     *
     * @param string|QueryBuilder $sql クエリ
     * @param array $params パラメータ
     * @return Statement プリペアドステートメント
     */
    public function prepare($sql, $params = [])
    {
        if ($sql instanceof QueryBuilder) {
            return $this->prepare((string) $sql, $sql->getParams());
        }
        return new Statement($sql, $params, $this);
    }

    /**
     * 取得系クエリを実行する
     *
     * @inheritdoc Connection::executeQuery
     */
    public function executeQuery($query, array $params = [])
    {
        $params = array_map(function ($p) { return is_bool($p) ? (int) $p : $p; }, $params);

        if ($filter_path = $this->getInjectCallStack()) {
            $query = implode('', $this->_getCallStack($filter_path)) . $query;
        }

        // コンテキストを戻すための try～catch
        try {
            return $this->getSlaveConnection()->executeQuery($query, $params);
        }
        catch (\Exception $ex) {
            $this->unstackAll();
            throw $ex;
        }
    }

    /**
     * 更新系クエリを実行する
     *
     * @inheritdoc Connection::executeUpdate
     */
    public function executeUpdate($query, array $params = [])
    {
        $params = array_map(function ($p) { return is_bool($p) ? (int) $p : $p; }, $params);

        if ($this->getUnsafeOption('dryrun')) {
            return $this->queryInto($query, $params);
        }

        if ($this->getUnsafeOption('preparing')) {
            return $this->prepare($query, $params);
        }

        if ($filter_path = $this->getInjectCallStack()) {
            $query = implode('', $this->_getCallStack($filter_path)) . $query;
        }

        // コンテキストを戻すための try～catch
        try {
            return $this->getMasterConnection()->executeUpdate($query, $params);
        }
        catch (\Exception $ex) {
            $this->unstackAll();
            throw $ex;
        }
    }

    /**
     * dryrun モードへ移行する
     *
     * このメソッドを呼んだ直後は、更新系メソッドが実際には実行せずに実行されるクエリを返すようになる。
     * 後述する insertArray/updateArray などでクエリを取得したいときやテスト・確認などで便利。
     *
     * このメソッドは `setOption` を利用した {@link context()} メソッドで実装されている。つまり
     *
     * - `setOption('dryrun', true);`
     * - `context(['dryrun' => true]);`
     *
     * などと実質的にはほとんど同じ（後者に至っては全く同じ=移譲・糖衣構文）。
     * {@link context()} で実装されているということは下記のような処理が可能になる。
     *
     * ```php
     * $db->dryrun()->update('t_table', $data, $where);
     * // ↑の文を抜けると dryrun モードは解除されている
     *
     * $db->dryrun();
     * $db->update('t_table', $data, $where);
     * // 逆に言うとこのようなことはできない（dryrun モードになった直後にコンテキストが破棄され、元に戻っている）
     *
     * $db->dryrun()->t_table->update($data, $where);
     * // ただし、Gateway で dryrun したくてもこれは出来ない。 `->t_table` の時点で GC が実行され、 `->update` 実行時点では何も変わらなくなっているため
     *
     * $db->t_table->dryrun()->update($data, $where);
     * // Gateway で使いたい場合はこのように Gateway クラスに dryrun が生えているのでそれを使用する
     * ```
     *
     * @return $this 自分自身（のようなもの）
     */
    public function dryrun()
    {
        return $this->context(['dryrun' => true]);
    }

    /**
     * 空のレコードを返す
     *
     * 各カラムはテーブル定義のデフォルト値が格納される（それ以外はすべて null）。
     * ただし、引数で渡した $default 配列が優先される。
     *
     * $tablename がエンティティ名の場合はエンティティインスタンスで返す。
     *
     * ```php
     * # 配列で返す
     * $array = $db->getEmptyRecord('t_article');
     *
     * # エンティティで返す
     * $entity = $db->getEmptyRecord('Article');
     * ```
     *
     * @param string $tablename テーブル名
     * @param array|Entityable $default レコードのデフォルト値
     * @return array|Entityable 空レコード
     */
    public function getEmptyRecord($tablename, $default = [])
    {
        $table = $this->convertTableName($tablename);
        $columns = $this->getSchema()->getTableColumns($table);

        $record = $default;
        foreach ($columns as $column) {
            $cname = $column->getName();
            if (!array_key_exists($cname, $record)) {
                $record[$column->getName()] = $column->getDefault();
            }
        }

        if ($table !== $tablename) {
            $entityClass = $this->getEntityClass($table);
            /** @var Entityable $entity */
            $entity = new $entityClass(...$this->getEntityArgument());
            $record = $entity->assign($record);
        }

        return $record;
    }

    /**
     * ツリー構造の配列を一括で取り込む
     *
     * ツリー配列を水平的に走査して {@link changeArray()} でまとめて更新する。
     * 親・子・孫のような多階層でも動作する。
     * 外部キーで親のカラムを参照している場合、指定配列に含まれていなくても自動的に追加される。
     *
     * ```php
     * # t_ancestor に紐づく t_parent に紐づく t_child を一気に追加する
     * $db->import([
     *     't_ancestor' => [
     *         [
     *             'ancestor_name' => '祖先名',
     *             't_parent' => [
     *                 [
     *                     'parent_name' => '親名',
     *                     't_child' => [
     *                         [
     *                             'child_name' => '子供名1',
     *                         ],
     *                     ],
     *                 ],
     *             ],
     *         ],
     *     ],
     * ]);
     * // INSERT INTO t_ancestor (ancestor_id, ancestor_name) VALUES (1, "祖先名") ON DUPLICATE KEY UPDATE ancestor_id = VALUES(ancestor_id), ancestor_name = VALUES(ancestor_name)
     * // INSERT INTO t_parent (parent_id, parent_name, ancestor_id) VALUES (1, "親名", 1) ON DUPLICATE KEY UPDATE parent_id = VALUES(parent_id), parent_name = VALUES(parent_name), ancestor_id = VALUES(ancestor_id)
     * // INSERT INTO t_child (child_id, child_name, parent_id) VALUES (1, "子供名1", 1) ON DUPLICATE KEY UPDATE child_id = VALUES(child_id), child_name = VALUES(child_name), parent_id = VALUES(parent_id)
     * // 必要に応じて DELETE も行われる
     * ```
     *
     * @param array $datatree 取り込む配列
     * @return int affected rows
     */
    public function import($datatree)
    {
        $affected = 0;
        foreach ($datatree as $tableName => $rows) {
            $children = [];
            foreach ($rows as $n => &$row) {
                foreach ($row as $c => $v) {
                    if (is_array($v) && $this->getSchema()->hasTable($this->convertTableName($c))) {
                        $children[$c][$n] = $v;
                        unset($row[$c]);
                    }
                }
            }
            $tname = $this->convertTableName($tableName);
            $pks = $this->changeArray($tname, $rows, false);
            $nest = [];
            foreach ($children as $cn => $child) {
                foreach ($child as $n => $crows) {
                    foreach ($crows as $crow) {
                        foreach ($this->getSchema()->getForeignColumns($tname, $cn) as $ck => $pk) {
                            $crow[$ck] = $pks[$n][$pk];
                        }
                        $nest[$cn][] = $crow;
                    }
                }
            }
            $affected += count($pks) + $this->import($nest);
        }
        return $affected;
    }

    /**
     * CSV を取り込む
     *
     * CSV の各フィールドをテーブルカラムとしてインポートする。
     * mysql だけは native:true を指定することで LOAD DATA INFILE による高速なロードが可能。
     * 他の RDBMS はアプリでエミュレーションする。
     *
     * $options の詳細は下記。
     *
     * | name       | default              | 説明
     * |:--         |:--                   |:--
     * | native     | false                | RDBMS ネイティブの機能を使うか（mysql 専用）
     * | encoding   | mb_internal_encoding | 取り込むファイルのエンコーディング
     * | skip       | 0                    | 読み飛ばす行（ヘッダ読み飛ばしのために1を指定することが多い）
     * | delimiter  | ','                  | デリミタ文字（fgetcsv の第2引数）
     * | enclosure  | '"'                  | 囲いこみ文字（fgetcsv の第3引数）
     * | escape     | '\\'                 | エスケープ文字（fgetcsv の第4引数）
     * | eol        | "\n"                 | 行終端文字（native:true 時のみ有効）
     * | chunk      | null                 | 一度に実行するレコード数（native:false 時のみ有効）
     * | var_prefix | ''                   | mysql 変数のプレフィックス（native:true 時のみ有効だが気にしなくていい）
     *
     * native は非常に高速だが、制約も留意点も多い。
     * 非 native は汎用性があるが、ただの INSERT の羅列になるので速度的なメリットはない。
     *
     * $table は要素1の配列でも与えられる。その場合キーがテーブル名、値が取り込むカラム（配列）となる。
     * 配列でない場合（単純にテーブル名だけを与えた場合）は CSV 列とテーブル定義順が同じとみなしてすべて取り込む。
     * 少々ややこしいので下記の使用例を参照。
     *
     * ```php
     * # テーブル定義は t_hoge {id: int, name: string, data: blob, flg: tinyint} とする
     *
     * # CSV: "1,hoge,data,0" を取り込む例（テーブル定義と CSV が一致している最も単純な例）
     * $db->loadCsv('t_hoge', $csvfile);
     * // results: ['id' => 1, 'name' => 'hoge', 'data' => 'data', 'flg' => 0];
     *
     * # CSV: "hoge,0" を取り込む例（CSV に一部しか含まれていない例）
     * $db->loadCsv([
     *     // このように [テーブル名 => カラム] の配列で指定する
     *     't_hoge' => [
     *         // 原則としてこの配列の並び順と CSV の並び順がマップされる
     *         'name', // CSV 第1列
     *         'flg',  // CSV 第2列
     *         // それ以降（CSV 列からはみ出す分）は他のカラムとして直値を与えることができる
     *         'id'   => 1,
     *         'data' => null,
     *     ],
     * ], $csvfile);
     * // results: ['id' => 1, 'name' => 'hoge', 'data' => null, 'flg' => 0];
     *
     * # CSV: "1,hoge,dummy,0" を取り込む例（CSV に取り込みたくない列が含まれている例）
     * $db->loadCsv([
     *     't_hoge' => [
     *         'id',   // CSV 第1列
     *         'name', // CSV 第2列
     *         null,   // CSV 第3列。このように null を指定するとその列を読み飛ばすことができる
     *         'flg',  // CSV 第4列
     *     ],
     * ], $csvfile);
     * // results: ['id' => 1, 'name' => 'hoge', 'data' => null, 'flg' => 0];
     *
     * # CSV: "1,hoge" を HOGE として取り込む例（SQL 関数やクロージャを経由して取り込む例）
     * $db->loadCsv([
     *     't_hoge' => [
     *         'id',
     *         // 値に ? で列値を参照できる式を渡すことができる（この場合キーがカラム名指定になる）
     *         'name' => new Expression('UPPER(?)'),
     *         // 「php レイヤ」という点以外は↑と同じ（CSV 値が引数で渡ってくる）
     *         'name' => function ($v) { return strtoupper($v); },
     *     ],
     * ], $csvfile);
     * // results: ['id' => 1, 'name' => 'HOGE', 'data' => null];
     * ```
     *
     * mysql の native はクロージャが使えなかったり、null の扱いがアレだったり eol に注意したりと細かな点は異なるが原則的には同じ（サンプルは省略）。
     * ただし、 PDO に PDO::MYSQL_ATTR_LOCAL_INFILE: true を与えないと動作しないのでそれだけは注意。
     *
     * @param string|array $tableName テーブル名 or テーブル記法
     * @param string $filename CSV ファイル名
     * @param array $options CSV オプション
     * @return int|string|string[]|Statement 基本的には affected row. dryrun 中は文字列、preparing 中は Statement
     */
    public function loadCsv($tableName, $filename, $options = [])
    {
        $options += [
            'native'     => false,
            'encoding'   => mb_internal_encoding(),
            'skip'       => 0,
            'delimiter'  => ',',
            'enclosure'  => '"',
            'escape'     => '\\',
            'eol'        => "\n",
            'chunk'      => null,
            'var_prefix' => '',
        ];

        $tableName = array_each(TableDescriptor::forge($this, $tableName, []), function (&$carry, TableDescriptor $td) {
            $carry[$td->descriptor] = $td->column;
        }, []);
        list($tableName, $column) = first_keyvalue($tableName);

        // mysql 以外の RDBMS はローカルファイルの取り込みに対応してないようなので mysql の LOAD DATA FILE を直書きしている（つまり native ≒ mysql）
        if ($options['native']) {
            // php と mysql は charset 文字列が異なるので変換（他にもある気がするが頻出する utf8 のみ対応）
            $options['encoding'] = strcasecmp($options['encoding'], 'UTF-8') === 0 ? 'utf8' : $options['encoding'];
            $sql = array_sprintf([
                "LOAD DATA LOCAL INFILE %s" => $this->quote($filename),
                "INTO TABLE %s"             => $tableName,
                "CHARACTER SET %s"          => $this->quote($options['encoding']),
                "FIELDS TERMINATED BY %s"   => $this->quote($options['delimiter']),
                "ENCLOSED BY %s"            => $this->quote($options['enclosure']),
                "ESCAPED BY %s"             => $this->quote($options['escape']),
                "LINES TERMINATED BY %s"    => $this->quote($options['eol']),
                "IGNORE %d LINES"           => $options['skip'],
            ]);
            // LOAD DATA INFILE は結構高機能で、 CSV 列を変数に代入してその後 SET で自由な使うことができる。せっかくなので利用している
            if ($column) {
                // どうも連続して LOAD DATA を呼び出すと変数の charset が残存するバグがあるような気がする（CHARACTER SET を変えて呼び出すと確実に再現する）
                // まずありえないだろうが charset を変えつつ同じテーブル・カラムに LOAD すると死ぬので、プレフィックスを付与して別変数にする回避策を用意しておく
                $var_prefix = "@{$options['var_prefix']}";

                $r = -1;
                $vars = $sets = [];
                foreach ($column as $cname => $expr) {
                    $r++;
                    // native でクロージャはどう考えても無理
                    if ($expr instanceof \Closure) {
                        throw new \InvalidArgumentException("native can't accept Closure.");
                    }
                    // 値のみ指定ならそれをカラム名として CSV 列値を使う（ただし、 null はスキップ）
                    elseif (is_int($cname)) {
                        if ($expr === null) {
                            $vars[] = "{$var_prefix}__dummy__$r";
                            continue;
                        }
                        $vars[] = "{$var_prefix}$expr";
                        $sets[] = "$expr = {$var_prefix}$expr";
                    }
                    // php の bind 機構は使えないが、変数が宣言されているので変数に置換すれば実質的に bind になる
                    elseif ($expr instanceof Expression) {
                        $vars[] = "{$var_prefix}$cname";
                        $sets[] = "$cname = " . str_replace('?', "{$var_prefix}$cname", $expr);
                    }
                    else {
                        $vars[] = "{$var_prefix}$cname";
                        $sets[] = "$cname = " . $this->quote($expr);
                    }
                }
                $sql[] = "(" . implode(', ', $vars) . ") SET " . implode(', ', $sets);
            }
            return $this->executeUpdate(implode(" ", $sql));
        }
        else {
            $file = new \SplFileObject($filename);
            $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
            $file->setCsvControl($options['delimiter'], $options['enclosure'], $options['escape']);

            $columns = $column ?: array_keys($this->getSchema()->getTableColumns($tableName));
            $colnames = array_filter(array_keys(Adhoc::to_hash($columns)), 'strlen');
            $template = "INSERT INTO $tableName (%s) VALUES %s";

            $affected = [];
            $n = 0;
            $values = $params = [];
            $current = mb_internal_encoding();
            foreach ($file as $m => $fields) {
                if ($m < $options['skip']) {
                    continue;
                }

                if ($current !== $options['encoding']) {
                    mb_convert_variables($current, $options['encoding'], $fields);
                }

                $r = -1;
                $row = [];
                foreach ($columns as $cname => $expr) {
                    $r++;
                    // 範囲外は全部直値（マップするキーがないのでどうしようもない）
                    if (!isset($fields[$r])) {
                        $row[$cname] = $expr;
                    }
                    // 値のみ指定ならそれをカラム名として CSV 列値を使う（ただし、 null はスキップ）
                    elseif (is_int($cname)) {
                        if ($expr === null) {
                            continue;
                        }
                        $row[$expr] = $fields[$r];
                    }
                    // Expression はマーカーとしての役割なので作り直す
                    elseif ($expr instanceof Expression) {
                        $row[$cname] = new Expression($expr, $fields[$r]);
                    }
                    elseif ($expr instanceof \Closure) {
                        $row[$cname] = $expr($fields[$r]);
                    }
                    else {
                        $row[$cname] = $expr;
                    }
                }
                $row = $this->_normalize($tableName, $row);
                $set = $this->bindInto($row, $params);
                $values[] = '(' . implode(', ', $set) . ')';

                if (++$n === $options['chunk']) {
                    $sql = sprintf($template, implode(', ', $colnames), implode(', ', $values));
                    $affected[] = $this->executeUpdate($sql, $params);
                    $n = 0;
                    $values = $params = [];
                }
            }

            if ($values) {
                $sql = sprintf($template, implode(', ', $colnames), implode(', ', $values));
                $affected[] = $this->executeUpdate($sql, $params);
            }
            if ($this->getUnsafeOption('dryrun') || $this->getUnsafeOption('preparing')) {
                return $options['chunk'] ? $affected : reset($affected);
            }
            return array_sum($affected);
        }
    }

    /**
     * INSERT INTO SELECT 構文
     *
     * ```php
     * # 生クエリで INSERT INTO SELECT
     * $db->insertSelect('t_destination', 'SELECT * FROM t_source');
     * // INSERT INTO t_destination SELECT * FROM t_source
     *
     * # $columns を指定すると INSERT カラムを指定できる
     * $db->insertSelect('t_destination', 'SELECT * FROM t_source', ['id', 'name', 'content']);
     * // INSERT INTO t_destination (id, name, content) SELECT * FROM t_source
     *
     * # クエリビルダも渡せる
     * $db->insertSelect('t_destination', $db->select('t_source'));
     * // INSERT INTO t_destination SELECT * FROM t_source
     * ```
     *
     * @param string $tableName テーブル名
     * @param string|QueryBuilder $sql SELECT クエリ
     * @param array $columns カラム定義
     * @param array $params bind パラメータ
     * @return int|string|Statement 基本的には affected row. dryrun 中は文字列、preparing 中は Statement
     */
    public function insertSelect($tableName, $sql, $columns = [], array $params = [])
    {
        $tableName = $this->convertTableName($tableName);

        $query = $this->_builderToSql($sql, $params);

        $sql = "INSERT INTO $tableName " . concat('(', implode(', ', $columns), ') ') . $query;

        return $this->executeUpdate($sql, $params);
    }

    /**
     * BULK INSERT 構文
     *
     * BULK INSERT の仕様上、与えるカラム配列はキーが統一されていなければならない。
     *
     * ```php
     * $db->insertArray('t_table', [
     *     [
     *         'colA' => '1',                       // [カラム => 値] 形式
     *         'colB' => $db->raw('UPEER(?)', 'b'), // [カラム => Expression] 形式
     *     ],
     *     [
     *         'colA' => '2',
     *         'colB' => $db->raw('UPEER(?)', 'b'),
     *     ],
     * ]);
     * // INSERT INTO t_table (colA, colB) VALUES ('1', UPPER('b')), ('2', UPPER('b'))
     * ```
     *
     * @param string $tableName テーブル名
     * @param array|callable|\Generator $data カラムデータ配列あるいは Generator
     * @param int $chunk 分割 insert する場合はそのチャンクサイズ
     * @return int|string|string[]|Statement 基本的には affected row. dryrun 中は文字列、preparing 中は Statement
     */
    public function insertArray($tableName, $data, $chunk = 0)
    {
        $tableName = $this->_preaffect($tableName, $data);
        if (is_array($tableName)) {
            list($tableName, $data) = first_keyvalue($tableName);
        }
        $tableName = $this->convertTableName($tableName);

        $columns = null;
        $values = [];
        $params = [];

        $n = 0;
        $affected = [];
        $template = "INSERT INTO $tableName (%s) VALUES %s";
        foreach ($data as $row) {
            if (!is_array($row)) {
                throw new \InvalidArgumentException('$data\'s element must be array.');
            }

            $row = $this->_normalize($tableName, $row);
            $set = $this->bindInto($row, $params);

            if (!isset($columns)) {
                $columns = array_keys($set);
            }
            elseif ($columns !== array_keys($set)) {
                throw new \UnexpectedValueException('columns are not match.');
            }

            $values[] = '(' . implode(', ', $set) . ')';

            if (++$n === $chunk) {
                $sql = sprintf($template, implode(', ', $columns), implode(', ', $values));
                $affected[] = $this->executeUpdate($sql, $params);
                $n = 0;
                $values = [];
                $params = [];
            }
        }

        if ($values) {
            $sql = sprintf($template, implode(', ', $columns), implode(', ', $values));
            $affected[] = $this->executeUpdate($sql, $params);
        }

        if ($this->getUnsafeOption('dryrun') || $this->getUnsafeOption('preparing')) {
            return $chunk ? $affected : reset($affected);
        }
        return array_sum($affected);
    }

    /**
     * BULK UPDATE 構文
     *
     * 指定配列でバルクアップデートする。
     * `$data` の引数配列には必ず主キーを含める必要がある。
     *
     * ```php
     * # (id = 1,2,3) の行がそれぞれ与えられたデータに UPDATE される
     * $db->updateArray('tablename', [
     *     ['id' => 1, 'name' => 'hoge'],
     *     ['id' => 2, 'data' => 'FUGA'],
     *     ['id' => 3, 'name' => 'piyo', 'data' => 'PIYO'],
     * ], ['status_cd' => 50]);
     * // UPDATE tablename SET
     * //   name = CASE id WHEN '1' THEN 'hoge' WHEN '3' THEN 'piyo' ELSE name END,
     * //   data = CASE id WHEN '2' THEN 'FUGA' WHEN '3' THEN 'PIYO' ELSE data END
     * // WHERE (status_cd = '50') AND (id IN ('1','2','3'))
     * ```
     *
     * あくまで UPDATE であり、存在しない行には関与しない。
     *
     * `$data` の引数配列に含めた主キーは WHERE 句に必ず追加される。
     * したがって $identifier を指定するのは「`status_cd = 50` のもののみ」などといった「前提となるような条件」を書く。
     *
     * @param string $tableName テーブル名
     * @param array|callable|\Generator $data カラムデータあるいは Generator あるいは Generator を返す callable
     * @param array|mixed $identifier 束縛条件
     * @return int|string|string[]|Statement 基本的には affected row. dryrun 中は文字列、preparing 中は Statement
     */
    public function updateArray($tableName, $data, $identifier = [])
    {
        $tableName = $this->_preaffect($tableName, $data);
        if (is_array($tableName)) {
            list($tableName, $data) = first_keyvalue($tableName);
        }
        $tableName = $this->convertTableName($tableName);

        $pkey = $this->getSchema()->getTablePrimaryColumns($tableName);
        $columns = $this->_normalizes($tableName, $data, array_keys($pkey));
        $pkcols = array_intersect_key($columns, $pkey);
        $cvcols = array_diff_key($columns, $pkey);

        $params = [];
        $set = $this->bindInto($cvcols, $params);
        $sets = array_sprintf($set, '%2$s = %1$s', ', ');

        $condition = $this->_prewhere($tableName, $identifier);
        $condition[] = $this->getCompatiblePlatform()->getPrimaryCondition(array_uncolumns($pkcols), $tableName);
        $criteria = $this->whereInto($condition, $params);

        return $this->executeUpdate("UPDATE $tableName SET $sets" . ' WHERE ' . implode(' AND ', Adhoc::wrapParentheses($criteria)), $params);
    }

    /**
     * BULK UPSERT 構文
     *
     * 指定配列でバルクアップサートする（MySQL のみサポート）
     *
     * `$insertData` だけを指定した場合は「与えられた配列を modify する」という直感的な動作になる。
     * 更新は行われないので実質的に「重複を無視した挿入」のように振舞う。
     *
     * `$updateData` を指定すると存在する場合にその値が使用される。 **行ごとではなく一律である**ことに注意。
     * なので `$updateData` はレコードの配列ではなく [key => value] のシンプルな配列を与える。
     *
     * ```php
     * # 存在する行は (name = XXX) になり、追加される行は (name = hoge,fuga,piyo) になる
     * $db->modifyArray('tablename', [
     *     ['id' => 1, 'name' => 'hoge'],
     *     ['id' => 2, 'name' => 'fuga'],
     *     ['id' => 3, 'name' => 'piyo'],
     * ], ['name' => 'XXX']);
     * // INSERT INTO tablename (id, name) VALUES
     * //   ('1', 'hoge'),
     * //   ('2', 'fuga'),
     * //   ('3', 'piyo')
     * // ON DUPLICATE KEY UPDATE
     * //   name = 'XXX'
     *
     * # $updateData を指定しなければ VALUES(col) になる（≒変更されない）
     * $db->modifyArray('tablename', [
     *     ['id' => 1, 'name' => 'hoge'],
     *     ['id' => 2, 'name' => 'fuga'],
     *     ['id' => 3, 'name' => 'piyo'],
     * ]);
     * // INSERT INTO tablename (id, name) VALUES
     * //   ('1', 'hoge'),
     * //   ('2', 'fuga'),
     * //   ('3', 'piyo')
     * // ON DUPLICATE KEY UPDATE
     * //   id = VALUES(id),
     * //   name = VALUES(name)
     * ```
     *
     * @param string $tableName テーブル名
     * @param array|callable|\Generator $insertData カラムデータあるいは Generator
     * @param array $updateData カラムデータあるいは Generator
     * @param int $chunk 分割 insert する場合はそのチャンクサイズ
     * @return int|string|string[]|Statement 基本的には affected row. dryrun 中は文字列、preparing 中は Statement
     */
    public function modifyArray($tableName, $insertData, $updateData = [], $chunk = 0)
    {
        $tableName = $this->_preaffect($tableName, $insertData);
        if (is_array($tableName)) {
            list($tableName, $insertData) = first_keyvalue($tableName);
        }
        $tableName = $this->convertTableName($tableName);

        $updates = null;
        $updateParams = [];
        if ($updateData) {
            $updateData = $this->_normalize($tableName, $updateData);
            $updateData = $this->bindInto($updateData, $updateParams);
            $updates = array_sprintf($updateData, '%2$s = %1$s', ', ');
        }

        $columns = null;
        $values = [];
        $params = [];

        $n = 0;
        $affected = [];
        $template = "INSERT INTO $tableName (%s) VALUES %s ON DUPLICATE KEY UPDATE %s";
        foreach ($insertData as $row) {
            if (!is_array($row)) {
                throw new \InvalidArgumentException('$data\'s element must be array.');
            }

            $row = $this->_normalize($tableName, $row);
            $set = $this->bindInto($row, $params);

            if (!isset($columns)) {
                $columns = array_keys($set);
            }
            elseif ($columns !== array_keys($set)) {
                throw new \UnexpectedValueException('columns are not match.');
            }

            if (!isset($updates)) {
                $updates = array_sprintf($columns, '%1$s = VALUES(%1$s)', ', ');
            }

            $values[] = '(' . implode(', ', $set) . ')';

            if (++$n === $chunk) {
                $sql = sprintf($template, implode(', ', $columns), implode(', ', $values), $updates);
                $affected[] = $this->executeUpdate($sql, array_merge($params, $updateParams));
                $n = 0;
                $values = [];
                $params = [];
            }
        }

        if ($values) {
            $sql = sprintf($template, implode(', ', $columns), implode(', ', $values), $updates);
            $affected[] = $this->executeUpdate($sql, array_merge($params, $updateParams));
        }

        if ($this->getUnsafeOption('dryrun') || $this->getUnsafeOption('preparing')) {
            return $chunk ? $affected : reset($affected);
        }

        // カバレッジは sqlite だけで完結したい（mysql は毎回走らせてるので大丈夫でしょう）
        return array_sum($affected); // @codeCoverageIgnore
    }

    /**
     * INSERT+UPDATE+DELETE を同時に行う
     *
     * テーブル状態を指定した配列・条件に「持っていく」メソッドとも言える。
     *
     * このメソッドは複数のステートメントが実行され、 dryrun や prepare を使うことが出来ない。
     * また、可能な限りクエリを少なくかつ効率的に実行されるように構築されるので、テーブル定義や与えたデータによってはまったく構成の異なるクエリになる可能性がある（結果は同じになるが）。
     * 具体的には
     *
     * - BULK UPSERT をサポートしている場合（MySql）： modifyArray + delete 的な動作（最も高速）
     * - 与えたデータのカラムが共通の場合：            prepareModify + delete 的な動作（比較的高速）
     * - 上記以外（MySql）：                         各行 modify + delete 的な動作（比較的低速）
     * - 上記以外（MySql 以外）：                    各行 upsert + delete 的な動作（最も低速）
     *
     * という動作になる。つまり実質的にはほぼ mysql 専用で、他の DBMS では動きをなんとかして模倣しているだけに過ぎない。
     *
     * ```php
     * # `['category' => 'misc']` の世界でレコードが3行になる。指定した3行が無ければ作成され、有るなら更新され、id 指定している 1,2,3 以外のレコードは削除される
     * $db->changeArray('table_name', [
     *     ['id' => 1, 'name' => 'hoge'],
     *     ['id' => 2, 'name' => 'fuga'],
     *     ['id' => 3, 'name' => 'piyo'],
     * ], ['category' => 'misc']);
     * // mysql の場合
     * // INSERT INTO table_name (id, name) VALUES
     * //   ('1', 'hoge'),
     * //   ('2', 'fuga'),
     * //   ('3', 'piyo')
     * // ON DUPLICATE KEY UPDATE
     * //   id = VALUES(id),
     * //   name = VALUES(name)
     * // DELETE FROM table_name WHERE (category = 'misc') AND (NOT (id IN ('1', '2', '3')))
     * //
     * // mysql 以外の場合
     * // SELECT EXISTS (SELECT * FROM table_name WHERE id = '1')
     * // UPDATE table_name SET name = 'hoge' WHERE id = '1'
     * // SELECT EXISTS (SELECT * FROM table_name WHERE id = '2')
     * // UPDATE table_name SET name = 'fuga' WHERE id = '2'
     * // SELECT EXISTS (SELECT * FROM table_name WHERE id = '3')
     * // INSERT INTO table_name (id, name) VALUES ('3', 'piyo')
     * // DELETE FROM table_name WHERE (category = 'misc') AND (NOT (id IN ('1', '2', '3')))
     * ```
     *
     * @todo codeCoverageIgnore が点在してるのが気持ち悪いのでリファクタ
     *
     * @param string $tableName テーブル名
     * @param array $dataarray データ配列
     * @param array|mixed $identifier 束縛条件。 false を与えると DELETE 文自体を発行しない（速度向上と安全担保）
     * @return array 主キー配列
     */
    public function changeArray($tableName, $dataarray, $identifier)
    {
        $whereconds = arrayize($identifier);

        $tableName = $this->convertTableName($tableName);

        // 与えられていないなら消すだけ
        if (empty($dataarray)) {
            if ($identifier !== false) {
                $this->delete($tableName, $whereconds);
            }
            return [];
        }

        // バルクできそうか判定
        // @codeCoverageIgnoreStart
        $bulkable = $this->getPlatform() instanceof Platforms\MySqlPlatform;
        if ($bulkable) {
            $first = array_keys(reset($dataarray));
            foreach ($dataarray as $row) {
                // カラムが異なるならバルク出来ない
                if (array_keys($row) !== $first) {
                    $bulkable = false;
                    break;
                }
            }
            // バルクできるなら（カラムが同じなら）プリペアド可能
            if ($bulkable) {
                $stmt = $this->prepareModify($tableName, $first);
            }
        }
        // @codeCoverageIgnoreEnd

        // 主キー情報を漁っておく
        $pcols = $this->getSchema()->getTablePrimaryColumns($tableName);
        $autocolumn = optional($this->getSchema()->getTableAutoIncrement($tableName))->getName();

        $bulks = [];
        $primaries = [];
        foreach ($dataarray as $n => $row) {
            $pri = array_intersect_key($row, $pcols);
            // バルクできそうなら貯めておく
            // @codeCoverageIgnoreStart
            if ($bulkable && ($autocolumn == null || isset($pri[$autocolumn])) && count($pri) === count($pcols)) {
                $bulks[] = $row;
            }
            // @codeCoverageIgnoreEnd
            // だめそうなら個別更新
            else {
                // @codeCoverageIgnoreStart
                if (isset($stmt)) {
                    $stmt->executeUpdate($row);
                }
                else {
                    $this->modify($tableName, $row);
                }
                // @codeCoverageIgnoreEnd

                if ($autocolumn !== null && !isset($pri[$autocolumn])) {
                    $pri[$autocolumn] = $this->getLastInsertId($tableName, $autocolumn);
                }
            }
            $primaries[$n] = $pri;
        }

        // 主キー込みだった奴らを bulk upsert
        // @codeCoverageIgnoreStart
        if ($bulks) {
            $this->modifyArray($tableName, $bulks);
        }
        // @codeCoverageIgnoreEnd

        // 更新外を削除（$cond を queryInto してるのは誤差レベルではなく速度に差が出るから）
        if ($identifier !== false) {
            $cond = $this->getCompatiblePlatform()->getPrimaryCondition($primaries, $tableName);
            $whereconds[] = $this->queryInto("NOT ($cond)", $cond->getParams());
            $this->delete($tableName, $whereconds);
        }
        return $primaries;
    }

    /**
     * INSERT 構文
     *
     * ```php
     * # シンプルに1行 INSERT
     * $db->insert('tablename', [
     *     'id'   => 1,
     *     'name' => 'hoge',
     * ]);
     * // INSERT INTO tablename (id, name) VALUES ('1', 'hoge')
     *
     * # 特殊構文としてカラムとデータを別に与えられる
     * $db->insert('tablename.name', 'hoge');
     * // INSERT INTO tablename (name) VALUES ('hoge')
     * $db->insert('tablename.id, name', ['1', 'hoge']);
     * // INSERT INTO tablename (id, name) VALUES ('1', 'hoge')
     *
     * # TableDescriptor 的値や QueryBuilder を渡すと複数テーブルへ INSERT できる
     * // この用途は「垂直分割したテーブルへの INSERT」である（主キーを混ぜてくれるので小細工をする必要がない）。
     * $db->insert('t_article + t_article_misc', [
     *     'title'     => 'article_title', // t_article 側のデータ
     *     'misc_data' => 'misc_data',     // t_article_misc 側のデータ
     * ]);
     * // INSERT INTO t_article (title) VALUES ('article_title')
     * // INSERT INTO t_article_misc (id, misc_data) VALUES ('1', 'misc_data')
     *
     * # 複数テーブルへ INSERT は配列でも表現できる
     * $db->insert([
     *     't_article' => [
     *         'title' => 'article_title',
     *     ],
     *     '+t_article_misc' => [
     *         'misc_data' => 'misc_data',
     *     ],
     * ], []);
     * // INSERT INTO t_article (title) VALUES ('article_title')
     * // INSERT INTO t_article_misc (id, misc_data) VALUES ('1', 'misc_data')
     * ```
     *
     * @used-by insertOrThrow()
     * @used-by insertIgnore()
     * @used-by insertConditionally()
     *
     * @param string|array $tableName テーブル名
     * @param mixed $data INSERT データ配列
     * @return int|string|array|Statement 基本的には affected row. dryrun 中は文字列、preparing 中は Statement
     */
    public function insert($tableName, $data)
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 3 ? func_get_arg(2) : [];

        $tableName = $this->_preaffect($tableName, $data);

        // oracle には multiple insert なるものが有るらしいが・・・
        if ($tableName instanceof QueryBuilder) {
            $data += $tableName->getQueryPart('colval');
            $result = null;
            $affected = [];
            foreach ($tableName->getFromPart() as $table) {
                $owndata = array_map_key($data, function ($k) use ($table) {
                    return str_lchop($k, "{$table['alias']}.");
                });
                $jtype = $table['type'] ?? '';
                if ($jtype === '') {
                    $result = $this->insert($table['table'], $owndata, $opt);
                    $affected[] = $result;
                }
                elseif (strcasecmp($jtype, 'INNER') === 0) {
                    $affected[] = $this->insert($table['table'], $owndata, ['ignore' => $this->getCompatiblePlatform()->supportsIgnore()] + $opt);
                }
                else {
                    $affected[] = $this->insert($table['table'], $owndata, ['ignore' => false] + $opt);
                }
                $data += $this->_postaffect($table['table'], $owndata);
            }
            if ($this->getUnsafeOption('dryrun') || $this->getUnsafeOption('preparing')) {
                return $affected;
            }

            $affected = array_sum($affected);
            if (array_get($opt, 'primary')) {
                return $result;
            }
            return $affected;
        }
        if (is_array($tableName)) {
            list($tableName, $data) = first_keyvalue($tableName);
        }

        $tableName = $this->convertTableName($tableName);

        $params = [];
        $data = $this->_normalize($tableName, $data);
        $set = $this->bindInto($data, $params);

        $cplatform = $this->getCompatiblePlatform();
        $ignore = array_get($opt, 'ignore') ? $cplatform->getIgnoreSyntax() . ' ' : '';
        $sql = "INSERT {$ignore}INTO $tableName ";
        if (($condition = array_get($opt, 'where')) !== null) {
            if (is_array($condition)) {
                $condition = $this->selectNotExists($tableName, $condition);
            }
            if ($condition instanceof Queryable) {
                $condition = $condition->merge($params);
            }
            $fromDual = concat(' FROM ', $cplatform->getDualTable());
            $sql .= sprintf("(%s) SELECT %s$fromDual WHERE $condition", implode(', ', array_keys($set)), implode(', ', $set));
        }
        elseif ((!$cplatform->supportsInsertSet() || !$this->getUnsafeOption('insertSet'))) {
            $sql .= sprintf("(%s) VALUES (%s)", implode(', ', array_keys($set)), implode(', ', $set));
        }
        else {
            $sql .= "SET " . array_sprintf($set, '%2$s = %1$s', ', ');
        }
        $affected = $this->executeUpdate($sql, $params);
        if (!is_int($affected)) {
            return $affected;
        }

        if ($affected === 0 && array_get($opt, 'throw')) {
            throw new NonAffectedException('affected row is nothing.');
        }
        if ($affected === 0 && array_get($opt, 'primary') === 2) {
            return [];
        }
        if (array_get($opt, 'primary')) {
            return $this->_postaffect($tableName, $data);
        }
        return $affected;
    }

    /**
     * UPDATE 構文
     *
     * ```php
     * # シンプルに1行 UPDATE
     * $db->update('tablename', [
     *     'name' => 'hoge',
     * ], ['id' => 1]);
     * // UPDATE tablename SET name = 'hoge' WHERE id = '1'
     *
     * # 特殊構文としてカラムとデータを別に与えられる
     * $db->update('tablename.name', 'hoge', ['id' => 1]);
     * // UPDATE tablename SET name = 'hoge' WHERE id = '1'
     *
     * # TableDescriptor 的値や QueryBuilder を渡すと UPDATE JOIN になる（多分 mysql でしか動かない）
     * $db->update('t_article + t_comment', [
     *     'title'   => 'hoge',
     *     'comment' => 'fuga',
     * ]);
     * // UPDATE t_article INNER JOIN t_comment ON t_comment.article_id = t_article.article_id SET title = "hoge", comment = "fuga"
     *
     * # UPDATE JOIN は配列でも表現できる（やはり mysql のみ）
     * $db->update([
     *     't_article' => [
     *         'title' => 'hoge',
     *     ],
     *     '+t_comment' => [
     *         'comment' => 'fuga',
     *     ],
     * ], []);
     * // UPDATE t_article A INNER JOIN t_comment C ON C.article_id = A.article_id SET A.title = 'hoge', C.comment = 'fuga'
     * ```
     *
     * @used-by updateOrThrow()
     * @used-by updateIgnore()
     *
     * @param string|array|QueryBuilder $tableName テーブル名
     * @param mixed $data UPDATE データ配列
     * @param array|mixed $identifier WHERE 条件
     * @return int|string|array|Statement 基本的には affected row. dryrun 中は文字列、preparing 中は Statement
     */
    public function update($tableName, $data, $identifier = [])
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 4 ? func_get_arg(3) : [];

        $tableName = $this->_preaffect($tableName, $data);

        if ($tableName instanceof QueryBuilder) {
            $params = [];
            $data += $tableName->getQueryPart('colval');
            $set = $this->bindInto($data, $params);
            $sets = array_sprintf($set, '%2$s = %1$s', ', ');
            $tableName->addParam($params, 'join'); // クエリ順は from,join,set,where になるので join 位置に入れる
            $tableName->andWhere($identifier);
            return $this->executeUpdate($this->getCompatiblePlatform()->convertUpdateQuery($tableName, $sets), $tableName->getParams());
        }
        if (is_array($tableName)) {
            list($tableName, $data) = first_keyvalue($tableName);
        }

        $tableName = $this->convertTableName($tableName);

        $params = [];
        $data = $this->_normalize($tableName, $data);
        $set = $this->bindInto($data, $params);
        $sets = array_sprintf($set, '%2$s = %1$s', ', ');
        $criteria = $this->whereInto($this->_prewhere($tableName, $identifier), $params);

        $ignore = array_get($opt, 'ignore') ? $this->getCompatiblePlatform()->getIgnoreSyntax() . ' ' : '';
        $affected = $this->executeUpdate("UPDATE {$ignore}$tableName SET $sets" . ($criteria ? ' WHERE ' . implode(' AND ', Adhoc::wrapParentheses($criteria)) : ''), $params);
        if (!is_int($affected)) {
            return $affected;
        }

        if ($affected === 0 && array_get($opt, 'throw')) {
            throw new NonAffectedException('affected row is nothing.');
        }
        if ($affected === 0 && array_get($opt, 'primary') === 2) {
            return [];
        }
        if (array_get($opt, 'primary')) {
            return $this->_postaffect($tableName, $data + arrayize($identifier));
        }
        return $affected;
    }

    /**
     * DELETE 構文
     *
     * ```php
     * # シンプルに1行 DELETE
     * $db->delete('tablename', ['id' => 1]);
     * // DELETE FROM tablename WHERE id = '1'
     *
     * # TableDescriptor 的値や QueryBuilder を渡すと DELETE JOIN になる（多分 mysql でしか動かない）
     * $db->delete('t_article + t_comment', [
     *     't_article.article_id' => 1,
     * ]);
     * // DELETE t_article FROM t_article INNER JOIN t_comment ON t_comment.article_id = t_article.article_id WHERE t_article.article_id = 1
     * ```
     *
     * @used-by deleteOrThrow()
     * @used-by deleteIgnore()
     *
     * @param string|array|QueryBuilder $tableName テーブル名
     * @param array|mixed $identifier WHERE 条件
     * @return int|string|array|Statement 基本的には affected row. dryrun 中は文字列、preparing 中は Statement
     */
    public function delete($tableName, $identifier = [])
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 3 ? func_get_arg(2) : [];

        $tableName = $this->_preaffect($tableName, []);

        if ($tableName instanceof QueryBuilder) {
            $tableName->andWhere($identifier);
            return $this->executeUpdate($this->getCompatiblePlatform()->convertDeleteQuery($tableName, []), $tableName->getParams());
        }

        $tableName = $this->convertTableName($tableName);

        $params = [];
        $criteria = $this->whereInto($this->_prewhere($tableName, $identifier), $params);

        $ignore = array_get($opt, 'ignore') ? $this->getCompatiblePlatform()->getIgnoreSyntax() . ' ' : '';
        $affected = $this->executeUpdate("DELETE {$ignore}FROM $tableName" . ($criteria ? ' WHERE ' . implode(' AND ', Adhoc::wrapParentheses($criteria)) : ''), $params);
        if (!is_int($affected)) {
            return $affected;
        }

        if ($affected === 0 && array_get($opt, 'throw')) {
            throw new NonAffectedException('affected row is nothing.');
        }
        if ($affected === 0 && array_get($opt, 'primary') === 2) {
            return [];
        }
        if (array_get($opt, 'primary')) {
            return $this->_postaffect($tableName, arrayize($identifier));
        }
        return $affected;
    }

    /**
     * DELETE 構文（RESTRICT/NO ACTION を除外）
     *
     * CASCADE/SET NULL はむしろ「消えて欲しい/NULL になって欲しい」状況だと考えられるので何も手を加えない。
     * 簡単に言えば「外部キーエラーにならない**ような**」 DELETE を実行する。
     *
     * ```php
     * # childtable -> parenttable に RESTRICT な外部キーがある場合
     * $db->remove('parenttable', ['id' => 1]);
     * // DELETE FROM parenttable WHERE id = '1' AND (NOT EXISTS (SELECT * FROM childtable WHERE parenttable.id = childtable.parent_id))
     * ```
     *
     * @used-by removeOrThrow()
     * @used-by removeIgnore()
     *
     * @param string|array|QueryBuilder $tableName テーブル名
     * @param array|mixed $identifier WHERE 条件
     * @return int|string|array|Statement 基本的には affected row. dryrun 中は文字列、preparing 中は Statement
     */
    public function remove($tableName, $identifier = [])
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 3 ? func_get_arg(2) : [];

        $tableName = $this->_preaffect($tableName, []);
        $aliasName = null;

        if ($tableName instanceof QueryBuilder) {
            $originalBuilder = $tableName;
            $froms = $originalBuilder->getQueryPart('from');
            $from = reset($froms);
            $tableName = $from['table'];
            $aliasName = $from['alias'];
        }

        $tableName = $this->convertTableName($tableName);
        $identifier = $this->_prewhere($tableName, $identifier);

        $fkeys = $this->getSchema()->getForeignKeys($tableName, null);
        foreach ($fkeys as $fkey) {
            $action = strtoupper($fkey->getOption('onDelete') ?: 'RESTRICT');
            if (in_array($action, ['NO ACTION', 'RESTRICT'])) {
                $notexists = $this->select($fkey->getLocalTableName());
                $notexists->setSubwhere($tableName, $aliasName, $fkey->getName());
                $identifier[] = $notexists->notExists();
            }
        }

        if (isset($originalBuilder)) {
            return $this->delete($originalBuilder, $identifier);
        }

        $params = [];
        $criteria = $this->whereInto($identifier, $params);

        $ignore = array_get($opt, 'ignore') ? $this->getCompatiblePlatform()->getIgnoreSyntax() . ' ' : '';
        $affected = $this->executeUpdate("DELETE {$ignore}FROM $tableName" . ($criteria ? ' WHERE ' . implode(' AND ', Adhoc::wrapParentheses($criteria)) : ''), $params);
        if (!is_int($affected)) {
            return $affected;
        }

        if ($affected === 0 && array_get($opt, 'throw')) {
            throw new NonAffectedException('affected row is nothing.');
        }
        if ($affected === 0 && array_get($opt, 'primary') === 2) {
            return [];
        }
        if (array_get($opt, 'primary')) {
            return $this->_postaffect($tableName, arrayize($identifier));
        }
        return $affected;
    }

    /**
     * DELETE 構文（RESTRICT/NO ACTION も削除）
     *
     * RESTRICT/NO ACTION な子テーブルレコードを先に削除してから実行する。
     * 簡単に言えば「外部キーエラーにならないように**してから**」 DELETE を実行する。
     *
     * 実質的には RESTRICT/NO ACTION を無視して CASCADE 的な動作と同等なので注意して使用すべき。
     * （RESTRICT/NO ACTION にしているのには必ず理由があるはず）。
     *
     * 相互参照外部キーでかつそれらが共に「RESTRICT/NO ACTION」だと無限ループになるので注意。
     * （そのような外部キーはおかしいと思うので特にチェックしない）。
     *
     * さらに、複合カラム外部キーだと行値式 IN を使うので SQLServer では実行できない。また、 mysql 5.6 以下ではインデックスが効かないので注意。
     * 単一カラム外部キーなら問題ない。
     *
     * ```php
     * # childtable -> parenttable に RESTRICT な外部キーがある場合
     * $db->destroy('parenttable', ['status' => 'deleted']);
     * // DELETE FROM childtable WHERE (cid) IN (parenttable id FROM parenttable WHERE status = 'deleted')
     * // DELETE FROM parenttable WHERE status = 'deleted'
     * ```
     *
     * @used-by destroyOrThrow()
     * @used-by destroyIgnore()
     *
     * @param string|array $tableName テーブル名
     * @param array|mixed $identifier WHERE 条件
     * @return int|string[] 基本的には affected row. dryrun 中は文字列配列
     */
    public function destroy($tableName, $identifier = [])
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 3 ? func_get_arg(2) : [];

        $tableName = $this->_preaffect($tableName, []);
        $aliasName = null;

        $tableName = $this->convertTableName($tableName);
        $identifier = $this->_prewhere($tableName, $identifier);

        $affected = [];
        $fkeys = $this->getSchema()->getForeignKeys($tableName, null);
        foreach ($fkeys as $fkey) {
            $action = strtoupper($fkey->getOption('onDelete') ?: 'RESTRICT');
            if (in_array($action, ['NO ACTION', 'RESTRICT'])) {
                $pselect = $this->select([$tableName => $fkey->getForeignColumns()], $identifier);
                $subwhere = [];
                if (array_get($opt, 'in')) {
                    $pvals = $pvals ?? $pselect->array();
                    $pvals2 = array_rmap($pvals, 'array_combine', $fkey->getLocalColumns());
                    $pcond = $this->getCompatiblePlatform()->getPrimaryCondition($pvals2, $fkey->getLocalTableName());
                    $subwhere[] = $this->queryInto($pcond) ?: 'FALSE';
                }
                else {
                    $ckey = implode(',', $fkey->getLocalColumns());
                    $subwhere["($ckey)"] = $pselect;
                }
                $affected = array_merge($affected, (array) $this->destroy($fkey->getLocalTableName(), $subwhere, $opt));
            }
        }

        $params = [];
        $criteria = $this->whereInto($identifier, $params);

        $ignore = array_get($opt, 'ignore') ? $this->getCompatiblePlatform()->getIgnoreSyntax() . ' ' : '';
        $affected[] = $this->executeUpdate("DELETE {$ignore}FROM $tableName" . ($criteria ? ' WHERE ' . implode(' AND ', Adhoc::wrapParentheses($criteria)) : ''), $params);

        if ($this->getUnsafeOption('dryrun')) {
            return $affected;
        }

        $affected = array_sum($affected);
        if ($affected === 0 && array_get($opt, 'throw')) {
            throw new NonAffectedException('affected row is nothing.');
        }
        if ($affected === 0 && array_get($opt, 'primary') === 2) {
            return [];
        }
        if (array_get($opt, 'primary')) {
            return $this->_postaffect($tableName, arrayize($identifier));
        }
        return $affected;
    }

    /**
     * DELETE 構文（指定件数を残して削除）
     *
     * $orderBy の順番で $limit 件残すように DELETE を発行する。
     * $groupBy を指定するとそのグルーピングの世界で $limit 件残すようにそれぞれ削除する。
     * 条件を指定した場合や同値が存在した場合、指定件数より残ることがあるが、少なくなることはない。
     *
     * $orderBy は単一しか対応していない（大抵の場合は日付的なカラムの単一指定のはず）。
     * "+column" のように + を付与すると昇順、 "-column" のように - を付与すると降順になる（未指定時は昇順）。
     * 一応 ['column' => true] のような orderBy 指定にも対応している。
     *
     * 削除には行値式 IN を使うので SQLServer では実行できない。また、 mysql 5.6 以下ではインデックスが効かないので注意。
     * 単一主キーなら問題ない。
     *
     * ```php
     * # logs テーブルから log_time の降順で 10 件残して削除
     * $db->reduce('logs', 10, '-log_time');
     *
     * # logs テーブルから log_time の降順でカテゴリごとに 10 件残して削除
     * $db->reduce('logs', 10, '-log_time', ['category']);
     *
     * # logs テーブルから log_time の降順でカテゴリごとに 10 件残して削除するが直近1ヶ月は残す（1ヶ月以上前を削除対象とする）
     * $db->reduce('logs', 10, '-log_time', ['category'], ['log_time < ?' => date('Y-m-d', strtotime('now -1 month'))]);
     * ```
     *
     * @used-by reduceOrThrow()
     *
     * @param string|array $tableName テーブル名
     * @param int $limit 残す件数
     * @param string|array $orderBy 並び順
     * @param string|array $groupBy グルーピング条件
     * @param array|mixed $identifier WHERE 条件
     * @return int|string 基本的には affected row. dryrun 中は文字列
     */
    public function reduce($tableName, $limit = null, $orderBy = [], $groupBy = [], $identifier = [])
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 6 ? func_get_arg(5) : [];

        $orderBy = arrayize($orderBy);
        $groupBy = arrayize($groupBy);
        $identifier = arrayize($identifier);

        $simplize = function ($v) { return last_value(explode('.', $v)); };

        $tableName = $this->_preaffect($tableName, []);
        if ($tableName instanceof QueryBuilder) {
            $limit = $tableName->getQueryPart('limit') ?: $limit;
            $orderBy = array_merge($tableName->getQueryPart('orderBy'), $orderBy);
            $groupBy = array_merge($tableName->getQueryPart('groupBy'), $groupBy);
            if ($where = $tableName->getQueryPart('where')) {
                $identifier[] = new Expression(implode(' AND ', Adhoc::wrapParentheses(array_map($simplize, $where))), $tableName->getParams('where'));
            }
            $tableName = first_value($tableName->getFromPart())['table'];
        }

        $limit = intval($limit);
        if ($limit < 1) {
            throw new \InvalidArgumentException("\$limit must be > 0 ($limit).");
        }

        $orderBy = array_kmap($orderBy, function ($v, $k) use ($simplize) { return is_int($k) ? $simplize($v) : ($v ? '+' : '-') . $simplize($k); });
        if (count($orderBy) !== 1) {
            throw new \InvalidArgumentException("\$orderBy must be === 1.");
        }
        $orderBy = reset($orderBy);

        $groupBy = array_map($simplize, $groupBy);

        $tableName = $this->convertTableName($tableName);
        $BASETABLE = '__dbml_base_table';
        $JOINTABLE = '__dbml_join_table';
        $TEMPTABLE = '__dbml_temp_table';
        $GROUPTABLE = '__dbml_group_table';
        $VALUETABLE = '__dbml_value_table';

        $pcols = $this->getSchema()->getTablePrimaryKey($tableName)->getColumns();
        $ascdesc = $orderBy[0] !== '-';
        $glsign = ($ascdesc ? '>' : '<');
        $orderBy = ltrim($orderBy, '-+');

        // 境界値が得られるサブクエリ
        $subquery = $this->select(["$tableName $VALUETABLE" => $orderBy])
            ->where(array_map(function ($gk) use ($GROUPTABLE, $VALUETABLE) { return "$GROUPTABLE.$gk = $VALUETABLE.$gk"; }, $groupBy))
            ->orderBy($groupBy + [$orderBy => $ascdesc])
            ->limit(1, $limit - 1);

        // グルーピングしないなら主キー指定で消す必要はなく、直接比較で消すことができる（結果は変わらないがパフォーマンスが劇的に違う）
        if (!$groupBy) {
            $identifier["$tableName.$orderBy $glsign ?"] = $subquery->wrap("SELECT * FROM", "AS $TEMPTABLE");
        }
        else {
            // グループキーと境界値が得られるサブクエリ
            $subtable = $this->select([
                "$tableName $GROUPTABLE" => $groupBy + [$orderBy => $subquery],
            ], $identifier)->groupBy($groupBy);
            // ↑と JOIN して主キーが得られるサブクエリ
            $select = $this->select([
                "$tableName $BASETABLE" => $pcols,
            ])->innerJoinOn([$JOINTABLE => $subtable],
                array_merge(array_map(function ($gk) use ($BASETABLE, $JOINTABLE) { return "$JOINTABLE.$gk = $BASETABLE.$gk"; }, $groupBy), [
                    "$BASETABLE.$orderBy $glsign $JOINTABLE.$orderBy",
                ])
            );
            // ↑を主キー where に設定する
            $identifier["(" . implode(',', $pcols) . ")"] = $select->wrap("SELECT * FROM", "AS $TEMPTABLE");
        }

        return $this->delete($tableName, $identifier, $opt);
    }

    /**
     * 行が無かったら INSERT、有ったら UPDATE
     *
     * アプリレイヤーで SELECT EXISTS（排他ロック） で行を確認し、無ければ INSERT 有れば UPDATE する。
     * RDBMS に依存せず癖が少ない行置換メソッドであるが、 mysql ではギャップロック同士が競合せず deadlock になるケースが極稀に存在する。
     *
     * OrThrow 版の戻り値は「本当に更新した主キー配列」になる。
     * 下記のパターンがある。
     *
     * - 存在しなかったので insert を行った (≒ lastInsertId を含む主キーを返す)
     * - 存在したので update を行った (＝ 存在した行の主キーを返す)
     * - 存在したが更新データに主キーが含まれていたので**主キーも含めて更新を行った** (＝ 存在した行の**更新後**の主キーを返す)
     *
     * 言い換えれば「更新したその行にアクセスするに足る主キー配列」を返す。
     *
     * ```php
     * # id 的列が指定されていないかつ AUTOINCREMENT の場合は INSERT 確定となる
     * $db->upsert('tablename', ['name' => 'hoge']);
     * // INSERT INTO tablename (name) VALUES ('piyo') -- 連番は AUTOINCREMENT
     *
     * # id 的列が指定されているか AUTOINCREMENT でない場合は SELECT EXISTS でチェックする
     * $db->upsert('tablename', ['id' => 1, 'name' => 'hoge']);
     * // SELECT EXISTS (SELECT * FROM tablename WHERE id = '1')
     * //   存在しない: INSERT INTO tablename (id, name) VALUES ('1', 'hoge')
     * //   存在する:   UPDATE tablename SET name = 'hoge' WHERE id = '1'
     * ```
     *
     * @used-by upsertOrThrow()
     * @used-by upsertConditionally()
     *
     * @param string|array $tableName テーブル名
     * @param mixed $insertData INSERT データ配列
     * @param mixed $updateData UPDATE データ配列
     * @return int|array affected rows. if update return 0 or 2, else insert return 1
     */
    public function upsert($tableName, $insertData, $updateData = null)
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 4 ? func_get_arg(3) : [];

        $tableName = $this->_preaffect($tableName, $insertData);

        if ($tableName instanceof QueryBuilder) {
            throw new \InvalidArgumentException('upsert is not supported QueryBuilder.');
        }
        if (is_array($tableName)) {
            list($tableName, $insertData) = first_keyvalue($tableName);
            if ($updateData !== null && !is_hasharray($updateData)) {
                $updateData = array_combine(array_keys($insertData), $updateData);
            }
        }

        $originalName = $tableName;
        $tableName = $this->convertTableName($tableName);

        $condition = array_get($opt, 'where');
        if ($condition !== null) {
            $params = [];
            if (is_array($condition)) {
                $condition = $this->selectNotExists($tableName, $condition);
            }
            if ($condition instanceof Queryable) {
                $condition = $condition->merge($params);
            }
            $fromDual = concat(' FROM ', $this->getCompatiblePlatform()->getDualTable());
            if (!$this->fetchValue("SELECT 1$fromDual WHERE $condition", $params)) {
                return [];
            }
        }

        $pcols = $this->getSchema()->getTablePrimaryColumns($tableName);
        $wheres = array_intersect_key($insertData, $pcols);

        if (!count($wheres)) {
            if ($this->getSchema()->getTableAutoIncrement($tableName)) {
                return $this->insert($originalName, $insertData, $opt);
            }
        }

        if (count($wheres) !== count($pcols)) {
            throw new \UnexpectedValueException("no match primary key's data");
        }

        if ($this->exists($tableName, $wheres, true)) {
            if ($updateData === null) {
                $updateData = array_diff_key($insertData, $pcols);
            }
            $affected = $this->update($originalName, $updateData, $wheres, $opt);
            return is_int($affected) && $affected === 1 ? 2 : $affected;
        }
        return $this->insert($originalName, $insertData, $opt);
    }

    /**
     * MERGE 構文
     *
     * RDBMS で方言・効果がかなり激しい。
     *
     * - sqlite：     存在しないので {@link upsert()} に委譲される
     * - mysql：      INSERT ～ ON DUPLICATE KEY が実行される
     * - postgresql： INSERT ～ ON CONFLICT DO UPDATE が実行される
     * - sqlserver：  MERGE があるが複雑すぎるので {@link upsert()} に委譲される
     *
     * ```php
     * # シンプルな INSERT ～ ON DUPLICATE KEY
     * $db->modify('tablename', [
     *     'id'   => 1,
     *     'name' => 'hoge',
     * ]);
     * // INSERT INTO tablename SET id = '1', name = 'hoge' ON DUPLICATE KEY UPDATE id = VALUES(id), name = VALUES(name)
     *
     * # $updateData で更新時のデータを指定できる
     * $db->modify('tablename', [
     *     'id'   => 1,
     *     'name' => 'hoge',
     * ], ['name' => 'fuga']);
     * // INSERT INTO tablename SET id = '1', name = 'hoge' ON DUPLICATE KEY UPDATE name = 'fuga'
     * ```
     *
     * @used-by modifyOrThrow()
     * @used-by modifyIgnore()
     * @used-by modifyConditionally()
     *
     * @codeCoverageIgnore カバレッジは sqlite だけで完結したい（mysql は毎回走らせてるので大丈夫でしょう）
     *
     * @param string|array $tableName テーブル名
     * @param mixed $insertData INSERT データ配列
     * @param mixed $updateData UPDATE データ配列
     * @return int|array|Statement affected rows. if update return 0 or 2, else insert return 1
     */
    public function modify($tableName, $insertData, $updateData = [])
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 4 ? func_get_arg(3) : [];

        if (!$this->getCompatiblePlatform()->supportsMerge()) {
            return $this->upsert($tableName, $insertData, $updateData ?: null, $opt);
        }

        $tableName = $this->_preaffect($tableName, $insertData);

        if ($tableName instanceof QueryBuilder) {
            throw new \InvalidArgumentException('upsert is not supported QueryBuilder.');
        }
        if (is_array($tableName)) {
            list($tableName, $insertData) = first_keyvalue($tableName);
            if ($updateData && !is_hasharray($updateData)) {
                $updateData = array_combine(array_keys($insertData), $updateData);
            }
        }
        $updatable = !!$updateData;

        $tableName = $this->convertTableName($tableName);

        $insertData = $this->_normalize($tableName, $insertData);
        $updateData = $this->_normalize($tableName, $updateData);
        $updateData = $this->getCompatiblePlatform()->convertMergeData($insertData, $updateData);

        $params = [];
        $sets1 = $this->bindInto($insertData, $params);
        $condition = array_get($opt, 'where');
        if (is_array($condition)) {
            $condition = $this->selectNotExists($tableName, $condition);
        }
        if ($condition instanceof Queryable) {
            $condition = $condition->merge($params);
        }
        $sets2 = $this->bindInto($updateData, $params);

        $pkname = $this->getSchema()->getTablePrimaryKey($tableName)->getName();

        $cplatform = $this->getCompatiblePlatform();
        $ignore = array_get($opt, 'ignore') ? $cplatform->getIgnoreSyntax() . ' ' : '';
        $sql = "INSERT {$ignore}INTO $tableName ";
        if ($condition !== null) {
            $fromDual = concat(' FROM ', $cplatform->getDualTable());
            $sql .= sprintf("(%s) SELECT %s$fromDual WHERE $condition", implode(', ', array_keys($sets1)), implode(', ', $sets1));
        }
        elseif ((!$cplatform->supportsInsertSet() || !$this->getUnsafeOption('insertSet'))) {
            $sql .= sprintf("(%s) VALUES (%s)", implode(', ', array_keys($sets1)), implode(', ', $sets1));
        }
        else {
            $sql .= "SET " . array_sprintf($sets1, '%2$s = %1$s', ', ');
        }
        $sql .= ' ' . $this->getCompatiblePlatform()->getMergeSQL($sets2, $pkname);
        $affected = $this->executeUpdate($sql, $params);
        if (!is_int($affected)) {
            return $affected;
        }

        if ($affected === 0 && array_get($opt, 'throw')) {
            throw new NonAffectedException('affected row is nothing.');
        }
        if ($affected === 0 && array_get($opt, 'primary') === 2) {
            return [];
        }
        if (array_get($opt, 'primary')) {
            return $this->_postaffect($tableName, $updatable ? $updateData : $insertData);
        }
        return $affected;
    }

    /**
     * REPLACE 構文
     *
     * 標準のよくある REPLACE とは違って、元のカラム値は維持される。
     * ただし、REPLACE であることに変わりはないので DELETE -> INSERT されるため外部キー（特に CASCADE DELETE） に注意。
     *
     * ```php
     * # シンプルな REPLACE
     * $db->replace('tablename', [
     *     'id'   => 1,
     *     'name' => 'hoge',
     * ]);
     * // REPLACE INTO tablename (id, name, othercolumn) SELECT '1', 'hoge', othercolumn FROM (SELECT NULL) __T LEFT JOIN tablename ON id = '1'
     * ```
     *
     * @used-by replaceOrThrow()
     *
     * @param string|array $tableName テーブル名
     * @param mixed $data REPLACE データ配列
     * @return int|array|Statement affected rows. if update return 0 or 2, else insert return 1
     */
    public function replace($tableName, $data)
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 3 ? func_get_arg(2) : [];

        $tableName = $this->convertTableName($tableName);

        $params = [];
        $data = $this->_normalize($tableName, $data);
        $sets = $this->bindInto($data, $params);

        $primary = $this->getSchema()->getTablePrimaryColumns($tableName);
        $columns = $this->getSchema()->getTableColumns($tableName);

        $selects = [];
        foreach ($columns as $cname => $column) {
            $selects[$cname] = array_get($sets, $cname, $cname);
        }

        $criteria = $this->whereInto(array_intersect_key($data, $primary), $params);

        $sql = "REPLACE INTO $tableName (" . implode(', ', array_keys($selects)) . ") ";
        $sql .= "SELECT " . implode(', ', $selects) . " FROM (SELECT NULL) __T ";
        $sql .= "LEFT JOIN $tableName ON " . ($criteria ? implode(' AND ', Adhoc::wrapParentheses($criteria)) : '1=0');

        $affected = $this->executeUpdate($sql, $params);
        if (!is_int($affected)) {
            return $affected;
        }

        /** @noinspection PhpStatementHasEmptyBodyInspection REPLACE が 0 を返すことはない */
        if ($affected === 0 && array_get($opt, 'throw')) {
            // throw new NonAffectedException('affected row is nothing.');
        }
        if (array_get($opt, 'primary')) {
            return $this->_postaffect($tableName, $data);
        }
        return $affected;
    }

    /**
     * 行を複製する
     *
     * ```php
     * # 最もシンプルな例。単純に tablename のレコードが2倍になる（主キーが重複してしまうので AUTOINCREMENT の場合のみ）
     * $db->duplicate('tablename');
     * // INSERT INTO tablename (name, other_columns) SELECT name AS name, other_columns AS other_columns FROM tablename
     *
     * # 複製データと条件を指定して複製
     * $db->duplicate('tablename', [
     *     'name' => 'copied',
     * ], ['id' => 1]);
     * // INSERT INTO tablename (name, other_columns) SELECT 'copied' AS name, other_columns AS other_columns FROM tablename WHERE id = '1'
     * ```
     *
     * @param string $targetTable 挿入するテーブル名
     * @param array $overrideData selectしたデータを上書きするデータ
     * @param array|mixed $where 検索条件
     * @param string $sourceTable 元となるテーブル名。省略すると $targetTable と同じになる
     * @return int|Statement affected rows
     */
    public function duplicate($targetTable, array $overrideData = [], $where = [], $sourceTable = null)
    {
        $sourceTable = $sourceTable === null ? $targetTable : $sourceTable;
        $targetTable = $this->convertTableName($targetTable);
        $sourceTable = $this->convertTableName($sourceTable);

        $metatarget = $this->getSchema()->getTableColumns($targetTable);
        $metasource = $this->getSchema()->getTableColumns($sourceTable);

        // 主キーが指定されてないなんておかしい（必ず重複してしまう）
        // しかし AUTO INCREMENT を期待して敢えて指定してないのかもしれない
        // したがって、「同じテーブルの場合は AUTO INCREMENT な主キーはselectしない」で対応できる（その結果例外が出てもそれは呼び出し側の責任）
        if ($sourceTable === $targetTable) {
            $autocolumn = optional($this->getSchema()->getTableAutoIncrement($targetTable))->getName();
            $metasource = array_diff_key($metasource, [$autocolumn => null]);
        }

        $overrideData = $this->_normalize($targetTable, $overrideData);

        $params = [];
        $overrideSet = $this->bindInto($overrideData, $params);
        $overrideSet = array_map(function ($v) { return new Expression($v); }, $overrideSet);

        foreach ($metasource as $name => $dummy) {
            if (array_key_exists($name, $metatarget) && !array_key_exists($name, $overrideSet)) {
                $overrideSet[$name] = new Expression($name);
            }
        }

        $select = $this->select([$sourceTable => $overrideSet], $where);
        $sql = "INSERT INTO $targetTable (" . implode(', ', array_keys($overrideSet)) . ") $select";

        return $this->executeUpdate($sql, array_merge($params, $select->getParams()));
    }

    /**
     * TRUNCATE 構文
     *
     * ```php
     * $db->truncate('tablename');
     * // TRUNCATE tablename
     * ```
     *
     * @param string $tableName テーブル名
     * @param bool $cascade CASCADE フラグ。PostgreSql の場合のみ有効
     * @return int affected rows
     */
    public function truncate($tableName, $cascade = false)
    {
        $tableName = $this->convertTableName($tableName);
        $sql = $this->getCompatiblePlatform()->getTruncateTableSQL($tableName, $cascade);
        $affected = $this->executeUpdate($sql);
        if (!$this->getCompatiblePlatform()->supportsResetAutoIncrementOnTruncate() && $this->getSchema()->getTableAutoIncrement($tableName)) {
            $this->resetAutoIncrement($tableName);
        }
        return $affected;
    }

    /**
     * 最後に挿入した ID を返す
     *
     * @param string $tableName テーブル名。PostgreSql の場合のみ有効
     * @param string $columnName カラム名。PostgreSql の場合のみ有効
     * @return null|string 最後に挿入した ID
     */
    public function getLastInsertId($tableName = null, $columnName = null)
    {
        return $this->getMasterConnection()->lastInsertId($this->getCompatiblePlatform()->getIdentitySequenceName($tableName, $columnName));
    }

    /**
     * 自動採番列をリセットする
     *
     * @param string $tableName テーブル名
     * @param int $seq 採番列の値
     */
    public function resetAutoIncrement($tableName, $seq = 1)
    {
        $autocolumn = $this->getSchema()->getTableAutoIncrement($tableName);
        if ($autocolumn === null) {
            throw new \UnexpectedValueException("table '$tableName' is not auto incremental.");
        }

        $queries = $this->getCompatiblePlatform()->getResetSequenceExpression($tableName, $autocolumn->getName(), $seq);
        foreach ($queries as $query) {
            $this->executeUpdate($query);
        }
    }

    /**
     * yaml 文字列をパースする
     *
     * @ignore
     *
     * @param string $yaml yaml 文字列
     * @param bool $usecache キャッシュするか（テスト用）
     * @return mixed yaml パース結果
     */
    public function parseYaml($yaml, $usecache = true)
    {
        static $cache = [];
        if (!$usecache || !isset($cache[$yaml])) {
            $cache[$yaml] = call_safely(function ($yaml) {
                return $this->getUnsafeOption('yamlParser')($yaml);
            }, $yaml);
        }
        return $cache[$yaml];
    }
}
