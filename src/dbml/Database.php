<?php

namespace ryunosuke\dbml;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;
use ryunosuke\dbml\Entity\Entity;
use ryunosuke\dbml\Entity\Entityable;
use ryunosuke\dbml\Exception\NonAffectedException;
use ryunosuke\dbml\Exception\NonSelectedException;
use ryunosuke\dbml\Exception\TooManyException;
use ryunosuke\dbml\Gateway\TableGateway;
use ryunosuke\dbml\Generator\AbstractGenerator;
use ryunosuke\dbml\Generator\ArrayGenerator;
use ryunosuke\dbml\Generator\CsvGenerator;
use ryunosuke\dbml\Generator\JsonGenerator;
use ryunosuke\dbml\Generator\Yielder;
use ryunosuke\dbml\Logging\LoggerChain;
use ryunosuke\dbml\Logging\Middleware as LoggingMiddleware;
use ryunosuke\dbml\Metadata\CompatibleConnection;
use ryunosuke\dbml\Metadata\CompatiblePlatform;
use ryunosuke\dbml\Metadata\Schema;
use ryunosuke\dbml\Mixin\AffectConditionallyTrait;
use ryunosuke\dbml\Mixin\AffectIgnoreTrait;
use ryunosuke\dbml\Mixin\AffectOrThrowTrait;
use ryunosuke\dbml\Mixin\AggregateTrait;
use ryunosuke\dbml\Mixin\EntityForAffectTrait;
use ryunosuke\dbml\Mixin\EntityForUpdateTrait;
use ryunosuke\dbml\Mixin\EntityInShareTrait;
use ryunosuke\dbml\Mixin\EntityOrThrowTrait;
use ryunosuke\dbml\Mixin\ExportTrait;
use ryunosuke\dbml\Mixin\FetchOrThrowTrait;
use ryunosuke\dbml\Mixin\OptionTrait;
use ryunosuke\dbml\Mixin\PrepareTrait;
use ryunosuke\dbml\Mixin\SelectAggregateTrait;
use ryunosuke\dbml\Mixin\SelectForAffectTrait;
use ryunosuke\dbml\Mixin\SelectForUpdateTrait;
use ryunosuke\dbml\Mixin\SelectInShareTrait;
use ryunosuke\dbml\Mixin\SelectOrThrowTrait;
use ryunosuke\dbml\Mixin\SubAggregateTrait;
use ryunosuke\dbml\Mixin\SubSelectTrait;
use ryunosuke\dbml\Mixin\YieldTrait;
use ryunosuke\dbml\Query\Expression\Alias;
use ryunosuke\dbml\Query\Expression\Expression;
use ryunosuke\dbml\Query\Expression\Operator;
use ryunosuke\dbml\Query\Expression\TableDescriptor;
use ryunosuke\dbml\Query\Parser;
use ryunosuke\dbml\Query\Queryable;
use ryunosuke\dbml\Query\QueryBuilder;
use ryunosuke\dbml\Query\Statement;
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
 * ただし mysqli を用いる場合は PDO::MYSQL_ATTR_FOUND_ROWS = true と同等の結果になるように実装されている。
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
 * @method bool                   getInsertSet()
 * @method $this                  setInsertSet($bool)
 * @method bool                   getUpdateEmpty()
 * @method $this                  setUpdateEmpty($bool)
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
 * @method bool                   getFilterNullAtNotNullColumn()
 * @method $this                  setFilterNullAtNotNullColumn($bool) {
 *     not null なカラムの null をフィルタするか指定する
 *
 *     この設定を true にすると INSERT/UPDATE 時に「not null なのに null が来たカラム」が自動で伏せられるようになる。
 *     呼び出し側の都合などで null を予約的に扱い、キーとして存在してしまうことはよくある。
 *     どうせエラーになるので、結局呼び出し直前に if 分岐で unset したりするのでいっそのこと自動で伏せてしまったほうが便利なことは多い。
 *
 *     @param bool $bool not null なカラムの null をフィルタするなら true
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
 * @method bool                   getConvertBoolToInt()
 * @method $this                  setConvertBoolToInt($bool) {
 *     数値系カラムに真偽値が来たときの挙動を指定する
 *
 *     この設定を true にすると、数値系カラムに真偽値が来た場合に自動で int に変換されるようになる。
 *
 *     @param bool $bool 真偽値を int に変換するなら true
 * }
 * @method bool                   getConvertNumericToDatetime()
 * @method $this                  setConvertNumericToDatetime($bool) {
 *     日時系カラムに int/float が来たときの挙動を指定する
 *
 *     この設定を true にすると、日時系カラムに int/float が来た場合にタイムスタンプとみなすようになる。
 *
 *     @param bool $bool int/float をタイムスタンプとみなすなら true
 * }
 * @method bool                   getTruncateString()
 * @method $this                  setTruncateString($bool) {
 *     文字列系カラムに length を超える文字列が来たときの挙動を指定する
 *
 *     この設定を true にすると、文字列系カラムに length を超える文字列が来た場合に切り落とされるようになる。
 *
 *     @param bool $bool length で切り落とすなら true
 * }
 * @method callable               getYamlParser()
 * @method $this                  setYamlParser($callable)
 * @method array                  getAutoCastType()
 * @nethod self                   setAutoCastType($array) 実際に定義している
 * @method string                 getInjectCallStack()
 * @method $this                  setInjectCallStack(string|array|callable $string)
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
 * @method bool                   getPropagateLockMode() {{@link QueryBuilder::getPropagateLockMode()} 参照@inheritdoc QueryBuilder::getPropagateLockMode()}
 * @method $this                  setPropagateLockMode($bool) {{@link QueryBuilder::setPropagateLockMode()} 参照@inheritdoc QueryBuilder::setPropagateLockMode()}
 * @method bool                   getInjectChildColumn() {{@link QueryBuilder::getInjectChildColumn()} 参照@inheritdoc QueryBuilder::getInjectChildColumn()}
 * @method $this                  setInjectChildColumn($bool) {{@link QueryBuilder::setInjectChildColumn()} 参照@inheritdoc QueryBuilder::setInjectChildColumn()}
 */
// @formatter:on
class Database
{
    use OptionTrait;

    use FetchOrThrowTrait;
    use SelectOrThrowTrait;
    use SelectInShareTrait;
    use SelectForUpdateTrait;
    use SelectForAffectTrait;
    use EntityInShareTrait;
    use EntityOrThrowTrait;
    use EntityForUpdateTrait;
    use EntityForAffectTrait;
    use YieldTrait;
    use ExportTrait;
    use SelectAggregateTrait;

    use AggregateTrait;
    use SubSelectTrait;
    use SubAggregateTrait;

    use AffectIgnoreTrait;
    use AffectConditionallyTrait;
    use AffectOrThrowTrait;
    use PrepareTrait;

    protected function getDatabase() { return $this; }

    protected function getConditionPosition() { return 1; }

    /// 内部的に自動付加されるカラム名
    public const AUTO_KEY         = '__dbml_auto_column';
    public const AUTO_PRIMARY_KEY = self::AUTO_KEY . '_key';
    public const AUTO_PARENT_KEY  = self::AUTO_KEY . '_pk';
    public const AUTO_CHILD_KEY   = self::AUTO_KEY . '_ck';
    public const AUTO_DEPEND_KEY  = self::AUTO_KEY . '_depend';

    /// perform メソッド
    public const METHOD_ARRAY = 'array';
    public const METHOD_ASSOC = 'assoc';
    public const METHOD_LISTS = 'lists';
    public const METHOD_PAIRS = 'pairs';
    public const METHOD_TUPLE = 'tuple';
    public const METHOD_VALUE = 'value';

    /// perform メソッド配列
    public const METHODS = [
        self::METHOD_ARRAY => ['keyable' => false, 'entity' => true],
        self::METHOD_ASSOC => ['keyable' => true, 'entity' => true],
        self::METHOD_LISTS => ['keyable' => false, 'entity' => false],
        self::METHOD_PAIRS => ['keyable' => true, 'entity' => false],
        self::METHOD_TUPLE => ['keyable' => null, 'entity' => true],
        self::METHOD_VALUE => ['keyable' => null, 'entity' => false],
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

    /** @var \ArrayObject */
    private $vtables;

    /** @var \ArrayObject 「未初期化なら生成して返す」系のメソッドのキャッシュ */
    private $cache;

    /** @var int */
    private $affectedRows;

    /** @var int[] dryrun 中の挿入 ID. dryrun でしか使わないので（値が戻って欲しいので ArrayObject にはしていない） */
    private $lastInsertIds = [];

    public static function getDefaultOptions()
    {
        $default_options = [
            // キャッシュオブジェクト
            'cacheProvider'             => null,
            // 初期化後の SQL コマンド（mysql@PDO でいう MYSQL_ATTR_INIT_COMMAND）
            'initCommand'               => null,
            // スキーマを必要としたときのコールバック
            'onRequireSchema'           => function (Database $db) { },
            // テーブルを必要としたときのコールバック（スキーマアクセス時に一度だけ呼ばれる）
            'onIntrospectTable'         => function (Table $table) { },
            // テーブル名 => Entity クラス名のコンバータ
            'tableMapper'               => function ($table) { return pascal_case($table); },
            // 拡張 INSERT SET 構文を使うか否か（mysql 以外は無視される）
            'insertSet'                 => false,
            // UPDATE で空データの時に意味のない更新をするか？（false だと構文エラーになる）
            'updateEmpty'               => true,
            // insert 時などにテーブルに存在しないカラムを自動でフィルタするか否か
            'filterNoExistsColumn'      => true,
            // insert 時などに not null な列に null が来た場合に自動でフィルタするか否か
            'filterNullAtNotNullColumn' => true,
            // insert 時などに NULLABLE NUMERIC カラムは 空文字を null として扱うか否か
            'convertEmptyToNull'        => true,
            // insert 時などに数値系カラムは真偽値を int として扱うか否か
            'convertBoolToInt'          => true,
            // insert 時などに日時カラムは int/float をタイムスタンプとして扱うか否か
            'convertNumericToDatetime'  => false, // for compatible
            // insert 時などに文字列カラムは length で切るか否か
            'truncateString'            => false,
            // 埋め込み条件の yaml パーサ
            'yamlParser'                => function ($yaml) { return \ryunosuke\dbml\paml_import($yaml)[0]; },
            // DB型で自動キャストする型設定。select,affect 要素を持つ（多少無駄になるがサンプルも兼ねて冗長に記述してある）
            'autoCastType'              => [
                // 正式な与え方。select は取得（SELECT）時、affect は設定（INSERT/UPDATE）時を表す
                // 個人的には DATETIME で設定したい。出すときは DateTime で返ってくれると便利だけど、入れるときは文字列で入れたい
                'hoge'                  => [
                    'select' => true,
                    'affect' => false,
                ],
                // 短縮記法。select/affect が両方 true ならこのように true だけでも良い
                'fuga'                  => true,
                // 共に false。実質的に与えていないのと同じで単に明示するだけ
                Types::DATE_MUTABLE     => [
                    'select' => false,
                    'affect' => false,
                ],
                // 共に false の短縮記法。やはり単に明示するだけ
                Types::DATETIME_MUTABLE => false,
                // このようにクロージャを与えると Type::convertTo(PHP|Database)Value の代わりのこれらが呼ばれるようになる
                // なお、クロージャの $this は「その Type」でバインドされる
                'piyo'                  => [
                    'select' => function ($value, AbstractPlatform $platform) { return Type::getType('string')->convertToPHPValue($value, $platform); },
                    'affect' => function ($value, AbstractPlatform $platform) { return Type::getType('string')->convertToDatabaseValue($value, $platform); },
                ],
                // このように Type を渡すと（一度だけ） addType されると同時に select:convertToPHPValue, affect:convertToDatabaseValue が自動で設定される
                'type'                  => new \Doctrine\DBAL\Types\DateTimeType(),
            ],
            // 同名カラムがあった時どう振る舞うか[null, 'noallow', 'strict', 'loose']
            'checkSameColumn'           => null,
            // SQL 実行時にコールスタックを埋め込むか(パスを渡す。!プレフィックスで否定、 null で無効化)
            'injectCallStack'           => null,
            // 更新クエリを実行せずクエリ文字列を返すようにするか
            'dryrun'                    => false,
            // 更新クエリを実行せずプリペアされたステートメントを返すようにするか
            'preparing'                 => false,
            // 参照系クエリをマスターで実行するか(「スレーブに書き込みたい」はまずあり得ないが「マスターから読み込みたい」はままある)
            'masterMode'                => false,
            // anywhere のデフォルトオプション
            'anywhereOption'            => [
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
            // CompatiblePlatform のクラス名 or インスタンス
            'compatiblePlatform'        => CompatiblePlatform::class,
            // exportXXX 呼び出し時にどのクラスを使用するか
            'exportClass'               => [
                'array' => ArrayGenerator::class,
                'csv'   => CsvGenerator::class,
                'json'  => JsonGenerator::class,
            ],
            // ロギングオブジェクト（LoggerInterface）
            'logger'                    => null,
        ];

        // 他クラスのオプションをマージ
        $default_options += TableGateway::getDefaultOptions();
        $default_options += QueryBuilder::getDefaultOptions();
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
        // 代替クラスのロード（本来こんなところに書くべきではないが他に良い場所もないのでほぼ必ず通るここに書く）
        class_aliases([
            \Doctrine\DBAL\Driver\SQLite3\Result::class => \ryunosuke\dbml\Driver\SQLite3\Result::class,
            \Doctrine\DBAL\Driver\Mysqli\Result::class  => \ryunosuke\dbml\Driver\Mysqli\Result::class,
            \Doctrine\DBAL\Driver\PgSQL\Result::class   => \ryunosuke\dbml\Driver\PgSQL\Result::class,
        ]);

        $configure = function (Configuration $configuration) use ($options) {
            $middlewares = $configuration->getMiddlewares();
            if (array_find($middlewares, fn($middleware) => $middleware instanceof LoggingMiddleware) === false) {
                $middlewares[] = new LoggingMiddleware(new LoggerChain());
                $configuration->setMiddlewares($middlewares);
            }
            // for compatible
            if (strlen($options['initCommand'] ?? '')) {
                $middlewares[] = new class($options['initCommand']) implements \Doctrine\DBAL\Driver\Middleware {
                    private $initCommand;

                    public function __construct($initCommand)
                    {
                        $this->initCommand = $initCommand;
                    }

                    public function wrap(Driver $driver): Driver
                    {
                        return new class ($driver, $this->initCommand) extends AbstractDriverMiddleware {
                            private $initCommand;

                            public function __construct(Driver $wrappedDriver, $initCommand)
                            {
                                parent::__construct($wrappedDriver);
                                $this->initCommand = $initCommand;
                            }

                            public function connect(array $params): \Doctrine\DBAL\Driver\Connection
                            {
                                $connection = parent::connect($params);
                                $connection->exec($this->initCommand);
                                return $connection;
                            }
                        };
                    }
                };
            }
            $configuration->setMiddlewares($middlewares);
            return $configuration;
        };

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
                $connections = array_fill(0, 2, DriverManager::getConnection($dbconfig, $configure(new Configuration())));
            }
            else {
                $connections = [
                    DriverManager::getConnection($master, $configure(new Configuration())),
                    DriverManager::getConnection($slave, $configure(new Configuration())),
                ];
            }
        }

        if (count(array_unique(array_map(function ($connection) { return get_class($connection->getDriver()); }, $connections))) !== 1) {
            throw new \DomainException('master and slave connection are must be same platform.');
        }
        $this->connections = array_combine(['master', 'slave'], $connections);
        $this->txConnection = $this->getMasterConnection();
        $this->vtables = new \ArrayObject();
        $this->cache = new \ArrayObject();

        if (!isset($options['cacheProvider'])) {
            $source = array_pickup($this->txConnection->getParams(), ['url', 'driver', 'host', 'port', 'user', 'password', 'dbname']);
            $options['cacheProvider'] = cacheobject(sys_get_temp_dir() . '/dbml-' . sha1(serialize($source)));
        }

        $this->setDefault($options);

        $this->setLogger($this->getUnsafeOption('logger'));
        $this->setAutoCastType($this->getUnsafeOption('autoCastType')); // デフォルト兼サンプルの正規化の必要があるので無駄に呼んでおく
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
            $classname = $this->getGatewayClass($name);
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

        // Gateway 取得
        if ($gateway = $this->$name) {
            if ($arguments) {
                if (filter_var($arguments[0], \FILTER_VALIDATE_INT) !== false) {
                    return $gateway->pk($arguments[0]);
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
        unset($properties["\0$classname\0connections"]);  // 旨味が少ない（有益な情報があまりない）
        unset($properties["\0$classname\0txConnection"]); // connections と同じ
        unset($properties["\0$classname\0cache"]);        // 不要（個別に見れば良い）
        return $properties;
    }

    private function _tableMap()
    {
        $maps = cache_fetch($this->getUnsafeOption('cacheProvider'), 'Database-tableMap', function () {
            $maps = [
                'entityClass'  => [],
                'gatewayClass' => [],
                'EtoT'         => [],
                'TtoE'         => [],
            ];
            $defaultEntity = namespace_split(Entity::class)[0];
            $defaultGateway = namespace_split(TableGateway::class)[0];
            $tableMapper = $this->getUnsafeOption('tableMapper');
            $tables = $this->getSchema()->getTableNames();
            usort($tables, fn($a, $b) => $a <=> $b);
            foreach ($tables as $tablename) {
                $map = $tableMapper($tablename);
                if ($map === null) {
                    continue;
                }
                if (is_string($map)) {
                    $map = [$map => []];
                }

                foreach ($map as $entityname => $class) {
                    // テーブル名とエンティティ名が一致してはならない
                    if (isset($maps['EtoT'][$tablename])) {
                        throw new \DomainException("'$tablename' is already defined.");
                    }
                    // 同じエンティティ名があってはならない
                    if (isset($maps['EtoT'][$entityname])) {
                        throw new \DomainException("'$entityname' is already defined.");
                    }

                    $class += [
                        'entityClass'  => "$defaultEntity\\$entityname",
                        'gatewayClass' => "$defaultGateway\\$entityname",
                    ];
                    $class = array_map(function ($v) { return ltrim($v, '\\'); }, $class);

                    $maps['entityClass'][$entityname] = class_exists($class['entityClass']) ? $class['entityClass'] : null;
                    $maps['gatewayClass'][$tablename] = class_exists($class['gatewayClass']) ? $class['gatewayClass'] : null;
                    $maps['gatewayClass'][$entityname] = class_exists($class['gatewayClass']) ? $class['gatewayClass'] : null;
                    $maps['EtoT'][$entityname] = $tablename;
                    $maps['TtoE'][$tablename][] = $entityname;
                }
            }
            return $maps;
        });

        return $maps;
    }

    private function _doFetch($sql, iterable $params, $method)
    {
        $converter = $this->_getConverter($sql);
        $revert = $this->_toTablePrefix($sql);
        try {
            $stmt = $this->_sqlToStmt($sql, $params, $this->getSlaveConnection());
            return $this->perform($stmt->fetchAllAssociative(), $method, $converter);
        }
        finally {
            $revert();
        }
    }

    private function _sqlToStmt($sql, iterable $params, Connection $connection)
    {
        if ($sql instanceof Statement) {
            $stmt = $sql->executeSelect($params, $connection);
        }
        elseif ($sql instanceof QueryBuilder) {
            $stmt = $sql->getPreparedStatement();
            if ($stmt) {
                $stmt = $stmt->executeSelect($params, $connection);
            }
            else {
                $stmt = $this->executeSelect($this->_builderToSql($sql, $params), $params);
            }
        }
        else {
            $stmt = $this->executeSelect($sql, $params);
        }

        $cconnection = new CompatibleConnection($this->getSlaveConnection());
        $stmt = $cconnection->customResult($stmt, $this->getUnsafeOption('checkSameColumn'));

        return $stmt;
    }

    private function _builderToSql($builder, iterable &$fetch_params)
    {
        $fetch_params = $fetch_params instanceof \Traversable ? iterator_to_array($fetch_params) : $fetch_params;

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
        }, []) : [];

        /** @var QueryBuilder $data_source */
        $data_source = optional($data_source, QueryBuilder::class);
        $rconverter = $data_source->getRowConverter();
        $alias_table = array_lookup($data_source->getFromPart() ?: [], 'table');

        return function ($row) use ($platform, $cast_type, $table_columns, $alias_table, $rconverter, $samecheck_method, $samecheck) {
            $newrow = [];
            foreach ($row as $c => $v) {
                if ($samecheck_method && is_array($v)) {
                    $v = $samecheck($c, $v);
                }

                if ($cast_type) {
                    $parts = explode('.', $c, 3);
                    $pcount = count($parts);
                    if ($pcount >= 2) {
                        // mysql の場合は3個来ることがある（暗黙＋明示＋列名）。その場合は最初を捨てて明示を優先する
                        if ($pcount === 3) {
                            [, $table, $c] = $parts;
                        }
                        else {
                            [$table, $c] = $parts;
                        }

                        // クエリビルダ経由でエイリアスマップが得られるなら変換ができる
                        if (isset($alias_table[$table])) {
                            $table = $alias_table[$table];
                        }

                        // カラムが存在するなら convert
                        if (is_string($table) && isset($table_columns[$table][$c])) {
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
                [$c, $typename] = explode('|', $c, 2) + [1 => ''];
                if ($typename) {
                    if (isset($cast_type[$typename]['select'])) {
                        $converter = $cast_type[$typename]['select'];
                        if ($converter instanceof \Closure) {
                            $v = $converter($v, $platform);
                        }
                        else {
                            $v = Type::getType($typename)->convertToPHPValue($v, $platform);
                        }
                    }
                }

                $newrow[$c] = $v;
            }
            if ($rconverter) {
                $newrow = $rconverter($newrow);
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

        $cconnection = new CompatibleConnection($this->getSlaveConnection());
        $revert = $cconnection->setTablePrefix();

        // QueryBuilder 経由ならそれっぽいことはできる
        if ($revert === null) {
            if ($sql instanceof QueryBuilder) {
                $sql->setAutoTablePrefix(true);
                $revert = function () use ($sql) {
                    $sql->setAutoTablePrefix(false);
                };
            }
        }
        // どうしようもない
        if ($revert === null) {
            return function () { };
        }
        return $revert;
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

        foreach ($columns as $cname => $column) {
            if (array_key_exists($cname, $row) && ($vaffect = $this->getSchema()->getTableColumnExpression($table, $cname, 'affect'))) {
                $row = $vaffect($row[$cname], $row) + $row;
            }
            if ($column->getPlatformOptions()['virtual'] ?? null) {
                unset($columns[$cname]);
            }
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

        $filterNullAtNotNullColumn = $this->getUnsafeOption('filterNullAtNotNullColumn');
        $convertEmptyToNull = $this->getUnsafeOption('convertEmptyToNull');
        $convertBoolToInt = $this->getUnsafeOption('convertBoolToInt');
        $convertNumericToDatetime = $this->getUnsafeOption('convertNumericToDatetime');
        $truncateString = $this->getUnsafeOption('truncateString');
        $autoCastType = $this->getUnsafeOption('autoCastType');
        $compatibleCharAndBinary = $this->getCompatiblePlatform()->supportsCompatibleCharAndBinary();

        $integerTypes = [Types::BOOLEAN => true, Types::INTEGER => true, Types::SMALLINT => true, Types::BIGINT => true];
        $decimalTypes = [Types::DECIMAL => true, Types::FLOAT => true];
        $numericTypes = $integerTypes + $decimalTypes;
        $dateTypes = [Types::DATE_MUTABLE => true, Types::DATE_IMMUTABLE => true];
        $datetimeTypes = [Types::DATETIME_MUTABLE => true, Types::DATETIME_IMMUTABLE => true];
        $datetimeTZTypes = [Types::DATETIMETZ_MUTABLE => true, Types::DATETIMETZ_IMMUTABLE => true];
        $datetimableTypes = $dateTypes + $datetimeTypes + $datetimeTZTypes;
        $clobTypes = [Types::STRING => true, Types::TEXT => true];
        $blobTypes = [Types::BINARY => true, Types::BLOB => true];
        $stringTypes = $clobTypes + $blobTypes;

        foreach ($columns as $cname => $column) {
            if (array_key_exists($cname, $row)) {
                $type = $column->getType();
                $typename = $type->getName();
                $nullable = !$column->getNotnull();

                if ($filterNullAtNotNullColumn && $row[$cname] === null && !$nullable) {
                    unset($row[$cname]);
                    continue;
                }

                if ($convertEmptyToNull && $row[$cname] === '' && ($cname === $autocolumn || (!isset($stringTypes[$typename]) && $nullable))) {
                    $row[$cname] = null;
                }

                if ($convertBoolToInt && is_bool($row[$cname]) && isset($numericTypes[$typename])) {
                    $row[$cname] = (int) $row[$cname];
                }

                if ($convertNumericToDatetime && (is_int($row[$cname]) || is_float($row[$cname])) && isset($datetimableTypes[$typename])) {
                    $dt = new \DateTime();
                    $dt->setTimestamp($row[$cname]);
                    $dt->modify(((int) (($row[$cname] - (int) $row[$cname]) * 1000 * 1000)) . " microsecond");
                    $format = null;
                    $format ??= isset($dateTypes[$typename]) ? $this->getPlatform()->getDateFormatString() : null;
                    $format ??= isset($datetimeTypes[$typename]) ? $this->getPlatform()->getDateTimeFormatString() : null;
                    $format ??= isset($datetimeTZTypes[$typename]) ? $this->getPlatform()->getDateTimeTzFormatString() : null;
                    $row[$cname] = $dt->format($format);
                }

                if ($truncateString && is_string($row[$cname]) && isset($stringTypes[$typename])) {
                    $row[$cname] = $this->getCompatiblePlatform()->truncateString($row[$cname], $column);
                }

                if (($converter = $autoCastType[$typename]['affect'] ?? null) && !$row[$cname] instanceof Queryable) {
                    if ($converter instanceof \Closure) {
                        $row[$cname] = $converter($row[$cname], $this->getPlatform());
                    }
                    else {
                        $row[$cname] = $type->convertToDatabaseValue($row[$cname], $this->getPlatform());
                    }
                }

                if (!$compatibleCharAndBinary && is_string($row[$cname]) && isset($blobTypes[$typename])) {
                    $row[$cname] = $this->getCompatiblePlatform()->getBinaryExpression($row[$cname]);
                }
            }
        }

        // mysql は null を指定すれば自動採番されるが、他の RDBMS では伏せないと採番されないようだ
        if ($autocolumn && !isset($row[$autocolumn]) && !$this->getCompatiblePlatform()->supportsIdentityNullable()) {
            unset($row[$autocolumn]);
        }

        $row = $this->$table->normalize($row);

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
        foreach ($rows as $row) {
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

        if (is_string($tableName) && str_exists($tableName, array_merge(Database::JOIN_MAPPER, [',', '.']))) {
            $tableName = array_each(TableDescriptor::forge($this, $tableName, []), function (&$carry, TableDescriptor $td) {
                $carry[$td->descriptor] = $td->column;
            }, []);
        }
        if (is_string($tableName) && str_exists($tableName, TableDescriptor::META_CHARACTORS)) {
            $tableName = TableDescriptor::forge($this, $tableName, []);
        }
        if (is_array($tableName)) {
            $data = arrayize($data);
            if ($data && !is_hasharray($data)) {
                if (count($tableName) !== 1) {
                    throw new \InvalidArgumentException('don\'t specify multiple table.');
                }
                [$tableName, $columns] = first_keyvalue($tableName);
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
        foreach ($primary as $val) {
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
            [$tableAlias, $tableName] = first_keyvalue($tableName);
        }

        $wheres = [];
        foreach (arrayize($identifier) as $key => $cond) {
            if ($key === '') {
                $pcols = $this->getSchema()->getTablePrimaryKey($tableName)->getColumns();
                $params = (array) $cond;
                if (count($pcols) !== 1 && count($params) !== 0 && array_depth($params) === 1) {
                    $params = [$params];
                }
                $pvals = array_each($params, function (&$carry, $pval) use ($pcols) {
                    $pvals = (array) $pval;
                    if (count($pcols) !== count($pvals)) {
                        throw new \InvalidArgumentException('argument\'s length is not match primary columns.');
                    }
                    $carry[] = array_combine($pcols, $pvals);
                });
                $wheres[] = $this->getCompatiblePlatform()->getPrimaryCondition($pvals, $tableAlias ?? $tableName);
                continue;
            }

            if ($cond instanceof QueryBuilder && $cond->getSubmethod() !== null) {
                $cond->setSubwhere($tableName, $tableAlias, null);
            }

            $wheres[$key] = $cond;
        }
        return $wheres;
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
     * @param ?string $objectname オブジェクト名
     * @return AbstractAsset スキーマオブジェクト
     */
    public function describe($objectname = null)
    {
        $schema = $this->getSchema();

        if ("$objectname" === "") {
            return $this->getSlaveConnection()->createSchemaManager()->introspectSchema();
        }

        [$parentname, $childname] = explode('.', $objectname) + [1 => null];
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
     * 存在するテーブル名や tableMapper を利用してアノテーションコメントを作成する。
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
     * 存在するテーブル名や tableMapper などを利用して mixin 用のトレイトを作成する。
     * このメソッドが吐き出したトレイトを `@ mixin Hogera` などとすると補完が効くようになる。
     *
     * @param ?string $namespace トレイト群の名前空間。未指定だとグローバル
     * @param ?string $filename ファイルとして吐き出す先
     * @return string アノテーションコメント
     */
    public function echoAnnotation($namespace = null, $filename = null)
    {
        $special_types = [
            Types::SIMPLE_ARRAY => 'array|string',
            Types::JSON         => 'array|string',
            Types::BOOLEAN      => 'bool',
            Types::INTEGER      => 'int',
            Types::SMALLINT     => 'int',
            Types::BIGINT       => 'int|string',
            Types::DECIMAL      => 'float|string',
            Types::FLOAT        => 'float',
        ];
        $args1 = '$tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []';
        $args2 = '$variadic_primary, $tableDescriptor = []';
        $args3 = '$predicates = [], $limit = 1';

        $tablemap = $this->_tableMap();

        $gateway_provider = [
            'prop' => [],
            'func' => [],
        ];
        $unique_names = [];
        foreach ($tablemap['gatewayClass'] as $tname => $gname) {
            $ename = $this->convertEntityName($tname);
            $tname_key = strtolower($tname);
            if (!isset($unique_names[$tname_key])) {
                $unique_names[$tname_key] = true;
                $gateway_provider['prop'][] = "/** @var {$ename}TableGateway */\npublic \$$tname;";
                $gateway_provider['func'][] = "/** @return {$ename}TableGateway */\npublic function $tname($args1) { }";
            }
        }

        $gateways = [];
        $entities = [];
        foreach ($tablemap['TtoE'] ?? [] as $tname => $entity_names) {
            $column_types = array_each($this->getSchema()->getTableColumns($tname), function (&$carry, Column $column) use ($special_types) {
                $type = $column->getType();
                $typename = $type->getName();
                $reftype = (new \ReflectionMethod($type, 'convertToPHPValue'))->getReturnType();
                $carry[$column->getName()] = @strval($reftype ?? $special_types[$typename] ?? 'string');
            }, []);
            $shape = array_sprintf($column_types, '%2$s: %1$s', ', ');
            $props = array_sprintf($column_types, "/** @var %1\$s */\npublic \$%2\$s;");

            foreach ($entity_names as $entity_name) {
                $gclass = $tablemap['gatewayClass'][$entity_name] ?? TableGateway::class;
                $eclass = $tablemap['entityClass'][$entity_name] ?? Entity::class;
                $entity = "{$entity_name}Entity";

                $suffixes = ['', 'InShare', 'ForUpdate', 'OrThrow', 'ForAffect'];
                $rules = array_maps(static::METHODS, fn($rule) => $rule + ['arg' => $args1, 'suffix' => $suffixes]);
                $rules['find'] = static::METHODS[self::METHOD_TUPLE] + ['arg' => $args2, 'suffix' => $suffixes];
                $rules['neighbor'] = static::METHODS[self::METHOD_ASSOC] + ['arg' => $args3, 'suffix' => ['']];
                $types = [
                    "0-1" => "{$entity}[]|array<array{{$shape}}>", // array, assoc, neighbor
                    "1-1" => "{$entity}|array{{$shape}}",          // tuple, find
                ];
                $methods = array_fill_keys(array_keys($rules), []);
                foreach ($rules as $mname => $rule) {
                    if ($type = ($types[sprintf("%d-%d", $rule['keyable'] === null, $rule['entity'])] ?? null)) {
                        foreach ($rule['suffix'] as $suffix) {
                            $methods[$mname][] = "/** @return $type */\npublic function $mname$suffix({$rule['arg']}) { }";
                        }
                    }
                }

                $gateways["{$entity_name}TableGateway extends \\$gclass"] = ["use TableGatewayProvider;"] + $methods;
                $entities["{$entity_name}Entity extends \\$eclass"] = $props;
            }
        }

        $gen = function ($type, $name, $body) {
            $body = indent_php("\n" . implode("\n\n", array_flatten((array) $body)), ['baseline' => 0, 'indent' => 4]);
            return "$type $name\n{{$body}\n}\n";
        };
        $namespace = $namespace ? "\nnamespace $namespace;\n" : '';
        $result = "<?php\n$namespace\n// this code auto generated.\n\n// @formatter\x3Aoff\n\n"; // 文字列内だけど phpstorm が反応する
        $result .= implode("\n", [
            $gen('trait', "TableGatewayProvider", $gateway_provider),
            $gen('class', "Database extends \\" . get_class($this), "use TableGatewayProvider;"),
            array_sprintf($gateways, fn($body, $name) => $gen('class', $name, $body), "\n"),
            array_sprintf($entities, fn($body, $name) => $gen('class', $name, $body), "\n"),
        ]);

        if ($filename) {
            file_set_contents($filename, $result);
        }
        return $result;
    }

    /**
     * コード補完用の phpstorm.meta を取得する
     *
     * 存在するテーブル名や tableMapper などを利用して phpstorm.meta を作成する。
     *
     * @param bool $innerOnly namespace PHPSTORM_META のような外側を含めるか（非推奨）
     * @param ?string $filename ファイルとして吐き出す先
     * @param ?string $namespace エンティティの名前空間。未指定だと Entityable に集約される
     * @return string phpstorm.meta の内容
     */
    public function echoPhpStormMeta($innerOnly = false, $filename = null, $namespace = null)
    {
        // for compatible
        if (!is_bool($innerOnly)) {
            $namespace = $filename;
            $filename = $innerOnly;
            $innerOnly = false;
        }

        $special_types = [
            Types::SIMPLE_ARRAY => 'array|string',
            Types::JSON         => 'array|string',
            Types::BOOLEAN      => 'bool',
            Types::INTEGER      => 'int',
            Types::SMALLINT     => 'int',
            Types::BIGINT       => 'int|string',
            Types::DECIMAL      => 'float|string',
            Types::FLOAT        => 'float',
        ];

        $export = function ($value, $nest = 0, $parents = []) use (&$export) {
            if (!is_array($value) || !$value) {
                return var_export($value, true);
            }

            $keys = array_map($export, array_combine($keys = array_keys($value), $keys));
            $maxlen = max(array_map('strlen', $keys));

            $lines = [];
            foreach ($value as $k => $v) {
                $ks = $keys[$k];
                $ls = str_repeat(' ', ($nest + 1) * 4);
                $ms = str_repeat(' ', $maxlen - strlen($keys[$k]));
                $vs = class_exists($v) ? "\\$v::class" : var_export($v, true);
                $lines[] = "{$ls}{$ks}{$ms} => {$vs}";
            }
            $lines[] = "";
            return "[\n" . implode(",\n", $lines) . str_repeat(' ', $nest * 4) . "]";
        };

        $entities = [];
        foreach ($this->getSchema()->getTableNames() as $tname) {
            $entityname = $this->getEntityClass($tname);
            if (trim($entityname, '\\') === Entity::class) {
                if ($namespace === null) {
                    $entityname = Entityable::class;
                }
                else {
                    $entityname = trim($namespace, '\\') . '\\' . $this->convertEntityName($tname) . 'Entity';
                }
            }

            $columns = array_map(function (Column $column) use ($special_types) {
                $type = $column->getType();
                $typename = $type->getName();
                $autocast = $this->getUnsafeOption('autoCastType');
                $converter = $autocast[$typename]['select'] ?? null;
                if ($converter) {
                    if (is_callable($converter)) {
                        $reftype = reflect_callable($converter)->getReturnType();
                    }
                    else {
                        $reftype = (new \ReflectionMethod($type, 'convertToPHPValue'))->getReturnType();
                    }
                }
                return @strval($reftype ?? $special_types[$typename] ?? 'string');
            }, $this->getSchema()->getTableColumns($tname));
            $entities[$entityname] = ($entities[$entityname] ?? []) + $columns;
        }

        $result = '';
        foreach ($entities as $entityname => $entity) {
            $result .= <<<META
            
                override(new \\$entityname,
                    map({$export($entity, 2)})
                );
            META;
        }
        if (!$innerOnly) {
            $result = "<?php\nnamespace PHPSTORM_META {\n$result\n}";
        }

        if ($filename) {
            file_set_contents($filename, $result);
        }
        return $result;
    }

    /**
     * ロガーを設定する
     *
     * 配列で 0, master を指定するとマスター接続に設定される。
     * 同様に 1, slave を指定するとスレーブ接続に設定される。
     *
     * 単一のインスタンスを渡した場合は両方に設定される。
     *
     * @param LoggerInterface|LoggerInterface[]|null $logger ロガー
     * @return $this 自分自身
     */
    public function setLogger($logger)
    {
        if (is_array($logger)) {
            $loggers = [
                array_get($logger, 0) ?? array_get($logger, 'master') ?? null,
                array_get($logger, 1) ?? array_get($logger, 'slave') ?? null,
            ];
        }
        else {
            $loggers = [
                $logger,
                $logger,
            ];
        }

        foreach ($this->getConnections() as $n => $con) {
            foreach ($con->getConfiguration()->getMiddlewares() as $middleware) {
                if ($middleware instanceof LoggingMiddleware) {
                    $logger = $middleware->getLogger();
                    if ($logger instanceof LoggerChain) {
                        $logger->resetLoggers(array_filter([$loggers[$n]], fn($v) => $v !== null));
                    }
                }
            }
        }
        return $this;
    }

    /**
     * カラムの型に応じた自動変換処理を登録する
     *
     * 自動変換がなにかは {@link \ryunosuke\dbml\ dbml} を参照。
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
            if ($opt instanceof Type) {
                if (!Type::getTypeRegistry()->has($type)) {
                    Type::getTypeRegistry()->register($type, $opt);
                }
                $types[$type] = [
                    'select' => true,
                    'affect' => true,
                ];
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
            // type があるクロージャは $this をバインド（Type は static や引数ベースであり、状態を持たないのでこの段階でバインドできる）
            if (Type::hasType($type)) {
                $typeclass = Type::getType($type);
                if ($opt['select'] instanceof \Closure) {
                    $opt['select'] = $opt['select']->bindTo($typeclass, $typeclass);
                }
                if ($opt['affect'] instanceof \Closure) {
                    $opt['affect'] = $opt['affect']->bindTo($typeclass, $typeclass);
                }
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
     * コネクション配列を返す
     *
     * 単一だろうと Master/Slave 構成だろうとインスタンスとしての配列を返す。
     * 例えばマスタースレーブが同じインスタンスの場合は1つしか返さない。
     *
     * @return Connection[] コネクション配列
     */
    public function getConnections()
    {
        $cons = [];
        foreach ($this->connections as $con) {
            $cons[spl_object_hash($con)] = $con;
        }
        return array_values($cons);
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
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getConnection()->getNativeConnection();
    }

    /**
     * マスター接続の PDO を返す
     *
     * @return \PDO PDO オブジェクト
     */
    public function getMasterPdo()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getMasterConnection()->getNativeConnection();
    }

    /**
     * スレーブ接続の PDO を返す
     *
     * @return \PDO PDO オブジェクト
     */
    public function getSlavePdo()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getSlaveConnection()->getNativeConnection();
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
     * @param array|string|null $target "master" か "slave" でそちら側のみ変更する。未指定/null で両方変更する
     * @return \Closure 元に戻すためのクロージャ
     */
    public function setPdoAttribute($attributes, $target = null)
    {
        if ($target === null) {
            $target = ['master', 'slave'];
        }
        $target = (array) $target;

        $masterPdo = $this->getMasterPdo();
        $slavePdo = $this->getSlavePdo();

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
     * PDO の prepare がエミュレーションモードかを返す
     *
     * @return bool エミュレーションモードなら true
     */
    public function isEmulationMode()
    {
        if (!$this->getPdo() instanceof \PDO) {
            return false;
        }

        // driver ごとにエミュレーションサポートが異なる上、全ては調べてられないので実際に取得してダメだったら true とする
        try {
            return !!$this->getPdo()->getAttribute(\PDO::ATTR_EMULATE_PREPARES);
        }
        catch (\PDOException $e) {
            return true;
        }
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
        if (!isset($this->cache['compatiblePlatform'])) {
            $classname = $this->getUnsafeOption('compatiblePlatform');
            assert(is_a($classname, CompatiblePlatform::class, true));
            $this->cache['compatiblePlatform'] = is_object($classname) ? $classname : new $classname($this->getPlatform());
        }
        return $this->cache['compatiblePlatform'];
    }

    /**
     * {@link Schema スキーマオブジェクト}を取得する
     *
     * @return Schema スキーマオブジェクト
     */
    public function getSchema()
    {
        if (!isset($this->cache['schema'])) {
            $listeners = [
                'onIntrospectTable' => $this->getUnsafeOption('onIntrospectTable'),
            ];
            $cacher = $this->getUnsafeOption('cacheProvider');
            $callback = $this->getUnsafeOption('onRequireSchema');
            $this->cache['schema'] = new Schema($this->connections['slave']->createSchemaManager(), $listeners, $cacher);
            $callback($this);
        }
        return $this->cache['schema'];
    }

    /**
     * テーブル名からエンティティクラス名を取得する
     *
     * @param string|array $tablename テーブル名
     * @return string|false エンティティクラス名
     */
    public function getEntityClass($tablename)
    {
        $map = $this->_tableMap()['entityClass'];
        foreach ((array) $tablename as $tn) {
            foreach ((array) $this->convertEntityName($tn) as $t) {
                if (isset($map[$t])) {
                    return $map[$t];
                }
            }
        }
        return Entity::class;
    }

    /**
     * テーブル名からゲートウェイクラス名を取得する
     *
     * @param string $tablename テーブル名
     * @return string ゲートウェイクラス名
     */
    public function getGatewayClass($tablename)
    {
        $map = $this->_tableMap()['gatewayClass'];
        $tablename = $this->convertTableName($tablename);
        return $map[$tablename] ?? TableGateway::class;
    }

    /**
     * エンティティ名からテーブル名へ変換する
     *
     * @param string $entityname エンティティ名
     * @return string テーブル名
     */
    public function convertTableName($entityname)
    {
        $map = $this->_tableMap()['EtoT'];
        return $map[$entityname] ?? $entityname;
    }

    /**
     * テーブル名からエンティティ名へ変換する
     *
     * 複数のマッピングがあるときは最初の名前を返す。
     *
     * @param string $tablename テーブル名
     * @return string エンティティ名
     */
    public function convertEntityName($tablename)
    {
        $map = $this->_tableMap()['TtoE'];
        return first_value($map[$tablename] ?? []) ?: $tablename;
    }

    /**
     * 仮想テーブルを宣言する
     *
     * {@link select()} の引数に名前をつけて簡易に呼び出すことができる。
     * ややこしくなるので既に存在するテーブル名と同じものは登録できない。
     *
     * ```php
     * # 仮想テーブルを追加する
     * $db->declareVirtualTable('v_article_comment', [
     *     't_article@active' => [
     *         '*',
     *         '*t_comment@active' => [
     *             '*',
     *         ],
     *     ],
     * ]);
     *
     * # 追加した仮想テーブルをあたかもテーブルのように使用できる
     * $db->selectArray('v_article_comment'); // 上で追加した配列を与えるのと同じ
     * ```
     *
     * @inheritdoc select()
     *
     * @param string|array $vtableName 仮想テーブル名
     * @return $this 自分自身
     */
    public function declareVirtualTable($vtableName, $tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        assert(!$this->getSchema()->hasTable($vtableName));
        $this->vtables[$vtableName] = array_combine(QueryBuilder::CLAUSES, [$tableDescriptor, $where, $orderBy, $limit, $groupBy, $having]);
        return $this;
    }

    /**
     * CTE を宣言する
     *
     * ここで宣言された CTE は {@link select()} で使用されたときに自動的に WITH 句に追加されるようになる。
     * 「共通的に VIEW 的なものを宣言し、後で使用できるようにする」といったイメージ。
     *
     * ```php
     * $db->declareCommonTable([
     *     'c_table1'    => 'SELECT 1, 2',
     *     'c_table2(n)' => 'SELECT 1 UNION SELECT n + 1 FROM c_table2 WHERE n < 5',
     * ]);
     *
     * # 追加した CTE は自動で WITH 句に追加される
     * $db->selectArray('c_table2');
     * // WITH RECURSIVE c_table2(n) AS(SELECT 1 UNION SELECT n + 1 FROM c_table2 WHERE n < 5)SELECT c_table2.* FROM c_table2
     * ```
     *
     * @param array $expressions CTE 配列
     * @return $this 自分自身
     */
    public function declareCommonTable($expressions)
    {
        foreach ($expressions as $name => $expression) {
            if ($expression instanceof \Closure) {
                $expression = $expression($this);
            }
            if (is_array($expression)) {
                $expression = $this->createQueryBuilder()->build($expression);
            }

            $cols = '';
            $p = strpos($name, '(');
            if ($p !== false) {
                $cols = substr($name, $p);
                $name = trim(substr($name, 0, $p));
            }

            $columns = [];
            foreach (split_noempty(',', trim($cols, '()')) as $col) {
                $columns[] = new Column($col, Type::getType('integer'));
            }

            $table = new Table($name, $columns);
            $table->addOption('cte', [
                'columns' => $cols,
                'query'   => $expression,
            ]);
            $this->getSchema()->addTable($table);
        }

        return $this;
    }

    /**
     * 仮想テーブルを取得する
     *
     * 原則内部向け。
     *
     * @param string $vtableName
     * @return array|null 設定されていたらそれを、なかったら null
     */
    public function getVirtualTable($vtableName)
    {
        return $this->vtables[$vtableName] ?? null;
    }

    /**
     * 仮想カラムを追加する
     *
     * ここで追加したカラムはあたかもテーブルにあるかのように select, where することができる。
     * 仮想カラムは TableDescripter で使える記法すべてを使うことができる。
     *
     * ```php
     * # 仮想カラムを追加する
     * $db->overrideColumns([
     *     'table_name' => [
     *         // 単純なエイリアス。ほぼ意味はない
     *         'hoge'      => 'fuga',
     *         // 姓・名を結合してフルネームとする（php 版）
     *         'fullname1' => function($row) { return $v['sei'] . $v['mei']; },
     *         // 姓・名の SQL 版
     *         'fullname2' => 'CONCAT(sei, mei)',
     *         // 姓・名の SQL 版（修飾子）
     *         'fullname3' => 'CONCAT(%1$s.sei, %1$s.mei)',
     *         // 上記の例は実は簡易指定で本来は下記の配列を渡す（非配列を渡すと下記でいう select が渡されたとみなす）
     *         'misc'      => [
     *             'select'   => null,  // select 時の仮想カラムの定義（文字列や Expression やクエリビルダなど全て使用できる）
     *             'affect'   => null,  // affect 時の仮想カラムの定義（実列名をキーとする連想配列を返すクロージャを指定する）
     *             'type'     => null,  // 仮想カラムの型
     *             'lazy'     => false, // 遅延評価するか（後述）
     *             'implicit' => false, // !column などの一括取得に含めるか
     *         ],
     *         // null を渡すと仮想カラムの削除になる
     *         'deletedVcolumn' => null,
     *     ],
     * ]);
     *
     * # 追加した仮想カラムをあたかもテーブルカラムのように使用できる
     * $db->selectArray('table_name' => [
     *     'hoge',
     *     'fullname1', // php 的に文字列結合（$v['sei'] . $v['mei']）する
     *     'fullname2', // SQL 的に文字列結合（CONCAT(sei, mei)）する
     *     'fullname3', // 修飾子付きで SQL 的に文字列結合（CONCAT(AAA.sei, AAA.mei)）する
     *     // さらにエイリアスも使用できる
     *     'fullalias' => 'fullname1',
     * ]);
     * ```
     *
     * 'fullname3' について補足すると、 select が文字列であるとき、その実値は `sprintf($select, 修飾子)` となる。
     * 仮想カラムはあらゆる箇所で使い回される想定なので、「その時のテーブル名・エイリアス（修飾子）」を決めることができない。
     * かと言って修飾子を付けないと曖昧なカラムエラーが出ることがある。
     * `%1$s` しておけば sprintf で「現在の修飾子」が埋め込まれるためある程度汎用的にできる。
     * ただし、その弊害として素の % を使うときは %% のようにエスケープする必要があるので注意。
     *
     * lazy で指定する遅延評価について、例えば TableA が TableB のサブクエリを仮想カラムに設定し、TableB も TableA のサブクエリを設定している場合、即時評価だと場合によっては循環参照になってしまう or 仮想カラムが定義されていない状態でクエリビルダが走ってしまう、という事が起きる。
     * そんな時、 lazy: true とすることで仮想カラムの評価を実行時に遅延することができる。
     * また、Database を単一引数とするクロージャを select に渡すと暗黙的に lazy: true とすることができる。
     *
     * ```php
     * # 仮想カラムの遅延評価
     * $db->overrideColumns([
     *     'tableA' => [
     *         // このようにしないと $db->subselectArray('tableB') が即時評価され、 tableB の評価が始まってしまう（そのとき tableB に parent という仮想カラムはまだ生えていない）
     *         // つまり children の結果セットに parent が含まれることが無くなってしまう
     *         'children' => [
     *             'lazy'   => true,
     *             'select' => function () use ($db) {
     *                 return $db->subselectArray('tableB');
     *             },
     *         ],
     *     ],
     *     'tableB' => [
     *         // 同上（lazy 指定ではなく Database 引数版）
     *         'parent' => function (Database $db) {
     *             return $db->subselectTuple('tableA');
     *         },
     *     ],
     * ]);
     * ```
     *
     * affect にクロージャを指定すると insert/update 時にそのカラムが来た場合に他のカラム値に変換することができる。
     *
     * ```php
     * # 仮想カラムの更新処理
     * $db->overrideColumns([
     *     'table_name' => [
     *         'fullname' => [
     *             // 仮想カラム更新時の処理（$value はその仮想カラムとして飛んできた値, $row は行全体）
     *             'affect' => function ($value, $row) {
     *                 // fullname が飛んできたらスペース区切りで姓・名に分割して登録する
     *                 $parts = explode(' ', $value, 2);
     *                 return [
     *                     'sei' => $parts[0],
     *                     'mei' => $parts[1],
     *                 ];
     *             },
     *         ],
     *     ],
     * ]);
     * ```
     *
     * また、仮想といいつつも厳密には実際に定義されているカラムも指定可能。
     * これを利用するとカラムのメタ情報を上書きすることができる。
     *
     * ```php
     * # 仮想ではなく実カラムを指定
     * $db->overrideColumns([
     *     'table_name' => [
     *         'checkd_option'      => [
     *             // checkd_option という実際に存在するカラムの型を simple_array に上書きできる
     *             'type'     => Type::getType('simple_array'),
     *             // カラムオプションを変更できる
     *             'anywhere' => [],
     *         ],
     *     ],
     * ]);
     * ```
     *
     * なお、実際のデータベース上の型が変わるわけではない。あくまで「php が思い込んでいる型」の上書きである。
     * php 側の型が活用される箇所はそう多くないが、例えば下記のような処理では上書きすると有用なことがある。
     *
     * - {@link Database::setAutoCastType()} による型による自動キャスト
     * - {@link Database::anywhere()} によるよしなに検索
     *
     * @param array $definition 仮想カラム定義
     * @return $this 自分自身
     */
    public function overrideColumns($definition)
    {
        $schema = $this->getSchema();
        foreach ($definition as $tname => $columns) {
            foreach ($columns as $cname => $def) {
                if ($def !== null) {
                    if (!is_array($def)) {
                        $def = ['select' => $def];
                    }
                    $def += [
                        'lazy'   => false,
                        'type'   => null,
                        'select' => null,
                        'affect' => null,
                    ];
                    if (is_array($def['select'])) {
                        $def['select'] = $this->operator($def['select']);
                    }
                    if (!isset($def['type']) && $def['select'] instanceof TableGateway) {
                        $def['type'] = 'array';
                    }
                    if (!isset($def['type']) && $def['select'] instanceof QueryBuilder) {
                        $submethod = $def['select']->getSubmethod();
                        if (is_null($submethod)) {
                            $def['type'] = 'integer';
                        }
                        elseif (is_bool($submethod)) {
                            $def['type'] = 'boolean';
                        }
                        else {
                            $def['type'] = 'array';
                        }
                    }
                    if (!isset($def['type']) && $def['select'] instanceof Queryable) {
                        $def['type'] = 'string';
                    }
                    if (!isset($def['type']) && $def['select'] instanceof \Closure) {
                        $ref = new \ReflectionFunction($def['select']);
                        $rtype = $ref->getReturnType();
                        if ($rtype instanceof \ReflectionNamedType) {
                            $typename = strtolower($rtype->getName());
                            switch ($typename) {
                                case 'void':
                                    break;
                                case 'bool':
                                    $def['type'] = 'boolean';
                                    break;
                                case 'int':
                                    $def['type'] = 'integer';
                                    break;
                                default:
                                    $def['type'] = Type::hasType($typename) ? $typename : 'object';
                                    break;
                            }
                        }
                    }

                    if ($def['select'] instanceof \Closure) {
                        $ref = new \ReflectionFunction($def['select']);
                        $params = $ref->getParameters();
                        $rtype = isset($params[0]) ? $params[0]->getType() : null;
                        if ($rtype instanceof \ReflectionNamedType && is_a($rtype->getName(), Database::class, true)) {
                            $def['lazy'] = true;
                        }
                    }
                }
                $schema->setTableColumn($tname, $cname, $def);
            }
        }
        return $this;
    }

    /**
     * 外部キーをまとめて追加する
     *
     * addForeignKey を複数呼ぶのとほぼ等しいが、遅延実行されて必要になったときに追加される。
     * options で onUpdate/Delete や条件付き外部キーとして condition が与えられる。
     * onDelete CASCADE はアプリレイヤーで可能な限りエミュレーションされる（onUpdate は未対応）。
     * condition を指定すると条件付き外部キーとなり、JOIN するときに暗黙的に条件が含まれるようになる（subselect 等も同様）。
     * これは「マスターテーブル」のようなごちゃまぜテーブルに対してテーブルごとの外部キーを張らざるを得ない状況を想定している。
     * condition は現在のところ文字列での指定しかできない。
     *
     * ```php
     * # 下記のような配列を与える
     * $db->addRelation([
     *     'ローカルテーブル名' => [
     *         '外部テーブル名' => [
     *             '外部キー名' => [
     *                 'ローカルカラム名1' => '外部カラム名1',
     *                 'ローカルカラム名2' => '外部カラム名2',
     *                 'options' => [
     *                     'onDelete'  => 'CASCADE',                   // CASCADE 動作はアプリでエミュレーションされる
     *                     'condition' => ['colname' => 'cond value'], // join や subselect 時に自動付与される条件となる
     *                 ],
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
     * @return array 追加した外部キー名
     */
    public function addRelation($relations)
    {
        $result = [];
        foreach ($relations as $localTable => $foreignTables) {
            foreach ($foreignTables as $foreignTable => $relation) {
                foreach ($relation as $fkname => $fkdata) {
                    $result[] = $this->getSchema()->addForeignKeyLazy($localTable, $foreignTable, $fkdata, is_int($fkname) ? null : $fkname);
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
     * @param string|array $fkdata 外部キー情報
     * @param string|null $fkname 外部キー名。省略時は自動命名
     * @return ForeignKeyConstraint 追加した外部キーオブジェクト
     */
    public function addForeignKey($localTable, $foreignTable, $fkdata, $fkname = null)
    {
        $fkdata = arrayize($fkdata);
        $options = array_unset($fkdata, 'options', []) + ['virtual' => true];
        $columnsMap = array_rekey($fkdata, fn($k, $v) => trim(is_int($k) ? $v : $k));

        // 省略時は自動命名
        if (!$fkname) {
            $fkname = $localTable . '_' . $foreignTable . '_' . count($this->getSchema()->getTableForeignKeys($localTable));
        }

        // 外部キーオブジェクトの生成
        $fk = new ForeignKeyConstraint(array_keys($columnsMap), $foreignTable, array_values($columnsMap), $fkname, $options);

        return $this->getSchema()->addForeignKey($fk, $localTable);
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
        $columnsMap = array_rekey(arrayize($columnsMap), function ($k, $v) { return is_int($k) ? $v : $k; });

        // 外部キーオブジェクトの生成
        $fk = new ForeignKeyConstraint(array_keys($columnsMap), $foreignTable, array_values($columnsMap));

        return $this->getSchema()->ignoreForeignKey($fk, $localTable);
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
     * @param ?callable $catch 例外発生時の処理
     * @param array $options トランザクションオプション
     * @param bool $throwable 例外を投げるか返すか
     * @return mixed メイン処理の返り値
     */
    public function transact($main, $catch = null, $options = [], $throwable = true)
    {
        return $this->transaction($main, $catch, $options)->perform($throwable);
    }

    /**
     * トランザクションオブジェクトを返す
     *
     * $options は {@link Transaction} を参照。
     *
     * @param ?callable $main メイン処理
     * @param ?callable $catch 例外発生時の処理
     * @param array $options トランザクションオプション
     * @return Transaction トランザクションオブジェクト
     */
    public function transaction($main = null, $catch = null, $options = [])
    {
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
     * @param array|int|null $options トランザクションオプション
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
     * @param mixed $params bind パラメータ
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
     * @param mixed $params bind パラメータ
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
     * 値を保持しつつプレースホルダーを返すオブジェクトを返す
     *
     * ```php
     * $binder = $db->binder();
     * // このようにすると値を保持しつつプレースホルダー文字列を返す
     * $sql = "SELECT * FROM t_table WHERE id IN ({$binder([1, 2, 3])}) AND status = {$binder(1)}";
     * // $binder はそのままパラメータとして使える
     * $db->fetchAll($sql, $binder);
     * // prepare: SELECT * FROM t_table WHERE id IN (?, ?, ?) AND status = ?
     * // execute: SELECT * FROM t_table WHERE id IN (1, 2, 3) AND status = 1
     * ```
     *
     * @return callable|\ArrayObject
     */
    public function binder()
    {
        return new class extends \ArrayObject {
            public function __invoke($param)
            {
                if ($param instanceof Queryable) {
                    $this($param->getParams());
                    return $param->getQuery();
                }

                if (is_array($param)) {
                    if (count($param) === 0) {
                        return $this(null);
                    }
                    return implode(', ', array_map($this, $param));
                }

                $this[] = $param;
                return '?';
            }
        };
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
     * @param iterable $params bind 配列
     * @return string 値が埋め込まれた実行可能なクエリ
     */
    public function queryInto($sql, iterable $params = [])
    {
        $params = $params instanceof \Traversable ? iterator_to_array($params) : $params;

        if ($sql instanceof Queryable) {
            $sql = $sql->merge($params);
        }

        $parser = new Parser($this->getPlatform()->createSQLParser());
        return $parser->convertQuotedSQL($sql, $params, fn($v) => $this->quote($v));
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
     * @param ?array $params bind 値が渡される
     * @return mixed ? が埋め込まれた正規化されたクエリ文字列
     */
    public function bindInto($data, ?array &$params)
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
     * | 28 | `['hoge' => Operator::equal(1)]`               | 略                                 | No.5 と同じだが、 equal を別のメソッドに変えればシンプルな key => value 配列を保ちつつ演算子が使える
     * | 31 | `['hoge' => function () {}]`                   | 略                                 | クロージャを渡すとクロージャの実行結果が「あたかもそこにあるかのように」振る舞う
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
     * $stmt->executeAffect(['id' => 1]);                         // WHERE id = 1 で実行できる
     * $stmt->executeAffect(['id' => 2]);                         // WHERE id = 2 で実行できる
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
     * # No.28（Operator::method を呼ぶと左辺がキーで遅延設定される）
     * $wheres = [
     *     // like を呼べばキーに演算子を埋め込まなくても LIKE できる
     *     'name' => Operator::like('hoge'),
     *     // not も使える
     *     'text' => Operator::like('hoge')->not(),
     * ];
     * // WHERE name LIKE '%hoge%' AND NOT(text LIKE '%hoge%')
     *
     * # No.31（クロージャを使うと三項演算子を駆使する必要はない上、スコープを閉じ込めることができる）
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
     * @param ?array $params bind 値が格納される
     * @param string $andor 結合演算子（内部向け引数なので気にしなくて良い）
     * @param ?bool $filterd 条件が全て ! などでフィルタされたら true が格納される（内部向け引数なので気にしなくて良い）
     * @return array where 配列
     */
    public function whereInto(array $identifier, ?array &$params, $andor = 'OR', &$filterd = null)
    {
        $params = $params ?? [];
        $orand = $andor === 'AND' ? 'OR' : 'AND';
        $criteria = [];

        foreach ($identifier as $cond => $value) {
            if ($value instanceof \Closure) {
                $value = $value($this);
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
                        $ors = $this->whereInto([$op => $vs], $params, $orand, $filterd);
                        array_put($cds, implode(" $andor ", Adhoc::wrapParentheses($ors)), null, function ($v) { return !Adhoc::is_empty($v); });
                    }
                    array_put($criteria, implode(" $andor ", Adhoc::wrapParentheses($cds)), null, function ($v) { return !Adhoc::is_empty($v); });
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
                    if (strpos($cond, '/* vcolumn') !== false) {
                        array_shift($vcopy);
                    }
                    if (Adhoc::is_empty($vcopy)) {
                        $filterd = ($filterd ?? true) && true;
                        continue;
                    }
                    $cond = substr($cond, 1);
                }

                // AND,OR だけは特例処理（カラム指定と曖昧だが "OR" なんて識別子は作れないし指定できないのでOK）
                // 仮に指定するにしても "`OR`" になるはずなので文字列的には一致しない
                $CANDOR = strtoupper($cond);
                if ($CANDOR === 'AND' || $CANDOR === 'OR') {
                    $ors = $this->whereInto(arrayize($value), $params, $CANDOR === 'AND' ? 'OR' : 'AND', $filterd);
                    array_put($criteria, implode(" $CANDOR ", Adhoc::wrapParentheses($ors)), null, function ($v) { return !Adhoc::is_empty($v); });
                    continue;
                }
                // 同じく、NOT も特別扱い
                if ($CANDOR === 'NOT') {
                    $nots = $this->whereInto(arrayize($value), $params, $andor, $filterd);
                    array_put($criteria, 'NOT (' . implode(" $orand ", Adhoc::wrapParentheses($nots)) . ')', null, function ($v) { return !Adhoc::is_empty($v); });
                    continue;
                }

                // Operator は列を後定義したものを
                if ($value instanceof Operator) {
                    if (strpos($cond, ':') !== false) {
                        throw new \UnexpectedValueException('OPFUNC and :OP both specified.');
                    }
                    $value->lazy($cond, $this->getCompatiblePlatform());
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
                        $cond .= $value instanceof QueryBuilder ? ' IN ?' : ' = ?'; // IN のカッコはビルダが付けてくれる
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
                        if ($diff === 1) {
                            $cond .= ' = ?'; // for compatible
                        }
                        else {
                            $cond .= ' IN (' . implode(',', array_fill(0, $diff, '?')) . ')';
                        }
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
                $operator = new Operator($this->getCompatiblePlatform(), $ope, $cond, $value);
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
        [$alias, $tname] = Alias::split($table);
        $tname = $this->convertTableName($tname);
        $alias = $alias ?: $tname;

        // オプションを取得しておく
        $goptions = $this->getUnsafeOption('anywhereOption');
        $toptions = $schema->getTable($tname)->getOptions();

        // リレーションを漁る
        $keys = array_fill_keys($schema->getTablePrimaryKey($tname)->getColumns(), true);
        foreach ($schema->getForeignKeys($tname, null) as $fkey) {
            $keys += array_fill_keys($fkey->getForeignColumns(), true);
        }
        foreach ($schema->getForeignKeys(null, $tname) as $fkey) {
            $keys += array_fill_keys($fkey->getLocalColumns(), true);
        }

        // 検索ワードの正規化
        $is_numeric = !$quoted && is_numeric($word);
        $date_fromto = $quoted ? false : date_fromto(null, $word);
        if ($quoted) {
            $inwords = '%' . $this->getCompatiblePlatform()->escapeLike($word) . '%';
        }
        else {
            $inwords = '%' . preg_replace('#[\s　]+#u', '%', $this->getCompatiblePlatform()->escapeLike($word)) . '%';
        }

        $where = [];
        foreach ($schema->getTableColumns($tname) as $cname => $column) {
            $coptions = $column->getPlatformOptions();
            $coptions = array_replace_recursive(
                $goptions,
                $toptions['anywhere'] ?? [],
                $coptions['anywhere'] ?? [],
                $goptions[$tname] ?? [],
                $goptions[$tname][$cname] ?? []
            );
            if (!$coptions['enable']) {
                continue;
            }
            $type = $coptions['type'] ?: $column->getType()->getName();
            $comment = $coptions['comment'] ? $this->getCompatiblePlatform()->commentize($coptions['comment'], true) . ' ' : '';
            $key = $alias . '.' . $cname;
            switch ($type) {
                // 完全一致系
                case Types::BOOLEAN:
                case Types::BIGINT:
                case Types::INTEGER:
                case Types::SMALLINT:
                case Types::FLOAT:
                case Types::DECIMAL:
                    if ($is_numeric) {
                        if ($coptions['keyonly'] && (!isset($keys[$cname]))) {
                            break;
                        }
                        $where[$comment . $key . ' = ?'] = $word;
                    }
                    break;

                // 範囲系
                case Types::DATETIME_MUTABLE:
                case Types::DATETIME_IMMUTABLE:
                case Types::DATETIMETZ_MUTABLE:
                case Types::DATETIMETZ_IMMUTABLE:
                case Types::DATE_MUTABLE:
                case Types::DATE_IMMUTABLE:
                    if ($date_fromto) {
                        if (!$coptions['greedy'] && $is_numeric) {
                            break;
                        }
                        $format = 'Y-m-d';
                        if ($type !== Types::DATE_MUTABLE && $type !== Types::DATE_IMMUTABLE) {
                            $format .= ' H:i:s';
                        }
                        $from = date($format, $date_fromto[0]);
                        $to = date($format, $date_fromto[1] - 1);
                        $where[$comment . $key . " BETWEEN ? AND ?"] = [$from, $to];
                    }
                    break;

                // 包含系
                case Types::STRING:
                case Types::TEXT:
                    if (!$coptions['greedy'] && ($is_numeric || $date_fromto)) {
                        break;
                    }
                    $collate = '';
                    if ($coptions['collate']) {
                        $collate = ' collate ' . $coptions['collate'];
                    }
                    $where[$comment . $key . $collate . " LIKE ?"] = $inwords;
                    break;

                // いかんともしがたいので無視（array や json はなんとかなるかもしれない ）
                case Types::GUID:
                case Types::OBJECT:
                case Types::ARRAY:
                case Types::SIMPLE_ARRAY:
                case Types::JSON:
                case Types::BINARY:
                case Types::BLOB:
                default:
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
     * 暖気運転を行う
     *
     * 指定テーブルのすべてのインデックスに対して COUNT クエリを発行する。
     * RDBMS にも依るが、これによって・・・
     *
     * - レコードがバッファプールに乗る（クラスタードインデックスの場合）
     * - インデックスがバッファプールに乗る
     *
     * 概して mysql (InnoDB) 用だが、他の RDBMS でも呼んで無駄にはならないはず。
     *
     * @param array|string $table_names テーブル名配列（glob 的記法が使える。省略時は全テーブル）
     * @return array 暖気クエリの結果（現在は [table => [index => COUNT]] だが、COUNt 部分は変更されることがある）
     */
    public function warmup($table_names = [])
    {
        $DELIMITER = '@-@-@';
        $cplatform = $this->getCompatiblePlatform();

        $tables = $this->getSchema()->getTables($table_names);
        $columns = [];
        foreach ($tables as $table_name => $table) {
            $indexes = $table->getIndexes();
            foreach ($indexes as $index) {
                // DBAL が生成した暗黙のインデックスは除外する
                // https://github.com/doctrine/dbal/blob/v2.10.1/lib/Doctrine/DBAL/Schema/Index.php#L203 はバグだと思うんだけどなぁ
                if ($index->hasOption('lengths')) {
                    $alias = $table_name . $DELIMITER . $index->getName();
                    $select = $this->select([$table_name => $cplatform->getCountExpression('*')]);
                    $select->setAutoOrder(false);
                    $select->hint($cplatform->getIndexHintSQL($index->getName()));
                    $columns[$alias] = $select;
                }
            }
        }

        $result = [];
        foreach ($this->createQueryBuilder()->setAutoOrder(false)->addSelect($columns)->tuple() as $key => $count) {
            [$tname, $iname] = explode($DELIMITER, $key, 2);
            $result[$tname][$iname] = $count;
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
     * @param ?\Closure $converter 行ごとの変換クロージャ
     * @return array|bool|mixed クエリ結果
     */
    public function perform($row_provider, $fetch_mode, $converter = null)
    {
        switch ($fetch_mode) {
            default:
                throw new \BadMethodCallException("unknown fetch method '$fetch_mode'.");

            /// 配列の配列系
            case self::METHOD_ARRAY:
                $result = [];
                foreach ($row_provider as $row) {
                    $row = $converter ? $converter($row) : $row;
                    $result[] = $row;
                }
                return $result;
            case self::METHOD_ASSOC:
                $result = [];
                foreach ($row_provider as $row) {
                    $row = $converter ? $converter($row) : $row;
                    foreach ($row as $e) {
                        $key = $e;
                        break;
                    }
                    /** @noinspection PhpUndefinedVariableInspection */
                    $result[$key] = $row;
                }
                return $result;

            /// 配列系
            case self::METHOD_LISTS:
                $result = [];
                foreach ($row_provider as $row) {
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
                foreach ($row_provider as $row) {
                    $row = $converter ? $converter($row) : $row;
                    $i = 0;
                    foreach ($row as $e) {
                        if ($i++ === 1) {
                            $val = $e;
                            break;
                        }
                        $key = $e;
                    }
                    /** @noinspection PhpUndefinedVariableInspection */
                    $result[$key] = $val;
                }
                return $result;

            /// シンプル系
            case self::METHOD_TUPLE:
                $result = false;
                $first = true;
                foreach ($row_provider as $row) {
                    $row = $converter ? $converter($row) : $row;
                    if ($first) {
                        $first = false;
                        $result = $row;
                    }
                    else {
                        throw new TooManyException('record is too many.');
                    }
                }
                return $result;
            case self::METHOD_VALUE:
                $result = false;
                $first = true;
                foreach ($row_provider as $row) {
                    $row = $converter ? $converter($row) : $row;
                    if ($first) {
                        $first = false;
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

    public function fetchOrThrow($method, $arguments)
    {
        $result = $this->{"fetch$method"}(...$arguments);
        // Value, Tuple は [] を返し得ないし、複数行系も false を返し得ない
        if ($result === [] || $result === false) {
            throw new NonSelectedException('record is not found.');
        }
        return $result;
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
     * @param string|QueryBuilder|Statement $sql クエリ
     * @param iterable $params bind パラメータ
     * @return array|Entityable[] クエリ結果
     */
    public function fetchArray($sql, iterable $params = [])
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
     * @param string|QueryBuilder|Statement $sql クエリ
     * @param iterable $params bind パラメータ
     * @return array|Entityable[] クエリ結果
     */
    public function fetchAssoc($sql, iterable $params = [])
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
     * @param string|QueryBuilder|Statement $sql クエリ
     * @param iterable $params bind パラメータ
     * @return array|Entityable[] クエリ結果
     */
    public function fetchLists($sql, iterable $params = [])
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
     * @param string|QueryBuilder|Statement $sql クエリ
     * @param iterable $params bind パラメータ
     * @return array|Entityable[] クエリ結果
     */
    public function fetchPairs($sql, iterable $params = [])
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
     * @param string|QueryBuilder|Statement $sql クエリ
     * @param iterable $params bind パラメータ
     * @return array|Entityable|false クエリ結果
     */
    public function fetchTuple($sql, iterable $params = [])
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
     * @param string|QueryBuilder|Statement $sql クエリ
     * @param iterable $params bind パラメータ
     * @return mixed クエリ結果
     */
    public function fetchValue($sql, iterable $params = [])
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
     * @used-by selectArrayOrThrow()
     * @used-by selectArrayInShare()
     * @used-by selectArrayForUpdate()
     * @used-by selectArrayForAffect()
     * @used-by selectAssoc()
     * @used-by selectAssocOrThrow()
     * @used-by selectAssocInShare()
     * @used-by selectAssocForUpdate()
     * @used-by selectAssocForAffect()
     * @used-by selectLists()
     * @used-by selectListsOrThrow()
     * @used-by selectListsInShare()
     * @used-by selectListsForUpdate()
     * @used-by selectListsForAffect()
     * @used-by selectPairs()
     * @used-by selectPairsOrThrow()
     * @used-by selectPairsInShare()
     * @used-by selectPairsForUpdate()
     * @used-by selectPairsForAffect()
     * @used-by selectTuple()
     * @used-by selectTupleOrThrow()
     * @used-by selectTupleInShare()
     * @used-by selectTupleForUpdate()
     * @used-by selectTupleForAffect()
     * @used-by selectValue()
     * @used-by selectValueOrThrow()
     * @used-by selectValueInShare()
     * @used-by selectValueForUpdate()
     * @used-by selectValueForAffect()
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
     * {@uses Database::select()} の array 版（{@link fetchArray()} も参照）
     *
     * @inheritdoc Database::select()
     * @return array|Entityable[]
     */
    public function selectArray($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->array();
    }

    /**
     * {@uses Database::select()} の assoc 版（{@link fetchAssoc()} も参照）
     *
     * @inheritdoc Database::select()
     * @return array|Entityable[]
     */
    public function selectAssoc($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->assoc();
    }

    /**
     * {@uses Database::select()} の lists 版（{@link fetchLists()} も参照）
     *
     * @inheritdoc Database::select()
     * @return array
     */
    public function selectLists($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->lists();
    }

    /**
     * {@uses Database::select()} の pairs 版（{@link fetchPairs()} も参照）
     *
     * @inheritdoc Database::select()
     * @return array
     */
    public function selectPairs($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->pairs();
    }

    /**
     * {@uses Database::select()} の tuple 版（{@link fetchTuple()} も参照）
     *
     * @inheritdoc Database::select()
     * @return array|Entityable|false
     */
    public function selectTuple($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->tuple();
    }

    /**
     * {@uses Database::select()} の value 版（{@link fetchValue()} も参照）
     *
     * @inheritdoc Database::select()
     * @return mixed
     */
    public function selectValue($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->value();
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
     * @used-by entityArrayOrThrow()
     * @used-by entityArrayInShare()
     * @used-by entityArrayForUpdate()
     * @used-by entityArrayForAffect()
     * @used-by entityAssoc()
     * @used-by entityAssocOrThrow()
     * @used-by entityAssocInShare()
     * @used-by entityAssocForUpdate()
     * @used-by entityAssocForAffect()
     * @used-by entityTuple()
     * @used-by entityTupleOrThrow()
     * @used-by entityTupleInShare()
     * @used-by entityTupleForUpdate()
     * @used-by entityTupleForAffect()
     *
     * @inheritdoc select()
     */
    public function entity($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->cast(null);
    }

    /**
     * {@uses Database::entity()} の array 版
     *
     * @inheritdoc Database::entity()
     * @return Entityable[]
     */
    public function entityArray($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->array();
    }

    /**
     * {@uses Database::entity()} の assoc 版
     *
     * @inheritdoc Database::entity()
     * @return Entityable[]
     */
    public function entityAssoc($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->assoc();
    }

    /**
     * {@uses Database::entity()} の tuple 版
     *
     * @inheritdoc Database::entity()
     * @return Entityable
     */
    public function entityTuple($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        return $this->entity($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->tuple();
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
     * @used-by selectExists()
     * @used-by selectNotExists()
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
     * @param iterable $params SQL パラメータ
     * @return Yielder foreach で回せるオブジェクト
     */
    public function yield($sql, iterable $params = [])
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
     * @param iterable $params SQL パラメータ
     * @param array $config 出力パラメータ
     * @param string|resource|null $file 出力先。 null を与えると標準出力に書き出される
     * @return int 書き込みバイト数
     */
    public function export($generator, $sql, iterable $params = [], $config = [], $file = null)
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
     * 子供レコード配列を取得するビルダを返す
     *
     * このメソッドを使うと自身のレコード配列に子供レコードを生やすことができる。
     * この処理はクエリを2回投げることで実現される。つまり 1 + N 問題は起こらない（tuple だけではなく array/assoc でも同様）。
     * この挙動は setLazyMode で変更可能。
     *
     * WHERE や ORDER などの条件も完全に活かすことができるが、LIMIT だけは扱いが異なる（下記のサンプルコードを参照）。
     * これを利用するといわゆる「グループ内の上位N件取得」も簡単に実現できる。
     *
     * 親子の結合条件は原則として外部キーが前提。
     * 外部キーがない・特殊な条件で結合したい場合は親側のキーに `{cond}` でカラムを指定する。
     *
     * ```php
     * # t_parent に紐づく t_child レコードを引っ張る
     * $row = $db->selectTuple([
     *     't_parent P' => [
     *         'parent_id',
     *         // 外部キーが使用される
     *         'childarray'            => $db->subselectArray('t_child'),
     *         // 結合カラムを明示的に指定
     *         'childassoc{cid: pid}'  => $db->subselectAssoc('t_child'),
     *     ],
     * ]);
     *
     * # サブの limit は各行に対して作用する
     * $rows = $db->selectArray([
     *     't_parent P' => [
     *         'parent_id',
     *         // 各行に紐づく t_child の最新5件を取得する
     *         'latestchildren' => $db->subselectArray('t_child', [], ['update_time' => 'DESC'], 5),
     *     ],
     * ]);
     *
     * # 簡易記法としての配列形式（t_parent に紐づく t_child レコードを引っ張る）
     * $row = $db->selectTuple([
     *     't_parent P' => [
     *         'parent_id',
     *         // 親のキーがテーブル名（エイリアス）の役目を果たし、原則として assoc 相当の動作になる
     *         // つまり下記2つは全く同じ動作となる
     *         'childassoc1'            => $db->subselectAssoc('t_child'),
     *         't_child AS childassoc2' => ['*'],
     *     ],
     * ]);
     *
     * # ネストもできる（t_ancestor に紐づく t_parent に紐づく t_child レコードを引っ張る）
     * $row = $db->selectTuple([
     *     't_ancestor AS A' => [
     *         't_parent AS P' => [
     *             't_child AS C' => ['*'],
     *         ],
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
     *
     * @inheritdoc select()
     *
     * @return QueryBuilder サブクエリビルダオブジェクト
     */
    public function subselect($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = [])
    {
        $builder = $this->createQueryBuilder();
        return $builder->setLazyMode()->build(array_combine(QueryBuilder::CLAUSES, [$tableDescriptor, $where, $orderBy, $limit, $groupBy, $having]));
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
     * @used-by subexists()
     * @used-by notSubexists()
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
     * @used-by exists()
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

        $stmt = $this->executeSelect($builder, $builder->getParams());

        $cast = function ($var) {
            if ((!is_int($var) && !is_float($var)) && preg_match('#^-?([1-9]\d*|0)(\.\d+)?$#u', (string) $var, $match)) {
                return isset($match[2]) ? (float) $var : (int) $var;
            }
            return $var;
        };

        $selectCount = count($builder->getQueryPart('select'));
        $groupCount = count($builder->getQueryPart('groupBy'));
        if ($groupCount === 0 && $selectCount === 1) {
            return var_apply($stmt->fetchOne(), $cast); // value
        }
        elseif ($groupCount === 0 && $selectCount >= 2) {
            return var_apply($stmt->fetchAssociative(), $cast); // tuple
        }
        elseif ($groupCount === 1 && $selectCount === 2) {
            return var_apply($stmt->fetchAllKeyValue(), $cast); // pairs
        }
        elseif ($groupCount === 1 && $selectCount >= 3) {
            return var_apply($stmt->fetchAllAssociativeIndexed(), $cast); // assoc
        }
        else {
            return var_apply($stmt->fetchAllAssociative(), $cast); // array
        }
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
                [$ltable, $ftable] = first_keyvalue($schema->getForeignTable($fk));
                if ($ltable === $tablename) {
                    $cols += array_flip($fk->getLocalColumns());
                }
                if ($ftable === $tablename) {
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

                    [$ltable, $ftable] = first_keyvalue($schema->getForeignTable($fk));
                    if ($parentive && $ltable === $tablename) {
                        $fkcols[$fk->getName()][$ftable][] = $array_rekey($row, array_combine($fk->getLocalColumns(), $fk->getForeignColumns()));
                    }
                    if (!$parentive && $fk->getForeignTableName() === $tablename) {
                        $fkcols[$fk->getName()][$ltable][] = $array_rekey($row, array_combine($fk->getForeignColumns(), $fk->getLocalColumns()));
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
     * 配列を仮想テーブルとみなして差分をとる
     *
     * 指定された配列で仮想的なテーブルを作成し、NATURAL LEFT JOIN して無いものを返すイメージ。
     *
     * 配列はいわゆる「配列の配列」である必要がある。
     * 要素のキーが一致しない場合は例外を投げる。
     *
     * ```php
     * # hoge.jpg, fuga.jpg, piyo.jpg と t_s3 の差分をとる
     * $db->differ([
     *     'hoge' => ['path' => 'img/hoge.jpg'],
     *     'fuga' => ['path' => 'img/fuga.jpg'],
     *     'piyo' => ['path' => 'img/piyo.jpg'],
     * ], 't_s3');
     * // results: キーが維持されつつ存在しないものだけを返す
     * [
     *     'fuga' => ['path' => 'img/fuga.jpg'],
     * ];
     * ```
     *
     * @param array $array 対象配列
     * @param string $tablename 取得テーブル
     * @param array $wheres 対象テーブルの条件
     * @return array 突き合わせ結果
     */
    public function differ($array, $tablename, $wheres = [])
    {
        $keyname = Database::AUTO_PRIMARY_KEY;
        $tmpname = '__dbml_auto_join';

        $pkcols = $this->getSchema()->getTablePrimaryColumns($tablename);
        $columns = $this->getSchema()->getTable($tablename)->getColumns();

        $joincols = null;
        $selects = [];
        $params = [];
        foreach ($array as $key => $row) {
            $realrow = array_intersect_key($row, $columns);
            $realcols = array_keys($realrow);
            if (!$realcols) {
                throw new \InvalidArgumentException("row is empty#$key");
            }

            $joincols ??= $realcols;
            if ($joincols !== $realcols) {
                throw new \InvalidArgumentException("column is unmatched#$key (" . implode(',', $joincols) . ") != (" . implode(',', $realcols) . ")");
            }

            $selects[] = "SELECT ? $keyname, " . array_sprintf($realrow, '? %2$s', ', ');
            $params = array_merge($params, [$key], array_values($realrow));
        }
        if (!$selects) {
            return [];
        }

        $select = $this->createQueryBuilder();
        $select->select("$tmpname.$keyname");
        $select->from(new Expression('(' . implode(' UNION ', $selects) . ')', $params), $tmpname);
        $select->leftJoinOn($tablename, array_merge(arrayize($wheres), array_sprintf($joincols, "$tablename.%1\$s = $tmpname.%1\$s")));
        $select->where(array_sprintf($pkcols, "$tablename.%2\$s IS NULL"));
        $select->setAutoOrder(false);

        $rows = $select->assoc();
        $result = [];
        foreach ($array as $key => $row) {
            if (isset($rows[$key])) {
                $result[$key] = $row;
            }
        }
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
     * $stmt->executeAffect(['id' => 1]); // UPDATE t_table SET name = 'hoge' WHERE id = 1
     * $stmt->executeAffect(['id' => 2]); // UPDATE t_table SET name = 'hoge' WHERE id = 2
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
     * $stmt->executeAffect(['id' => 101, 'name' => 'hoge']);
     * $stmt->executeAffect(['id' => 102, 'name' => 'fuga']);
     * // UPDATE
     * $stmt = $db->prepareUpdate('t_table', [':name'], [':id']);
     * $stmt->executeAffect(['id' => 101, 'name' => 'HOGE']);
     * $stmt->executeAffect(['id' => 102, 'name' => 'FUGA']);
     * // DELETE
     * $stmt = $db->prepareDelete('t_table', [':id']);
     * $stmt->executeAffect(['id' => 101]);
     * $stmt->executeAffect(['id' => 102]);
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
     * @param iterable $params パラメータ
     * @return Statement プリペアドステートメント
     */
    public function prepare($sql, iterable $params = [])
    {
        if ($sql instanceof QueryBuilder) {
            return $this->prepare((string) $sql, $sql->getParams());
        }
        return new Statement($sql, $params, $this);
    }

    /**
     * 取得系クエリを実行する
     *
     * @inheritdoc Connection::executeQuery()
     */
    public function executeSelect($query, iterable $params = [])
    {
        $params = $params instanceof \Traversable ? iterator_to_array($params) : $params;
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
     * @inheritdoc Connection::executeStatement()
     */
    public function executeAffect($query, iterable $params = [])
    {
        $params = $params instanceof \Traversable ? iterator_to_array($params) : $params;
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
            $this->affectedRows = null;
            $this->affectedRows = $this->getMasterConnection()->executeStatement($query, $params);
            // 利便性のため $this->affectedRows には代入しない（こうしておくと mysqli においてマッチ行と変更行が得られる）
            $cconnection = new CompatibleConnection($this->getMasterConnection());
            return $cconnection->alternateMatchedRows() ?? $this->affectedRows;
        }
        catch (\Exception $ex) {
            $this->unstackAll();
            throw $ex;
        }
    }

    /**
     * 取得系クエリを非同期で実行する
     *
     * このメソッドはすぐに処理を返し、callable を返す。
     * その間もクエリは実行されており、callable を実行すると最終結果が得られる。
     *
     * 非常に実験的な機能で現実装は mysqli/pgsql のみの対応。仕様は互換性を考慮せず変更されることがある。
     *
     * @inheritdoc Connection::executeQuery()
     * @return callable 結果を返すクロージャ
     */
    public function executeSelectAsync($query, iterable $params = [])
    {
        $ticker = $this->executeAsync([$query => $params], $this->getSlaveConnection());
        return fn() => $ticker(0); // @codeCoverageIgnore
    }

    /**
     * 更新系クエリを非同期で実行する
     *
     * このメソッドはすぐに処理を返し、callable を返す。
     * その間もクエリは実行されており、callable を実行すると最終結果が得られる。
     *
     * 非常に実験的な機能で現実装は mysqli/pgsql のみの対応。仕様は互換性を考慮せず変更されることがある。
     *
     * @inheritdoc Connection::executeStatement()
     * @return callable 結果を返すクロージャ
     */
    public function executeAffectAsync($query, iterable $params = [])
    {
        $ticker = $this->executeAsync([$query => $params], $this->getMasterConnection());
        return fn() => $ticker(0); // @codeCoverageIgnore
    }

    /**
     * 複数クエリを非同期で実行する
     *
     * このメソッドはすぐに処理を返し、無名クラスを返す。
     * tick を実行すると処理が進むので呼び元で適宜実行しなければならない。
     * ただし declare(ticks=1) しておけばある程度自動で呼ばれるようになる。
     *
     * ticks を利用しているのは mysqli/pgsql ともに「本当に同時」には投げられないため。
     * 結局のところ結果を受け取らない限りは次のクエリが自動実行されるようなことはなく、明示的に受け取りが必要。
     * それを ticks で代用しているに過ぎない。
     *
     * 返り値を引数なしで呼ぶと同期待ちして全て返す。
     * 引数にインデックスを与えるとそのクエリが完了するまで待ってそれを返す。
     *
     * 非常に実験的な機能で現実装は mysqli/pgsql のみの対応。
     *
     * @param array $queries 実行するクエリ配列
     * @param ?Connection $connection 実行コネクション
     * @return object|callable
     */
    public function executeAsync($queries, $connection = null)
    {
        if ($this->getUnsafeOption('dryrun')) {
            throw new \UnexpectedValueException("executeAsync is not supported dryrun");
        }

        if ($this->getUnsafeOption('preparing')) {
            throw new \UnexpectedValueException("executeAsync is not supported prepare");
        }

        if (is_array($queries)) {
            $queries = (function ($queries) {
                foreach ($queries as $query => $params) {
                    if ($filter_path = $this->getInjectCallStack()) {
                        $query = implode('', $this->_getCallStack($filter_path)) . $query;
                    }

                    if (array_depth($params, 2) === 1) {
                        $params = [$params];
                    }
                    foreach ($params as $param) {
                        yield $query => $param;
                    }
                }
            })($queries);
        }

        $cconnection = new CompatibleConnection($connection ?? $this->getConnection());
        return $cconnection->executeAsync($queries, $this->affectedRows);
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
            $entity = new $entityClass();
            $record = $entity->assign($record);
        }

        return $record;
    }

    /**
     * データ移行用 SQL を発行する
     *
     * 要するに与えられたファイル・配列を SQL 文に変換する。
     * csv,json,php などを手元で（マクロなどで） sql に変換する作業は多々あるが、それを簡易化できる。
     *
     * ただし、 dryrun: true のときのみであり false だと実際に実行され、affected rows を返す。
     * 特に change や delete を指定したときは致命的な事態になるので注意。
     *
     * $dml には下記が指定できる。
     *
     * - select:
     *   - dryrun なら主キーで WHERE して該当列を返すような SQL を返す
     *   - dryrun でないなら実行してレコード配列を返す（実質的に入力配列と（型以外は）ほぼ同じものを返す）
     * - insert:
     *   - dryrun なら実行されるであろう INSERT な SQL を返す
     *   - dryrun でないなら実行してレコード配列を INSERT する（追加のみで更新・削除は行われない）
     * - update:
     *   - dryrun なら実行されるであろう UPDATE な SQL を返す
     *   - dryrun でないなら実行してレコード配列に UPDATE する（更新のみで追加・削除は行われない）
     * - delete:
     *   - dryrun なら実行されるであろう DELETE な SQL を返す
     *   - dryrun でないなら実行してレコード配列を DELETE する（削除のみで追加・更新は行われない）
     * - modify:
     *   - dryrun なら実行されるであろう UPSERT(MODIFY) な SQL を返す
     *   - dryrun でないなら実行してレコード配列を MODIFY する（追加・更新のみで削除は行われない）
     * - change:
     *   - dryrun なら実行されるであろう INSERT/UPDATE/DELETE な SQL を返す
     *   - dryrun でないなら実行してレコード配列の状態に「持っていく」（追加・更新・削除が行われる）
     * - save:
     *   - dryrun なら実行されるであろう INSERT/UPDATE/DELETE な SQL を返す
     *   - dryrun でないなら実行してレコード配列の状態に「再帰的に持っていく」（再帰的に追加・更新・削除が行われる）
     *
     * どの方法を選んだとしてもレコード配列に主キーは必須となる（値は null でも構わない）。
     *
     * $opt で chunk や ignore オプションが指定できる。
     * 特に chunk/bulk は返り値の SQL が大幅に変化する。
     *
     * @param string $tableName テーブル名
     * @param string $dml 処理名
     * @param array|string $recordsOrFilename レコード配列かそれが書かれたファイル名
     * @param array $opt オプション引数
     * @return array|int sql 文。dryrun でないなら affected rows
     */
    public function migrate($tableName, $dml, $recordsOrFilename, $opt = [])
    {
        $opt += [
            'dryrun' => true,
            'ignore' => false,
            'bulk'   => false,
            'chunk'  => 0,
        ];

        $tableName = $this->convertTableName($tableName);

        $primary = $this->getSchema()->getTablePrimaryColumns($tableName);
        $columns = $this->getSchema()->getTableColumns($tableName);

        $records = is_iterable($recordsOrFilename) ? $recordsOrFilename : file_get_arrays($recordsOrFilename);
        foreach ($records as $n => $record) {
            foreach ($primary as $cname => $pk) {
                if (!array_key_exists($cname, $record)) {
                    throw new \RuntimeException("undefined primary key at $n:$cname");
                }
            }
        }

        // memo 重複コードが多いが、下手に共通化しないほうが良い（不具合が致命的な事態になるので）

        $results = [];
        switch (strtolower($dml)) {
            default:
                throw new \InvalidArgumentException("dml '$dml' is not supported.");
            case 'select':
                $keys = array_map(fn($record) => array_intersect_key($record, $primary), $records);
                if ($opt['bulk'] || !$opt['dryrun']) {
                    $cols = array_intersect_key($columns, array_add(...$records));
                    $select = $this->select([$tableName => array_keys($cols)], [$keys]);
                    if (!$opt['dryrun']) {
                        return $select->array();
                    }
                    $results[] = $select->queryInto();
                }
                else {
                    foreach ($keys as $n => $key) {
                        $results[] = $this->select([$tableName => array_keys(array_intersect_key($columns, $records[$n]))], $key)->queryInto();
                    }
                }
                break;
            case 'insert':
                $db = $opt['dryrun'] ? $this->dryrun() : $this;
                if ($opt['bulk']) {
                    $results[] = $db->insertArray($tableName, $records, $opt['chunk'], $opt);
                }
                else {
                    foreach ($records as $record) {
                        $results[] = $db->insert($tableName, $record, $opt);
                    }
                }
                break;
            case 'update':
                $db = $opt['dryrun'] ? $this->dryrun() : $this;
                if ($opt['bulk']) {
                    $results[] = $db->updateArray($tableName, $records, [], $opt['chunk'], $opt);
                }
                else {
                    $keys = array_map(fn($record) => array_intersect_key($record, $primary), $records);
                    foreach ($keys as $n => $key) {
                        $results[] = $db->update($tableName, array_diff_key($records[$n], $key), $key, $opt);
                    }
                }
                break;
            case 'delete':
                $db = $opt['dryrun'] ? $this->dryrun() : $this;
                $keys = array_map(fn($record) => array_intersect_key($record, $primary), $records);
                if ($opt['bulk']) {
                    $results[] = $db->delete($tableName, [$keys], $opt);
                }
                else {
                    foreach ($keys as $key) {
                        $results[] = $db->delete($tableName, $key, $opt);
                    }
                }
                break;
            case 'modify':
                $db = $opt['dryrun'] ? $this->dryrun() : $this;
                if ($opt['bulk']) {
                    $results[] = $db->modifyArray($tableName, $records, [], 'PRIMARY', $opt['chunk'], $opt);
                }
                else {
                    foreach ($records as $record) {
                        $results[] = $db->modify($tableName, $record, [], 'PRIMARY', $opt);
                    }
                }
                break;
            case 'change':
                $db = $opt['dryrun'] ? $this->dryrun() : $this;
                $result = $db->changeArray($tableName, $records, [], 'PRIMARY', [], $opt);
                if ($opt['dryrun']) {
                    $results[] = $result[1];
                }
                else {
                    $results[] = count($result);
                }
                break;
            case 'save':
                $db = $opt['dryrun'] ? $this->dryrun() : $this;
                $result = [];
                if ($opt['bulk']) {
                    $result[] = $db->save($tableName, $records, $opt);
                }
                else {
                    foreach ($records as $record) {
                        $result[] = $db->save($tableName, $record, $opt);
                    }
                }
                if ($opt['dryrun']) {
                    $results[] = $result;
                }
                else {
                    $results[] = array_count($result, fn($v) => is_array($v) && !is_indexarray($v), true);
                }
                break;
        }

        $result = array_flatten($results);
        if (!$opt['dryrun']) {
            return array_sum($result);
        }
        return $result;
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
        [$tableName, $column] = first_keyvalue($tableName);

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
            return $this->executeAffect(implode(" ", $sql));
        }
        else {
            $file = new \SplFileObject($filename);
            $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
            $file->setCsvControl($options['delimiter'], $options['enclosure'], $options['escape']);

            $columns = $column ?: array_keys(array_filter($this->getSchema()->getTableColumns($tableName), function (Column $column) {
                return !($column->getPlatformOptions()['virtual'] ?? false);
            }));
            $colnames = array_filter(array_keys(array_rekey($columns, function ($k, $v) { return is_int($k) ? $v : $k; })), 'strlen');
            $template = "INSERT INTO $tableName (%s) VALUES %s";

            $affected = [];
            $current = mb_internal_encoding();
            foreach (iterator_chunk($file, $options['chunk'] ?: PHP_INT_MAX, true) as $rows) {
                $values = $params = [];

                foreach ($rows as $m => $fields) {
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
                }

                $sql = sprintf($template, implode(', ', $colnames), implode(', ', $values));
                $affected[] = $this->executeAffect($sql, $params);
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
     * @used-by insertSelectIgnore()
     *
     * @param string $tableName テーブル名
     * @param string|QueryBuilder $sql SELECT クエリ
     * @param array $columns カラム定義
     * @param iterable $params bind パラメータ
     * @return int|string|Statement 基本的には affected row. dryrun 中は文字列、preparing 中は Statement
     */
    public function insertSelect($tableName, $sql, $columns = [], iterable $params = [])
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 5 ? func_get_arg(4) : [];

        $tableName = $this->convertTableName($tableName);

        $query = $this->_builderToSql($sql, $params);

        $ignore = array_get($opt, 'ignore') ? $this->getCompatiblePlatform()->getIgnoreSyntax() . ' ' : '';
        $sql = "INSERT {$ignore}INTO $tableName " . concat('(', implode(', ', $columns), ') ') . $query;

        return $this->executeAffect($sql, $params);
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
     * @used-by insertArrayIgnore()
     *
     * @param string $tableName テーブル名
     * @param array|callable|\Generator $data カラムデータ配列あるいは Generator
     * @param int $chunk 分割 insert する場合はそのチャンクサイズ
     * @return int|string|string[]|Statement 基本的には affected row. dryrun 中は文字列、preparing 中は Statement
     */
    public function insertArray($tableName, $data, $chunk = 0)
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 4 ? func_get_arg(3) : [];

        $tableName = $this->_preaffect($tableName, $data);
        if (is_array($tableName)) {
            [$tableName, $data] = first_keyvalue($tableName);
        }
        $tableName = $this->convertTableName($tableName);

        $columns = null;

        $affected = [];
        $ignore = array_get($opt, 'ignore') ? $this->getCompatiblePlatform()->getIgnoreSyntax() . ' ' : '';
        $template = "INSERT {$ignore}INTO $tableName (%s) VALUES %s";
        foreach (iterator_chunk($data, $chunk ?: PHP_INT_MAX) as $rows) {
            $values = [];
            $params = [];
            foreach ($rows as $row) {
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
            }

            $sql = sprintf($template, implode(', ', $columns), implode(', ', $values));
            $affected[] = $this->executeAffect($sql, $params);
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
     * @used-by updateArrayIgnore()
     *
     * @param string $tableName テーブル名
     * @param array|callable|\Generator $data カラムデータあるいは Generator あるいは Generator を返す callable
     * @param array|mixed $identifier 束縛条件
     * @param int $chunk 分割 update する場合はそのチャンクサイズ
     * @return int|string|string[]|Statement 基本的には affected row. dryrun 中は文字列、preparing 中は Statement
     */
    public function updateArray($tableName, $data, $identifier = [], $chunk = 0)
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 5 ? func_get_arg(4) : [];

        $tableName = $this->_preaffect($tableName, $data);
        if (is_array($tableName)) {
            [$tableName, $data] = first_keyvalue($tableName);
        }
        $tableName = $this->convertTableName($tableName);

        $pkey = $this->getSchema()->getTablePrimaryColumns($tableName);
        $pcols = array_keys($pkey);
        $ignore = array_get($opt, 'ignore') ? $this->getCompatiblePlatform()->getIgnoreSyntax() . ' ' : '';

        $condition = $this->_prewhere($tableName, $identifier);

        $affected = [];
        foreach (iterator_chunk($data, $chunk ?: PHP_INT_MAX) as $rows) {
            $columns = $this->_normalizes($tableName, $rows, $pcols);
            $pkcols = array_intersect_key($columns, $pkey);
            $cvcols = array_diff_key($columns, $pkey);

            $params = [];
            $set = $this->bindInto($cvcols, $params);
            $sets = array_sprintf($set, '%2$s = %1$s', ', ');

            $pkcond = $this->getCompatiblePlatform()->getPrimaryCondition(array_uncolumns($pkcols), $tableName);
            $criteria = $this->whereInto(array_merge($condition, [$pkcond]), $params);

            $affected[] = $this->executeAffect("UPDATE {$ignore}$tableName SET $sets" . ' WHERE ' . implode(' AND ', Adhoc::wrapParentheses($criteria)), $params);
        }

        if ($this->getUnsafeOption('dryrun') || $this->getUnsafeOption('preparing')) {
            return $chunk ? $affected : reset($affected);
        }
        return array_sum($affected);
    }

    /**
     * BULK UPSERT 構文
     *
     * 指定配列でバルクアップサートする。
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
     * @used-by modifyArrayIgnore()
     *
     * @param string $tableName テーブル名
     * @param array|callable|\Generator $insertData カラムデータあるいは Generator
     * @param array $updateData カラムデータあるいは Generator
     * @param string|int $uniquekey 重複チェックに使うユニークキー名
     * @param int $chunk 分割 insert する場合はそのチャンクサイズ
     * @return int|string|string[]|Statement 基本的には affected row. dryrun 中は文字列、preparing 中は Statement
     */
    public function modifyArray($tableName, $insertData, $updateData = [], $uniquekey = 'PRIMARY', $chunk = 0)
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 6 ? func_get_arg(5) : [];

        // for compatible
        if (ctype_digit("$uniquekey")) {
            $chunk = $uniquekey;
            $uniquekey = 'PRIMARY';
        }

        $cplatform = $this->getCompatiblePlatform();
        if (!$cplatform->supportsBulkMerge()) {
            throw new \DomainException($cplatform->getName() . ' is not support modifyArray.');
        }

        $tableName = $this->_preaffect($tableName, $insertData);
        if (is_array($tableName)) {
            [$tableName, $insertData] = first_keyvalue($tableName);
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

        $affected = [];
        $merge = $cplatform->getMergeSyntax(array_keys($this->getSchema()->getTableUniqueColumns($tableName, $uniquekey)));
        $refer = $cplatform->getReferenceSyntax('%1$s');
        $ignore = array_get($opt, 'ignore') ? $cplatform->getIgnoreSyntax() . ' ' : '';
        $template = "INSERT {$ignore}INTO $tableName (%s) VALUES %s $merge %s";
        foreach (iterator_chunk($insertData, $chunk ?: PHP_INT_MAX) as $rows) {
            $values = [];
            $params = [];
            foreach ($rows as $row) {
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
                    $updates = array_sprintf($columns, '%1$s = ' . $refer, ', ');
                }

                $values[] = '(' . implode(', ', $set) . ')';
            }

            $sql = sprintf($template, implode(', ', $columns), implode(', ', $values), $updates);
            $affected[] = $this->executeAffect($sql, array_merge($params, $updateParams));
        }

        if ($this->getUnsafeOption('dryrun') || $this->getUnsafeOption('preparing')) {
            return $chunk ? $affected : reset($affected);
        }

        if (!$cplatform->supportsIdentityAutoUpdate() && $this->getSchema()->getTableAutoIncrement($tableName) !== null) {
            $this->resetAutoIncrement($tableName, null);
        }

        return array_sum($affected);
    }

    /**
     * INSERT+UPDATE+DELETE を同時に行う
     *
     * テーブル状態を指定した配列・条件に「持っていく」メソッドとも言える。
     *
     * このメソッドは複数のステートメントが実行され、 prepare を使うことが出来ない。
     * また、可能な限りクエリを少なくかつ効率的に実行されるように構築されるので、テーブル定義や与えたデータによってはまったく構成の異なるクエリになる可能性がある（結果は同じになるが）。
     * 具体的には
     *
     * - BULK MERGE をサポートしていてカラムが完全に共通の場合：     modifyArray(単一) + delete 的な動作（最も高速）
     * - BULK MERGE をサポートしていてカラムがそれなりに共通の場合： modifyArray(複数) + delete 的な動作（比較的高速）
     * - merge をサポートしていてカラムが完全に共通の場合：          prepareModify(単一) + delete 的な動作（標準速度）
     * - merge をサポートしていてカラムがそれなりに共通の場合：      prepareModify(複数) + delete 的な動作（比較的低速）
     * - merge をサポートしていてカラムがバラバラだった場合：        各行 modify + delete 的な動作（最も低速）
     * - merge をサポートしていなくてカラムがバラバラだった場合：    各行 select + 各行 insert/update + delete 的な動作（最悪）
     *
     * という動作になる。
     *
     * 返り値は `[primaryKeys]` となり「その世界における主キー配列」を返す。
     *
     * ただし $tableName に配列を渡すと 主キーをキーとしたレコード配列を返すようになる。
     * レコード配列には空文字キーで下記の値が自動で設定される。
     * - 1: レコードが作成された
     * - -1: レコードが削除された
     * - 2: レコードが更新された
     * - 0: 更新対象だが更新されなかった（mysql のみ）
     * 返ってくるレコードのソースは下記のとおりである。
     * - INSERT: 作成した後のレコード（元がないのだから新しいレコードしか返し得ない）
     * - DELETE: 削除する前のレコード（削除したのだから元のレコードしか返し得ない）
     * - UPDATE: 更新する前のレコード（「更新する値」は手元にあるわけなので更新前の値の方が有用度が高いため）
     * 実用的には RETURNING に近く、変更のあった一覧を得ることができる。
     * ただし bulk は自動で無効になるので注意（上記で言うところの「標準速度」が最速になる）。
     *
     * dryrun 中は [[primaryKeys], [実行した SQL]] という2タプルの階層配列を返す。
     * この返り値は内部規定であり、この構造に依存したコードを書いてはならない。
     * また、primaryKeys は現在の状態に基づく値であり、dryrun で得られた key や sql を後で実行する場合は注意を払わなければならない。
     *
     * ```php
     * # `['category' => 'misc']` の世界でレコードが3行になる。指定した3行が無ければ作成され、有るなら更新され、id 指定している 1,2,3 以外のレコードは削除される
     * $db->changeArray('table_name', [
     *     ['id' => 1, 'name' => 'hoge'],
     *     ['id' => 2, 'name' => 'fuga'],
     *     ['id' => 3, 'name' => 'piyo'],
     * ], ['category' => 'misc']);
     * // BULK MERGE をサポートしている場合、下記がカラムの種類ごとに発行される
     * // INSERT INTO table_name (id, name) VALUES
     * //   ('1', 'hoge'),
     * //   ('2', 'fuga'),
     * //   ('3', 'piyo')
     * // ON DUPLICATE KEY UPDATE
     * //   id = VALUES(id),
     * //   name = VALUES(name)
     * // DELETE FROM table_name WHERE (category = 'misc') AND (NOT (id IN ('1', '2', '3')))
     * //
     * // merge をサポートしている場合、下記がカラムの種類ごとに発行される（merge は疑似クエリ）
     * // [prepare] INSERT INTO table_name (id, name) VALUES (?, ?) ON UPDATE id = VALUES(id), name = VALUES(name)
     * // [execute] INSERT INTO table_name (id, name) VALUES (1, 'hoge') ON UPDATE id = VALUES(id), name = VALUES(name)
     * // [execute] INSERT INTO table_name (id, name) VALUES (2, 'fuga') ON UPDATE id = VALUES(id), name = VALUES(name)
     * // [execute] INSERT INTO table_name (id, name) VALUES (3, 'piyo') ON UPDATE id = VALUES(id), name = VALUES(name)
     * // DELETE FROM table_name WHERE (category = 'misc') AND (NOT (id IN ('1', '2', '3')))
     * //
     * // merge をサポートしていない場合、全行分 select して行が無ければ insert, 行が有れば update する
     * // SELECT EXISTS (SELECT * FROM table_name WHERE id = '1')
     * // UPDATE table_name SET name = 'hoge' WHERE id = '1'
     * // SELECT EXISTS (SELECT * FROM table_name WHERE id = '2')
     * // UPDATE table_name SET name = 'fuga' WHERE id = '2'
     * // SELECT EXISTS (SELECT * FROM table_name WHERE id = '3')
     * // INSERT INTO table_name (id, name) VALUES ('3', 'piyo')
     * // DELETE FROM table_name WHERE (category = 'misc') AND (NOT (id IN ('1', '2', '3')))
     *
     * // $returning に配列を与えると RETURNING 的動作になる（この場合作用行の id, name を返す）
     * $result = $db->changeArray('table_name', [
     *     ['id' => 1,    'name' => 'changed'], // 更新
     *     ['id' => 2,    'name' => 'hoge'],    // 未更新
     *     ['id' => null, 'name' => 'fuga'],    // 作成
     * ], ['category' => 'misc'],               // category=misc の世界で他のものを削除
     * ['id', 'name']);                         // 返り値として id, name を返す
     * result: [
     *     ['id' => 1, 'name' => 'base', '' => 2],  // 更新された行は 2（このとき、 name は更新前の値であり 'changed' ではない）
     *     ['id' => 2, 'name' => 'hoge', '' => 0],  // 更新されてない行は 0（mysql のみ）
     *     ['id' => 5, 'name' => 'fuga', '' => 1],  // 作成された行は 1（このとき、主キーも設定されて返ってくる）
     *     ['id' => 8, 'name' => 'piyo', '' => -1], // 削除された行は -1
     * ]
     * ```
     *
     * @used-by changeArrayIgnore()
     *
     * @param string|array $tableName テーブル名
     * @param array $dataarray データ配列
     * @param array|mixed $identifier 束縛条件。 false を与えると DELETE 文自体を発行しない（速度向上と安全担保）
     * @param string|array|null $uniquekey 重複チェックに使うユニークキー名
     * @param ?array $returning 返り値の制御変数。配列を与えるとそのカラムの SELECT 結果を返す（null は主キーを表す）
     * @return array 基本的には主キー配列. dryrun 中は SQL をネストして返す
     */
    public function changeArray($tableName, $dataarray, $identifier, $uniquekey = 'PRIMARY', $returning = [])
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 6 ? func_get_arg(5) : [];
        unset($opt['primary']); // 自身で処理するので不要

        // for compatible
        if (!is_string($uniquekey)) {
            $returning = $uniquekey;
            $uniquekey = 'PRIMARY';
        }

        $dryrun = $this->getUnsafeOption('dryrun');
        $cplatform = $this->getCompatiblePlatform();

        $whereconds = arrayize($identifier);

        $tableName = $this->_preaffect($tableName, []);

        // for compatiblle
        if ($tableName instanceof QueryBuilder) {
            $returning = $tableName->getQueryPart('select');
            $tableName = first_key($tableName->getFromPart());
        }
        $tableName = $this->convertTableName($tableName);

        // 主キー情報を漁っておく
        $pcols = $this->getSchema()->getTableUniqueColumns($tableName, $uniquekey);
        $plist = array_keys($pcols);
        $autocolumn = null;
        if ($uniquekey === 'PRIMARY') {
            $autocolumn = optional($this->getSchema()->getTableAutoIncrement($tableName))->getName();
        }
        $pksep = $this->getPrimarySeparator();

        if ($dryrun) {
            $returning = [];
        }
        if ($returning === null) {
            $returning = $plist;
        }
        if ($returning) {
            $opt['bulk'] = false;
        }

        // modifyArray や prepareModify が使えるか
        $bulkable = array_get($opt, 'bulk', true) && $cplatform->supportsBulkMerge();
        $preparable = !$dryrun && array_get($opt, 'prepare', true) && $cplatform->supportsMerge() && !$this->isEmulationMode();

        // カラムの種類でグルーピングする
        $primaries = [];
        $col_group = [];
        foreach ($dataarray as $n => $row) {
            // prepare する可能性があるのでこの段階で normalize する必要がある
            // prepare しなかった場合に2回呼ばれることになって無駄だがそもそもバラバラのカラムで呼ぶことをあまり想定していない
            $row = $this->_normalize($tableName, $row);
            $primaries[$n] = array_intersect_key($row, $pcols);

            $cols = array_keys($row);
            $gid = implode('+', $cols);

            $col_group[$gid]['cols'] = $cols;

            // 主キーが含まれているならバルクできる
            if ($bulkable && ($autocolumn === null || isset($primaries[$n][$autocolumn])) && count($primaries[$n]) === count($pcols)) {
                $col_group[$gid]['bulks'][$n] = $row;
            }
            else {
                $col_group[$gid]['rows'][$n] = $row;
            }
        }

        // returning モード（更新や削除のためその世界を事前取得する）
        $oldrecords = [];
        if ($returning) {
            $pkcol = $cplatform->getConcatExpression(array_values(array_implode($plist, $this->quote($pksep))));
            if ($identifier !== false) {
                $oldrecords = $this->selectAssoc([$tableName => [self::AUTO_PRIMARY_KEY => $pkcol], '' => $returning], $whereconds);
            }
        }

        $sqls = [];
        $idmap = [];
        $inserteds = [];

        foreach ($col_group as $group) {
            if ($group['bulks'] ?? []) {
                $sqls[] = $this->modifyArray($tableName, $group['bulks'], [], $uniquekey, 0, $opt);
            }
            if ($group['rows'] ?? []) {
                // 2件以上じゃないとプリペアの旨味が少ない
                $stmt = null;
                if ($preparable && count($group['rows']) > 1) {
                    $stmt = $this->prepareModify($tableName, $group['cols'], [], $uniquekey, $opt);
                }
                foreach ($group['rows'] as $n => $row) {
                    if ($stmt) {
                        $affected = $sqls[$n] = $stmt->executeAffect($row);
                    }
                    else {
                        $affected = $sqls[$n] = $this->modify($tableName, $row, [], $uniquekey, $opt);
                    }

                    if ($autocolumn !== null && !isset($primaries[$n][$autocolumn])) {
                        $primaries[$n][$autocolumn] = $this->getLastInsertId($tableName, $autocolumn);
                    }

                    // returning モード
                    if ($returning) {
                        $pv = implode($pksep, $primaries[$n]);
                        $idmap[$pv] = [
                            'seq'      => $n,
                            'affected' => $affected,
                        ];

                        if (isset($oldrecords[$pv])) {
                            $oldrecords[$pv] += ['' => $affected ? 2 : 0];
                        }
                        else {
                            $inserteds[$pv] = $primaries[$n];
                        }
                    }
                }
            }
        }

        // 更新外を削除（$cond を queryInto してるのは誤差レベルではなく速度に差が出るから）
        if ($identifier !== false) {
            $cond = $cplatform->getPrimaryCondition($primaries, $tableName);
            $sqls[] = $this->delete($tableName, array_merge($whereconds, $primaries ? [$this->queryInto("NOT ($cond)", $cond->getParams())] : []));
        }

        if ($dryrun) {
            return [$primaries, array_flatten($sqls)];
        }

        // returning モード（完了後に再フェッチを行い比較）
        if ($returning) {
            // 省略時の主キーだけであれば手元の情報で事足りるので漁る必要はない
            if ($returning === $plist) {
                $newrecords = $inserteds;
            }
            else {
                $cond = $cplatform->getPrimaryCondition($inserteds, $tableName);
                $newrecords = $this->selectAssoc([$tableName => [self::AUTO_PRIMARY_KEY => $pkcol], '' => $returning], [
                    [
                        $whereconds,
                        $inserteds ? [$this->queryInto("$cond", $cond->getParams())] : [],
                    ],
                ]);
            }
            foreach (array_diff_key($newrecords, $oldrecords) as $pv => $row) {
                $oldrecords[$pv] = $row + ['' => $idmap[$pv]['affected']];
            }
            foreach (array_diff_key($oldrecords, $newrecords) as $pv => $row) {
                $oldrecords[$pv] += ['' => -1];
            }

            $result = [];
            foreach ($oldrecords as $pv => $oldrecord) {
                if (isset($idmap[$pv])) {
                    $result[$idmap[$pv]['seq']] = $oldrecord;
                }
            }
            foreach ($oldrecords as $pv => $oldrecord) {
                if (!isset($idmap[$pv])) {
                    $result[] = $oldrecord;
                }
            }
            return array_map(fn($row) => array_remove($row, [self::AUTO_PRIMARY_KEY]), $result);
        }
        return $primaries;
    }

    /**
     * 自身 modify + 子テーブルの changeArray を行う
     *
     * フレームワークや ORM における save に近い。
     * 自身に関しては単純に「有ったら update/無かったら insert」を行い、キーが子テーブルである場合はさらにそれで changeArray する。
     * changeArray なので自身以外の興味の範囲外のレコードは消え去る。
     * つまりいわゆる「関連テーブルも含めた保存」になる。
     *
     * ネスト可能。「親に紐づく子に紐づく孫レコード」も再帰的に処理される。
     *
     * ```php
     * # 親 -> 子 -> 孫を処理
     * $db->save('t_parent', [
     *     'parent_name' => 'parent1',
     *     't_child'     => [
     *         [
     *             'child_name' => 'child11',
     *             't_grand'    => [
     *                 [
     *                     'grand_name' => 'grand111',
     *                 ],
     *                 [
     *                     'grand_name' => 'grand112',
     *                 ],
     *             ],
     *         ],
     *         [
     *             'child_name' => 'child12',
     *             't_grand'    => [
     *                 [
     *                     'grand_name' => 'grand121',
     *                 ],
     *                 [
     *                     'grand_name' => 'grand122',
     *                 ],
     *             ],
     *         ],
     *     ],
     * ]);
     *
     * # まずはメインである親が modify される
     * // INSERT INTO
     * //   t_parent(parent_id, parent_name)
     * // VALUES
     * //   (1, 'parent1')
     * // ON DUPLICATE KEY UPDATE
     * //   parent_id = VALUES(parent_id),
     * //   parent_name = VALUES(parent_name)
     *
     * # 次に子が changeArray される
     * // INSERT INTO
     * //   t_child(child_id, child_name, parent_id)
     * // VALUES
     * //   (1, 'child11', 60),
     * //   (2, 'child12', 60)
     * // ON DUPLICATE KEY UPDATE
     * //   child_id = VALUES(child_id),
     * //   child_name = VALUES(child_name),
     * //   parent_id = VALUES(parent_id)
     * // changeArray で興味のない行は吹き飛ぶ
     * // DELETE FROM t_child WHERE (t_child.parent_id IN(1)) AND (NOT(t_child.child_id IN(1, 2)))
     *
     * # 最後に孫が changeArray される
     * // INSERT INTO
     * //   t_grand(grand_id, grand_name, child_id)
     * // VALUES
     * //   (1, 'grand111', 1),
     * //   (2, 'grand112', 1),
     * //   (3, 'grand121', 2),
     * //   (4, 'grand122', 2)
     * // ON DUPLICATE KEY UPDATE
     * //   grand_id = VALUES(grand_id),
     * //   grand_name = VALUES(grand_name),
     * //   child_id = VALUES(child_id)
     * // changeArray で興味のない行は吹き飛ぶ
     * // DELETE FROM t_grand WHERE (t_grand.child_id IN(1, 2)) AND (NOT(t_grand.grand_id IN(1, 2, 3, 4)))
     * ```
     *
     * @used-by saveIgnore()
     *
     * @param string|array $tableName テーブル名
     * @param array $data 階層を持ったデータ配列
     * @return array|string[] 基本的には階層を持った主キー配列. dryrun 中は文字列配列
     */
    public function save($tableName, $data)
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 3 ? func_get_arg(2) : [];

        $TABLE_SEPARATOR = '/';
        $INDEX_SEPARATOR = '#';
        $SEPARATOR_REGEX = "#[" . preg_quote("{$TABLE_SEPARATOR}{$INDEX_SEPARATOR}", '#') . "]#";

        $tableName = $this->convertTableName($tableName);
        $schema = $this->getSchema();

        $single_mode = !is_indexarray($data);
        if ($single_mode) {
            $data = [$data];
        }

        // ツリー構造を、キーに親情報を持ったフラット構造に変換する
        $flatten = function ($tname, $rows, $key, &$dataarray) use (&$flatten, $schema, $INDEX_SEPARATOR, $TABLE_SEPARATOR) {
            assert(is_indexarray($rows), "$tname rows is not index array");
            $tname = $this->convertTableName($tname);
            $key .= ($key ? $TABLE_SEPARATOR : '') . $tname . $INDEX_SEPARATOR;
            foreach ($rows as $n => $row) {
                // 出現順もそれなりに大事なのでまず子供行を抽出してから親行の処理をする
                $children = array_filter($row, function ($crow, $col) use ($tname, $schema) {
                    return is_array($crow) && $schema->getForeignColumns($tname, $this->convertTableName($col));
                }, ARRAY_FILTER_USE_BOTH);

                // 親を処理してから抽出しておいた子行で再帰
                $id = $key . $n;
                $dataarray[$tname][$id] = array_diff_key($row, $children);
                foreach ($children as $c => $v) {
                    $flatten($c, $v, $id, $dataarray);
                }
            }
        };
        $flatten($tableName, $data, null, $dataarray);

        $dryrun = $this->getUnsafeOption('dryrun');
        $sqls = [];

        // フラット構造になったので縦断的に changeArray ができ、キーに親情報があるので復元・主キー設定もできる
        $primaries = [];
        $result = [];
        foreach ($dataarray as $tname => $rows) {
            // 子行に親の主キー（外部キー）を設定する。そのキーは後で削除に使うため別変数に溜めておく
            $parents = [];
            foreach ($rows as $id => $row) {
                $key = '';
                foreach (explode($TABLE_SEPARATOR, $id) as $keyn) {
                    $key .= $keyn;
                    [$cname,] = explode($INDEX_SEPARATOR, $keyn);
                    foreach ($schema->getForeignColumns($cname, $tname) as $ck => $pk) {
                        if (isset($primaries[$cname][$key][$pk])) {
                            $rows[$id][$ck] = $parents["$tname.$ck"][$key] = $primaries[$cname][$key][$pk];
                        }
                    }
                    $key .= $TABLE_SEPARATOR;
                }
            }

            // changeArray すれば主キーが得られる。主キーが得られれば外部キーに設定できるし返り値用に整形できる
            $changed = $this->changeArray($tname, $rows, $parents ?: false, 'PRIMARY', [], $opt);
            if ($dryrun) {
                $primaries[$tname] = $changed[0];
                $sqls = array_merge($sqls, $changed[1]);
            }
            else {
                $primaries[$tname] = $changed;
                foreach ($primaries[$tname] as $id => $pkval) {
                    array_put($result, $pkval, preg_split($SEPARATOR_REGEX, $id));
                }
            }
        }
        if ($dryrun) {
            return $sqls;
        }

        return $single_mode ? $result[$tableName][0] : $result[$tableName];
    }

    /**
     * insertOrThrow のエイリアス
     *
     * updateOrThrow や deleteOrThrow を使う機会はそう多くなく、実質的に主キーを得たいがために insertOrThrow を使うことが多い。
     * となると対称性がなく、コードリーディング時に余計な思考を挟むことが多い（「なぜ insert だけ OrThrow なんだろう？」）のでエイリアスを用意した。
     *
     * @used-by createIgnore()
     * @used-by createConditionally()
     *
     * @param string|array $tableName テーブル名
     * @param mixed $data INSERT データ配列
     * @return string|array|Statement 基本的には主キー. dryrun 中は文字列、preparing 中は Statement
     */
    public function create($tableName, $data)
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 3 ? func_get_arg(2) : [];
        $opt['throw'] = true;
        $opt['primary'] = $opt['primary'] ?? 1;

        return $this->insert($tableName, $data, $opt);
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
            $data += $tableName->getColval();
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
            [$tableName, $data] = first_keyvalue($tableName);
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
        elseif (count($data) && $cplatform->supportsInsertSet() && $this->getUnsafeOption('insertSet')) {
            $sql .= "SET " . array_sprintf($set, '%2$s = %1$s', ', ');
        }
        else {
            $sql .= sprintf("(%s) VALUES (%s)", implode(', ', array_keys($set)), implode(', ', $set));
        }
        $affected = $this->executeAffect($sql, $params);
        if (!is_int($affected)) {
            return $affected;
        }

        if ($affected === 0 && array_get($opt, 'primary') === 2) {
            return [];
        }
        if ($affected !== 0 && array_get($opt, 'primary')) {
            return $this->_postaffect($tableName, $data);
        }
        if ($affected === 0 && array_get($opt, 'throw')) {
            throw new NonAffectedException('affected row is nothing.');
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
            $tableName->set($data + $tableName->getColval())->andWhere($identifier);
            return $this->executeAffect($this->getCompatiblePlatform()->convertUpdateQuery($tableName), $tableName->getParams());
        }
        if (is_array($tableName)) {
            [$tableName, $data] = first_keyvalue($tableName);
        }

        $tableName = $this->convertTableName($tableName);

        $data = $this->_normalize($tableName, $data);
        if (!count($data) && $this->getUnsafeOption('updateEmpty')) {
            foreach ($this->getSchema()->getTablePrimaryColumns($tableName) as $pk => $column) {
                $data[$pk] = $this->raw($pk);
            }
        }

        $params = [];
        $set = $this->bindInto($data, $params);
        $sets = array_sprintf($set, '%2$s = %1$s', ', ');
        $criteria = $this->whereInto($this->_prewhere($tableName, $identifier), $params);

        $ignore = array_get($opt, 'ignore') ? $this->getCompatiblePlatform()->getIgnoreSyntax() . ' ' : '';
        $affected = $this->executeAffect("UPDATE {$ignore}$tableName SET $sets" . ($criteria ? ' WHERE ' . implode(' AND ', Adhoc::wrapParentheses($criteria)) : ''), $params);
        if (!is_int($affected)) {
            return $affected;
        }

        if ($affected !== 0 && array_get($opt, 'primary')) {
            return $this->_postaffect($tableName, $data + arrayize($identifier));
        }
        if ($affected === 0 && array_get($opt, 'primary') === 2) {
            return [];
        }
        if ($affected === 0 && array_get($opt, 'throw')) {
            throw new NonAffectedException('affected row is nothing.');
        }
        return $affected;
    }

    /**
     * DELETE 構文
     *
     * 仮想外部キーの CASCADE や SET NULL の実行も行われる（1階層のみ）。
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
            return $this->executeAffect($this->getCompatiblePlatform()->convertDeleteQuery($tableName, []), $tableName->getParams());
        }

        $tableName = $this->convertTableName($tableName);
        $prewhere = $this->_prewhere($tableName, $identifier);

        $schema = $this->getSchema();
        $cascades = [];
        $setnulls = [];
        $sets = [];
        $fkeys = $schema->getForeignKeys($tableName, null);
        foreach ($fkeys as $fkey) {
            // 仮想でない通常の外部キーであれば RDBMS 側で削除・更新してくれるが、仮想外部キーは能動的に実行する必要がある
            if (@$fkey->getOption('virtual') === true) {
                $onDelete = strtoupper($fkey->onDelete());
                if ($onDelete === 'CASCADE') {
                    $ltable = first_key($schema->getForeignTable($fkey));
                    $cascades[$ltable] = "<$ltable:{$fkey->getName()}";
                }
                if ($onDelete === 'SET NULL') {
                    $ltable = first_key($schema->getForeignTable($fkey));
                    $setnulls[$ltable] = "<$ltable:{$fkey->getName()}";
                    $sets = array_strpad(array_fill_keys($fkey->getLocalColumns(), null), "$ltable.");
                }
            }
        }

        $affecteds = [];
        if ($cascades) {
            $select = $this->select([$tableName => array_values($cascades)])->where($prewhere);
            $affecteds[] = $this->executeAffect($this->getCompatiblePlatform()->convertDeleteQuery($select, array_keys($cascades)), $select->getParams());
        }
        if ($setnulls) {
            $select = $this->select([$tableName => array_values($setnulls)])->where($prewhere)->set($sets);
            $affecteds[] = $this->executeAffect($this->getCompatiblePlatform()->convertUpdateQuery($select), $select->getParams());
        }

        $params = [];
        $criteria = $this->whereInto($prewhere, $params);

        $ignore = array_get($opt, 'ignore') ? $this->getCompatiblePlatform()->getIgnoreSyntax() . ' ' : '';
        $affected = $this->executeAffect("DELETE {$ignore}FROM $tableName" . ($criteria ? ' WHERE ' . implode(' AND ', Adhoc::wrapParentheses($criteria)) : ''), $params);
        if (!is_int($affected)) {
            if ($affecteds) {
                return array_merge($affecteds, (array) $affected);
            }
            return $affected;
        }

        if ($affected !== 0 && array_get($opt, 'primary')) {
            return $this->_postaffect($tableName, arrayize($identifier));
        }
        if ($affected === 0 && array_get($opt, 'primary') === 2) {
            return [];
        }
        if ($affected === 0 && array_get($opt, 'throw')) {
            throw new NonAffectedException('affected row is nothing.');
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

        $schema = $this->getSchema();
        $fkeys = $schema->getForeignKeys($tableName, null);
        foreach ($fkeys as $fkey) {
            if ($fkey->onDelete() === null) {
                $ltable = first_key($schema->getForeignTable($fkey));
                $notexists = $this->select($ltable);
                $notexists->setSubwhere($tableName, $aliasName, $fkey->getName());
                $identifier[] = $notexists->notExists();
            }
        }

        return $this->delete($originalBuilder ?? $tableName, $identifier, $opt);
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

        $tableName = $this->convertTableName($tableName);
        $identifier = $this->_prewhere($tableName, $identifier);

        $affecteds = [];
        $schema = $this->getSchema();
        $fkeys = $schema->getForeignKeys($tableName, null);
        foreach ($fkeys as $fkey) {
            if ($fkey->onDelete() === null) {
                $ltable = first_key($schema->getForeignTable($fkey));
                $pselect = $this->select([$tableName => $fkey->getForeignColumns()], $identifier);
                $subwhere = [];
                if (array_get($opt, 'in')) {
                    $pvals = $pvals ?? $pselect->array();
                    $pvals2 = array_rmap($pvals, 'array_combine', $fkey->getLocalColumns());
                    $pcond = $this->getCompatiblePlatform()->getPrimaryCondition($pvals2, $ltable);
                    $subwhere[] = $this->queryInto($pcond) ?: 'FALSE';
                }
                else {
                    $ckey = implode(',', $fkey->getLocalColumns());
                    $subwhere["($ckey)"] = $pselect;
                }
                $affecteds = array_merge($affecteds, (array) $this->destroy($ltable, $subwhere, array_pickup($opt, ['in', 'ignore'])));
            }
        }

        $affected = $this->delete($tableName, $identifier, $opt);
        if ($this->getUnsafeOption('dryrun')) {
            return array_merge($affecteds, (array) $affected);
        }
        if (is_int($affected)) {
            return array_sum([...$affecteds, $affected]);
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
     * @param ?int $limit 残す件数
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
        if ($limit < 0) {
            throw new \InvalidArgumentException("\$limit must be >= 0 ($limit).");
        }

        $orderBy = array_kmap($orderBy, function ($v, $k) use ($simplize) {
            if (is_int($k)) {
                if (is_array($v)) {
                    return ($v[1] ? '+' : '-') . $simplize($v[0]);
                }
                return $simplize($v);
            }
            return ($v ? '+' : '-') . $simplize($k);
        });
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
        $glsign = ($ascdesc ? '>=' : '<=');
        $orderBy = ltrim($orderBy, '-+');

        // 境界値が得られるサブクエリ
        $subquery = $this->select(["$tableName $VALUETABLE" => $orderBy])
            ->where($identifier)
            ->andWhere(array_map(function ($gk) use ($GROUPTABLE, $VALUETABLE) { return "$GROUPTABLE.$gk = $VALUETABLE.$gk"; }, $groupBy))
            ->orderBy($groupBy + [$orderBy => $ascdesc])
            ->limit(1, $limit);

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
     * @return int|string|array 基本的には affected row. dryrun 中は文字列
     */
    public function upsert($tableName, $insertData, $updateData = [])
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 4 ? func_get_arg(3) : [];

        $tableName = $this->_preaffect($tableName, $insertData);

        if ($tableName instanceof QueryBuilder) {
            throw new \InvalidArgumentException('upsert is not supported QueryBuilder.');
        }
        if (is_array($tableName)) {
            [$tableName, $insertData] = first_keyvalue($tableName);
            if ($updateData && !is_hasharray($updateData)) {
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
            if (!$updateData) {
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
     * - sqlite：     INSERT ～ ON CONFLICT(...) DO UPDATE が実行される
     * - mysql：      INSERT ～ ON DUPLICATE KEY が実行される
     * - postgresql： INSERT ～ ON CONFLICT(...) DO UPDATE が実行される
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
     * @param string|array $tableName テーブル名
     * @param mixed $insertData INSERT データ配列
     * @param mixed $updateData UPDATE データ配列
     * @param string $uniquekey 重複チェックに使うユニークキー名
     * @return int|string|array|Statement 基本的には affected row. dryrun 中は文字列、preparing 中は Statement
     */
    public function modify($tableName, $insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 5 ? func_get_arg(4) : [];

        if (!$this->getCompatiblePlatform()->supportsMerge()) {
            return $this->upsert($tableName, $insertData, $updateData ?: null, $opt);
        }

        $tableName = $this->_preaffect($tableName, $insertData);

        if ($tableName instanceof QueryBuilder) {
            throw new \InvalidArgumentException('upsert is not supported QueryBuilder.');
        }
        if (is_array($tableName)) {
            [$tableName, $insertData] = first_keyvalue($tableName);
            if ($updateData && !is_hasharray($updateData)) {
                $updateData = array_combine(array_keys($insertData), $updateData);
            }
        }
        $updatable = !!$updateData;

        $tableName = $this->convertTableName($tableName);

        $insertData = $this->_normalize($tableName, $insertData);
        $updateData = $this->_normalize($tableName, $updateData);
        $updateData = $this->getCompatiblePlatform()->convertMergeData($insertData, $updateData);

        $schema = $this->getSchema();
        $pkcols = $schema->getTableUniqueColumns($tableName, $uniquekey);

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

        $cplatform = $this->getCompatiblePlatform();
        $ignore = array_get($opt, 'ignore') ? $cplatform->getIgnoreSyntax() . ' ' : '';
        $sql = "INSERT {$ignore}INTO $tableName ";
        if ($condition !== null) {
            $fromDual = concat(' FROM ', $cplatform->getDualTable());
            $sql .= sprintf("(%s) SELECT %s$fromDual WHERE $condition", implode(', ', array_keys($sets1)), implode(', ', $sets1));
        }
        elseif (count($insertData) && $cplatform->supportsInsertSet() && $this->getUnsafeOption('insertSet')) {
            $sql .= "SET " . array_sprintf($sets1, '%2$s = %1$s', ', ');
        }
        else {
            $sql .= sprintf("(%s) VALUES (%s)", implode(', ', array_keys($sets1)), implode(', ', $sets1));
        }
        $sql .= ' ' . $cplatform->getMergeSyntax(array_keys($pkcols)) . ' ' . array_sprintf($sets2, '%2$s = %1$s', ', ');
        $affected = $this->executeAffect($sql, $params);
        if (!is_int($affected)) {
            return $affected;
        }

        if (!$cplatform->supportsIdentityAutoUpdate() && $this->getSchema()->getTableAutoIncrement($tableName) !== null) {
            $this->resetAutoIncrement($tableName, null);
        }

        if ($affected !== 0 && array_get($opt, 'primary')) {
            return $this->_postaffect($tableName, $updatable ? $updateData : $insertData);
        }
        if ($affected === 0 && array_get($opt, 'primary') === 2) {
            return [];
        }
        if ($affected === 0 && array_get($opt, 'throw')) {
            throw new NonAffectedException('affected row is nothing.');
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
     * @return int|string|array|Statement 基本的には affected row. dryrun 中は文字列、preparing 中は Statement
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
        $columns = array_filter($this->getSchema()->getTableColumns($tableName), function (Column $column) {
            return !($column->getPlatformOptions()['virtual'] ?? false);
        });

        $selects = [];
        foreach ($columns as $cname => $column) {
            $selects[$cname] = array_get($sets, $cname, $cname);
        }

        $criteria = $this->whereInto(array_intersect_key($data, $primary), $params);

        $sql = "REPLACE INTO $tableName (" . implode(', ', array_keys($selects)) . ") ";
        $sql .= "SELECT " . implode(', ', $selects) . " FROM (SELECT NULL) __T ";
        $sql .= "LEFT JOIN $tableName ON " . ($criteria ? implode(' AND ', Adhoc::wrapParentheses($criteria)) : '1=0');

        $affected = $this->executeAffect($sql, $params);
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
     * @param ?string $sourceTable 元となるテーブル名。省略すると $targetTable と同じになる
     * @return int|string|Statement 基本的には affected row. dryrun 中は文字列、preparing 中は Statement
     */
    public function duplicate($targetTable, array $overrideData = [], $where = [], $sourceTable = null)
    {
        $sourceTable = $sourceTable === null ? $targetTable : $sourceTable;
        $targetTable = $this->convertTableName($targetTable);
        $sourceTable = $this->convertTableName($sourceTable);

        $metatarget = array_filter($this->getSchema()->getTableColumns($targetTable), function (Column $column) {
            return !($column->getPlatformOptions()['virtual'] ?? false);
        });
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

        return $this->executeAffect($sql, array_merge($params, $select->getParams()));
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
     * @return int|string|Statement 基本的には affected row. dryrun 中は文字列、preparing 中は Statement
     */
    public function truncate($tableName, $cascade = false)
    {
        $tableName = $this->convertTableName($tableName);
        $sql = $this->getCompatiblePlatform()->getTruncateTableSQL($tableName, $cascade);
        $affected = $this->executeAffect($sql);
        if (!$this->getCompatiblePlatform()->supportsResetAutoIncrementOnTruncate() && $this->getSchema()->getTableAutoIncrement($tableName)) {
            $this->resetAutoIncrement($tableName);
        }
        return $affected;
    }

    /**
     * 最後に挿入した ID を返す
     *
     * @param ?string $tableName テーブル名。PostgreSql の場合のみ有効
     * @param ?string $columnName カラム名。PostgreSql の場合のみ有効
     * @return null|string 最後に挿入した ID. dryrun 中は max+1 から始まる連番を返す
     */
    public function getLastInsertId($tableName = null, $columnName = null)
    {
        if ($this->getUnsafeOption('dryrun')) {
            $key = "$tableName.$columnName";
            $this->lastInsertIds[$key] = ($this->lastInsertIds[$key] ?? $this->max([$tableName => $columnName])) + 1;
            return $this->lastInsertIds[$key];
        }
        return $this->getMasterConnection()->lastInsertId($this->getCompatiblePlatform()->getIdentitySequenceName($tableName, $columnName));
    }

    /**
     * 自動採番列をリセットする
     *
     * @param string $tableName テーブル名
     * @param ?int $seq 採番列の値（NULL を与えると最大値+1になる）
     */
    public function resetAutoIncrement($tableName, $seq = 1)
    {
        $autocolumn = $this->getSchema()->getTableAutoIncrement($tableName);
        if ($autocolumn === null) {
            throw new \UnexpectedValueException("table '$tableName' is not auto incremental.");
        }

        if ($seq === null) {
            $seq = $this->max([$tableName => $autocolumn->getName()]) + 1;
        }

        $queries = $this->getCompatiblePlatform()->getResetSequenceExpression($tableName, $autocolumn->getName(), $seq);
        foreach ($queries as $query) {
            $this->executeAffect($query);
        }
    }

    /**
     * 最後に更新した行数を返す
     *
     * 実行していない or 直前のクエリが失敗していた場合は null を返す。
     *
     * @return int|null 更新した行数
     */
    public function getAffectedRows()
    {
        return $this->affectedRows;
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
            $result = $this->getUnsafeOption('yamlParser')($yaml);
            if (is_string($result)) {
                throw new \InvalidArgumentException("syntax error ($yaml)");
            }
            $cache[$yaml] = $result;
        }
        return $cache[$yaml];
    }

    /**
     * 初期状態に戻す
     *
     * このメソッドはテスト用なので運用コードで決して呼んではならない。
     *
     * @internal
     * @ignore
     * @codeCoverageIgnore
     *
     * @return $this
     */
    public function refresh()
    {
        $this->getUnsafeOption('cacheProvider')->clear();
        $this->cache = new \ArrayObject();
        $this->txConnection = $this->getMasterConnection();
        $this->affectedRows = null;
        $this->lastInsertIds = [];
        return $this->unstackAll();
    }
}
