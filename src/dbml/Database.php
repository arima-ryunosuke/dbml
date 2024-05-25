<?php

namespace ryunosuke\dbml;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use ryunosuke\dbml\Driver\ArrayResult;
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
use ryunosuke\dbml\Mixin\AffectAndPrimaryTrait;
use ryunosuke\dbml\Mixin\AffectIgnoreTrait;
use ryunosuke\dbml\Mixin\AffectOrThrowTrait;
use ryunosuke\dbml\Mixin\AggregateTrait;
use ryunosuke\dbml\Mixin\EntityForAffectTrait;
use ryunosuke\dbml\Mixin\EntityForUpdateTrait;
use ryunosuke\dbml\Mixin\EntityInShareTrait;
use ryunosuke\dbml\Mixin\EntityMethodTrait;
use ryunosuke\dbml\Mixin\EntityOrThrowTrait;
use ryunosuke\dbml\Mixin\ExportTrait;
use ryunosuke\dbml\Mixin\FetchMethodTrait;
use ryunosuke\dbml\Mixin\FetchOrThrowTrait;
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
use ryunosuke\dbml\Query\AffectBuilder;
use ryunosuke\dbml\Query\Clause\Where;
use ryunosuke\dbml\Query\Expression\Expression;
use ryunosuke\dbml\Query\Parser;
use ryunosuke\dbml\Query\Queryable;
use ryunosuke\dbml\Query\SelectBuilder;
use ryunosuke\dbml\Query\Statement;
use ryunosuke\dbml\Query\TableDescriptor;
use ryunosuke\dbml\Transaction\Transaction;
use ryunosuke\dbml\Utility\Adhoc;
use ryunosuke\utility\attribute\Attribute\DebugInfo;
use ryunosuke\utility\attribute\ClassTrait\DebugInfoTrait;

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
 * **AndPrimary**
 *
 * 通常の更新系メソッドに付与できる。
 * 返り値として主キー配列を返すようになる。
 * OrThrow と異なり作用行は見ないで常に主キーを返すため、「とりあえずザクザクと行を追加したい」のようなテストケースの作成などで有用。
 *
 * **Ignore**
 *
 * [insert, updert, modify, delete, invalid, revise, upgrade, remove, destroy] メソッドのみに付与できる。
 * RDBMS に動作は異なるが、 `INSERT IGNORE` のようなクエリが発行される。
 *
 * ### エスケープ
 *
 * 識別子のエスケープは一切面倒をみない。外部入力を識別子にしたい（テーブル・カラムを外部指定するとか）場合は自前でエスケープすること。
 * 値のエスケープに関しては基本的には安全側に倒しているが、 {@link Expression} を使用する場合はその前提が崩れる事がある（ `()` を含むエントリは自動で Expression 化されるので同じ）。
 * 原則的に外部入力を Expression 化したり、値以外の入力として使用するのは全く推奨できない。
 *
 * @method CacheInterface         getCacheProvider()
 * @method bool                   getAutoIdentityInsert()
 * @method $this                  setAutoIdentityInsert($bool)
 * @method int|string             getDefaultChunk()
 * @method $this                  setDefaultChunk($int)
 * @method int                    getDefaultRetry()
 * @method $this                  setDefaultRetry($int)
 * @method array                  getAutoCastType()
 * @nethod self                   setAutoCastType($array) 実際に定義している
 * @method bool                   getMasterMode()
 * @method $this                  setMasterMode($bool)
 * @method string                 getCheckSameKey()
 * @method $this                  setCheckSameKey($string)
 * @method string                 getCheckSameColumn()
 * @method $this                  setCheckSameColumn($string)
 * @method array                  getExportClass()
 * @method $this                  setExportClass($array)
 *
 * @method string                 getDefaultIteration() {{@link TableGateway::getDefaultIteration()} 参照@inheritdoc TableGateway::getDefaultIteration()}
 * @method $this                  setDefaultIteration($iterationMode) {{@link TableGateway::setDefaultIteration()} 参照@inheritdoc TableGateway::setDefaultIteration()}
 * @method string                 getDefaultJoinMethod() {{@link TableGateway::getDefaultJoinMethod()} 参照@inheritdoc TableGateway::getDefaultJoinMethod()}
 * @method $this                  setDefaultJoinMethod($string) {{@link TableGateway::setDefaultJoinMethod()} 参照@inheritdoc TableGateway::setDefaultJoinMethod()}
 *
 * @method mixed                  getDefaultOrder() {{@link SelectBuilder::getDefaultOrder()} 参照@inheritdoc SelectBuilder::getDefaultOrder()}
 * @method $this                  setDefaultOrder($mixed) {{@link SelectBuilder::setDefaultOrder()} 参照@inheritdoc SelectBuilder::setDefaultOrder()}
 * @method string                 getPrimarySeparator() {{@link SelectBuilder::getPrimarySeparator()} 参照@inheritdoc SelectBuilder::getPrimarySeparator()}
 * @method $this                  setPrimarySeparator($string) {{@link SelectBuilder::setPrimarySeparator()} 参照@inheritdoc SelectBuilder::setPrimarySeparator()}
 * @method string                 getAggregationDelimiter() {{@link SelectBuilder::getAggregationDelimiter()} 参照@inheritdoc SelectBuilder::getAggregationDelimiter()}
 * @method $this                  setAggregationDelimiter($string) {{@link SelectBuilder::setAggregationDelimiter()} 参照@inheritdoc SelectBuilder::setAggregationDelimiter()}
 * @method bool                   getPropagateLockMode() {{@link SelectBuilder::getPropagateLockMode()} 参照@inheritdoc SelectBuilder::getPropagateLockMode()}
 * @method $this                  setPropagateLockMode($bool) {{@link SelectBuilder::setPropagateLockMode()} 参照@inheritdoc SelectBuilder::setPropagateLockMode()}
 * @method bool                   getInjectChildColumn() {{@link SelectBuilder::getInjectChildColumn()} 参照@inheritdoc SelectBuilder::getInjectChildColumn()}
 * @method $this                  setInjectChildColumn($bool) {{@link SelectBuilder::setInjectChildColumn()} 参照@inheritdoc SelectBuilder::setInjectChildColumn()}
 * @method bool                   getInsertSet() {{@link AffectBuilder::getInsertSet()} 参照@inheritdoc AffectBuilder::getInsertSet()}
 * @method $this                  setInsertSet($bool) {{@link AffectBuilder::setInsertSet()} 参照@inheritdoc AffectBuilder::setInsertSet()}
 * @method bool                   getUpdateEmpty() {{@link AffectBuilder::getUpdateEmpty()} 参照@inheritdoc AffectBuilder::getUpdateEmpty()}
 * @method $this                  setUpdateEmpty($bool) {{@link AffectBuilder::setUpdateEmpty()} 参照@inheritdoc AffectBuilder::setUpdateEmpty()}
 * @method array                  getDefaultInvalidColumn() {{@link AffectBuilder::getDefaultInvalidColumn()} 参照@inheritdoc AffectBuilder::getDefaultInvalidColumn()}
 * @method $this                  setDefaultInvalidColumn($array) {{@link AffectBuilder::setDefaultInvalidColumn()} 参照@inheritdoc AffectBuilder::setDefaultInvalidColumn()}
 * @method bool                   getFilterNoExistsColumn() {{@link AffectBuilder::getFilterNoExistsColumn()} 参照@inheritdoc AffectBuilder::getFilterNoExistsColumn()}
 * @method $this                  setFilterNoExistsColumn($bool) {{@link AffectBuilder::setFilterNoExistsColumn()} 参照@inheritdoc AffectBuilder::setFilterNoExistsColumn()}
 * @method bool                   getFilterNullAtNotNullColumn() {{@link AffectBuilder::getFilterNullAtNotNullColumn()} 参照@inheritdoc AffectBuilder::getFilterNullAtNotNullColumn()}
 * @method $this                  setFilterNullAtNotNullColumn($bool) {{@link AffectBuilder::setFilterNullAtNotNullColumn()} 参照@inheritdoc AffectBuilder::setFilterNullAtNotNullColumn()}
 * @method bool                   getConvertEmptyToNull() {{@link AffectBuilder::getConvertEmptyToNull()} 参照@inheritdoc AffectBuilder::getConvertEmptyToNull()}
 * @method $this                  setConvertEmptyToNull($bool) {{@link AffectBuilder::setConvertEmptyToNull()} 参照@inheritdoc AffectBuilder::setConvertEmptyToNull()}
 * @method bool                   getConvertBoolToInt() {{@link AffectBuilder::getConvertBoolToInt()} 参照@inheritdoc AffectBuilder::getConvertBoolToInt()}
 * @method $this                  setConvertBoolToInt($bool) {{@link AffectBuilder::setConvertBoolToInt()} 参照@inheritdoc AffectBuilder::setConvertBoolToInt()}
 * @method bool                   getConvertNumericToDatetime() {{@link AffectBuilder::getConvertNumericToDatetime()} 参照@inheritdoc AffectBuilder::getConvertNumericToDatetime()}
 * @method $this                  setConvertNumericToDatetime($bool) {{@link AffectBuilder::setConvertNumericToDatetime()} 参照@inheritdoc AffectBuilder::setConvertNumericToDatetime()}
 * @method bool                   getTruncateString() {{@link AffectBuilder::getTruncateString()} 参照@inheritdoc AffectBuilder::getTruncateString()}
 * @method $this                  setTruncateString($bool) {{@link AffectBuilder::setTruncateString()} 参照@inheritdoc AffectBuilder::setTruncateString()}
 */
// @formatter:on
#[DebugInfo(false)]
class Database
{
    use DebugInfoTrait;
    use OptionTrait;

    use FetchMethodTrait {
        fetchArrayWithSql as public fetchArray;
        fetchAssocWithSql as public fetchAssoc;
        fetchListsWithSql as public fetchLists;
        fetchPairsWithSql as public fetchPairs;
        fetchTupleWithSql as public fetchTuple;
        fetchValueWithSql as public fetchValue;
    }
    use FetchOrThrowTrait {
        fetchArrayOrThrowWithSql as public fetchArrayOrThrow;
        fetchAssocOrThrowWithSql as public fetchAssocOrThrow;
        fetchListsOrThrowWithSql as public fetchListsOrThrow;
        fetchPairsOrThrowWithSql as public fetchPairsOrThrow;
        fetchTupleOrThrowWithSql as public fetchTupleOrThrow;
        fetchValueOrThrowWithSql as public fetchValueOrThrow;
    }
    use SelectMethodTrait;
    use SelectOrThrowTrait;
    use SelectInShareTrait;
    use SelectForUpdateTrait;
    use SelectForAffectTrait;
    use EntityMethodTrait;
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

    use AffectIgnoreTrait {
        insertSelectIgnoreWithTable as public insertSelectIgnore;
        insertArrayIgnoreWithTable as public insertArrayIgnore;
        updateArrayIgnoreWithTable as public updateArrayIgnore;
        modifyArrayIgnoreWithTable as public modifyArrayIgnore;
        changeArrayIgnoreWithTable as public changeArrayIgnore;
        affectArrayIgnoreWithTable as public affectArrayIgnore;
        saveIgnoreWithTable as public saveIgnore;
        insertIgnoreWithTable as public insertIgnore;
        updateIgnoreWithTable as public updateIgnore;
        deleteIgnoreWithTable as public deleteIgnore;
        invalidIgnoreWithTable as public invalidIgnore;
        reviseIgnoreWithTable as public reviseIgnore;
        upgradeIgnoreWithTable as public upgradeIgnore;
        removeIgnoreWithTable as public removeIgnore;
        destroyIgnoreWithTable as public destroyIgnore;
        createIgnoreWithTable as public createIgnore;
        modifyIgnoreWithTable as public modifyIgnore;
        modifyIgnoreWithTable as public modifyIgnore;
    }
    use AffectOrThrowTrait {
        insertArrayOrThrowWithTable as public insertArrayOrThrow;
        createWithTable as public create;
        insertOrThrowWithTable as public insertOrThrow;
        updateOrThrowWithTable as public updateOrThrow;
        deleteOrThrowWithTable as public deleteOrThrow;
        invalidOrThrowWithTable as public invalidOrThrow;
        reviseOrThrowWithTable as public reviseOrThrow;
        upgradeOrThrowWithTable as public upgradeOrThrow;
        removeOrThrowWithTable as public removeOrThrow;
        destroyOrThrowWithTable as public destroyOrThrow;
        reduceOrThrowWithTable as public reduceOrThrow;
        upsertOrThrowWithTable as public upsertOrThrow;
        modifyOrThrowWithTable as public modifyOrThrow;
        replaceOrThrowWithTable as public replaceOrThrow;
    }
    use AffectAndPrimaryTrait {
        insertAndPrimaryWithTable as public insertAndPrimary;
        updateAndPrimaryWithTable as public updateAndPrimary;
        deleteAndPrimaryWithTable as public deleteAndPrimary;
        invalidAndPrimaryWithTable as public invalidAndPrimary;
        reviseAndPrimaryWithTable as public reviseAndPrimary;
        upgradeAndPrimaryWithTable as public upgradeAndPrimary;
        removeAndPrimaryWithTable as public removeAndPrimary;
        destroyAndPrimaryWithTable as public destroyAndPrimary;
        upsertAndPrimaryWithTable as public upsertAndPrimary;
        modifyAndPrimaryWithTable as public modifyAndPrimary;
        replaceAndPrimaryWithTable as public replaceAndPrimary;
    }

    protected function getDatabase(): Database { return $this; }

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
    #[DebugInfo(false)]
    private array $connections;

    #[DebugInfo(false)]
    private Connection $txConnection;

    private \ArrayObject $vtables;

    #[DebugInfo(false)]
    private \ArrayObject $cache;

    private array $foreignKeySwitchingLevels = [];

    private ?int $affectedRows;

    /** @var int[] dryrun 中の挿入 ID. dryrun でしか使わないので（値が戻って欲しいので ArrayObject にはしていない） */
    private array $lastInsertIds = [];

    public static function getDefaultOptions(): array
    {
        $default_options = [
            /** @var ?CacheInterface キャッシュオブジェクト */
            'cacheProvider'      => null,
            /** @var ?string|array 初期化後の SQL コマンド（mysql@PDO でいう MYSQL_ATTR_INIT_COMMAND） */
            'initCommand'        => null,
            /** @var \Closure スキーマを必要としたときのコールバック */
            'onRequireSchema'    => function (Database $db) { },
            /** @var \Closure テーブルを必要としたときのコールバック（スキーマアクセス時に一度だけ呼ばれる） */
            'onIntrospectTable'  => function (Table $table) { },
            /** @var \Closure テーブル名 => Entity クラス名のコンバータ */
            'tableMapper'        => function ($table) { return pascal_case($table); },
            /** @var bool SET IDENTITY_INSERT を自動発行するか（SQLServer 以外は無視される） */
            'autoIdentityInsert' => true,
            /** @var ?int|string バルク系メソッドのデフォルトチャンクサイズ
             * null だとチャンクしない。文字列指定で特殊なコールバックが設定できる。
             * 文字列指定は現在のところ `params:12345` で「bind parameter 数が 12345 を超えたとき」のみ。
             * これは mysql の bind parameter 上限が 65535 であることに起因している。
             */
            'defaultChunk'       => null,
            /** @var int insert 時などのデフォルトリトライ回数 */
            'defaultRetry'       => 0,
            /** @var array DB型で自動キャストする型設定。select,affect 要素を持つ（多少無駄になるがサンプルも兼ねて冗長に記述してある） */
            'autoCastType'       => [
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
            /** @var string assoc,pairs で同名キーがあった時どう振る舞うか
             * - null: 何もしない（後方優先。実質上書き）
             * - 'noallow': 例外
             * - 'skip': スキップ（前方優先）
             */
            'checkSameKey'       => null,
            /** @var string 同名カラムがあった時どう振る舞うか
             * - null: 同名カラムに対して何もしない。デフォルトの挙動（後ろ優先）となる
             * - "noallow": 同名カラムを検出したら即座に例外を投げるようになる
             * - "strict": 同名カラムを検出したら値を確認し、全て同一値ならその値をカラム値とする。一つでも異なる値がある場合は例外を投げる
             * - "loose": ↑と同じだが、比較は緩く行われる（文字列比較）。更に null は除外してチェックする
             *
             * 例えばよくありそうな `name` カラムがある2つのテーブルを JOIN すると意図しない結果になることが多々ある。
             *
             * ```php
             * $db->fetchArray('select t_article.*, t_user.* from t_article join t_user using (user_id)');
             * ```
             *
             * このクエリは `t_user.name` と `t_article.name` というカラムが存在すると破綻する。大抵の場合は `t_user.name` が返ってくるが明確に意図した結果ではない。
             * このオプションを指定するとそういった状況を抑止することができる。
             *
             * ただし、このオプションはフェッチ結果の全行全列を確認する必要があるため**猛烈に遅い**。
             * 基本的には開発時に指定し、本運用環境では null を指定しておくと良い。
             *
             * ただ開発時でも、 "noallow" の使用はおすすめできない。
             * 例えば↑のクエリは user_id で using しているように、name 以外に user_id カラムも重複している。したがって "noallow" を指定すると例外が飛ぶことになる。
             * 往々にして主キーと外部キーは同名になることが多いので、 "noallow" を指定しても実質的には使い物にならない。
             *
             * これを避けるのが "strict" で、これを指定すると同名カラムの値が同値であればセーフとみなす。つまり動作に影響を与えずある程度良い感じにチェックしてくれるようになる。
             * さらに "loose" を指定すると NULL を除外して重複チェックする。これは LEFT JOIN 時に効果を発揮する（LEFT 時は他方が NULL になることがよくあるため）。
             * "loose" は文字列による緩い比較になってしまうが、型が異なる状況はそこまで多くないはず。
             *
             * なお、フェッチ値のチェックであり、クエリレベルでは何もしないことに注意。
             * 例えば↑のクエリで "strict" のとき「**たまたま** `t_user.name` と `t_article.name` が同じ値だった」ケースは検出できない。また、「そもそもフェッチ行が0だった」場合も検出できない。
             * このオプションはあくまで開発をサポートする機能という位置づけである。
             */
            'checkSameColumn'    => null,
            /** @var bool 更新クエリを実行せずクエリ文字列を返すようにするか（内部向け） */
            'dryrun'             => false,
            /** @var bool 更新クエリを実行せずプリペアされたステートメントを返すようにするか（内部向け） */
            'preparing'          => false,
            /** @var bool 参照系クエリをマスターで実行するか(「スレーブに書き込みたい」はまずあり得ないが「マスターから読み込みたい」はままある) */
            'masterMode'         => false,
            /** @var string|CompatiblePlatform CompatiblePlatform のクラス名 or インスタンス */
            'compatiblePlatform' => CompatiblePlatform::class,
            /** @var array exportXXX 呼び出し時にどのクラスを使用するか */
            'exportClass'        => [
                'array' => ArrayGenerator::class,
                'csv'   => CsvGenerator::class,
                'json'  => JsonGenerator::class,
            ],
            /** @var ?LoggerInterface ロギングオブジェクト（LoggerInterface） */
            'logger'             => null,
        ];

        // 他クラスのオプションをマージ
        $default_options += TableGateway::getDefaultOptions();
        $default_options += SelectBuilder::getDefaultOptions();
        $default_options += AffectBuilder::getDefaultOptions();
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
     * さらに、このクラスのオプションは少し特殊で、 {@link SelectBuilder} や {@link TableGateway} のオプションも複合で与えることができる。
     * その場合、**そのクラスのインスタンスが生成されたときのデフォルト値**として作用する。
     *
     * ```php
     * # defaultOrder は本来 SelectBuilder のオプションだが、 Database のオプションとしても与えることができる
     * $db = new Database($dbconfig, [
     *     'defaultOrder' => true,
     * ]);
     * $db->selectArray('tablename'); // 上記で false を設定してるので自動で `ORDER BY 主キー` は付かない
     * ```
     *
     * つまり実質的には「本ライブラリの設定が全て可能」となる。あまり「この設定はどこのクラスに係るのか？」は気にしなくて良い。
     *
     * @param array|Connection|Connection[] $dbconfig 設定配列
     */
    public function __construct($dbconfig, array $options = [])
    {
        // 代替クラスのロード（本来こんなところに書くべきではないが他に良い場所もないのでほぼ必ず通るここに書く）
        class_aliases([
            \Doctrine\DBAL\Driver\SQLite3\Result::class => \ryunosuke\dbml\Driver\SQLite3\Result::class,
            \Doctrine\DBAL\Driver\Mysqli\Result::class  => \ryunosuke\dbml\Driver\Mysqli\Result::class,
            \Doctrine\DBAL\Driver\PgSQL\Result::class   => \ryunosuke\dbml\Driver\PgSQL\Result::class,
            \Doctrine\DBAL\Driver\SQLSrv\Result::class  => \ryunosuke\dbml\Driver\SQLSrv\Result::class,
            \Doctrine\DBAL\Driver\PDO\Result::class     => \ryunosuke\dbml\Driver\PDO\Result::class,
        ]);

        $configure = function (Configuration $configuration) use ($options) {
            $middlewares = $configuration->getMiddlewares();
            if (array_find($middlewares, fn($middleware) => $middleware instanceof LoggingMiddleware) === null) {
                $middlewares[] = new LoggingMiddleware(new LoggerChain());
                $configuration->setMiddlewares($middlewares);
            }
            if ($options['initCommand'] ?? []) {
                $middlewares[] = new class((array) $options['initCommand']) implements \Doctrine\DBAL\Driver\Middleware {
                    private $initCommands;

                    public function __construct($initCommands)
                    {
                        $this->initCommands = $initCommands;
                    }

                    public function wrap(Driver $driver): Driver
                    {
                        return new class ($driver, $this->initCommands) extends AbstractDriverMiddleware {
                            private $initCommands;

                            public function __construct(Driver $wrappedDriver, $initCommands)
                            {
                                parent::__construct($wrappedDriver);
                                $this->initCommands = $initCommands;
                            }

                            public function connect(array $params): \Doctrine\DBAL\Driver\Connection
                            {
                                $connection = parent::connect($params);
                                foreach ($this->initCommands as $initCommand) {
                                    $connection->exec($initCommand);
                                }
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
            $master = $slave = $dbconfig = Adhoc::parseParams($dbconfig);
            foreach ($dbconfig as $key => $value) {
                if ($key !== 'driverOptions' && is_array($value) && isset($value[0], $value[1])) {
                    $master[$key] = $value[0];
                    $slave[$key] = $value[rand(1, count($value) - 1)];
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
     */
    public function __isset(string $name): bool
    {
        return $this->$name !== null;
    }

    /**
     * ゲートウェイオブジェクトを伏せる
     */
    public function __unset(string $name): void
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
     */
    public function __get(string $name): ?TableGateway
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

    private function _tableMap(): array
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

    private function _sqlToStmt($sql, iterable $params, Connection $connection)
    {
        if ($sql instanceof Statement) {
            $stmt = $sql->executeSelect($params, $connection);
        }
        elseif ($sql instanceof SelectBuilder) {
            $stmt = $sql->getPreparedStatement();
            if ($stmt) {
                $stmt = $stmt->executeSelect($params, $connection);
            }
            else {
                $builder_params = $sql->getParams();
                $params = $params instanceof \Traversable ? iterator_to_array($params) : $params;
                // $builder も params を持っていて fetch の引数も指定されていたらどっちを使えばいいのかわからない
                if (count($params) > 0 && count($builder_params) > 0) {
                    throw new \UnexpectedValueException('specified parameter both $builder and fetch argument.');
                }
                $sql->detectAutoOrder(true);
                $stmt = $this->executeSelect((string) $sql, $builder_params ?: $params, $sql->getCacheTtl());
            }
        }
        else {
            $stmt = $this->executeSelect($sql, $params);
        }

        $stmt = $this->getCompatibleConnection($connection)->customResult($stmt, $this->getUnsafeOption('checkSameColumn'));

        return $stmt;
    }

    private function _getConverter($data_source): \Closure
    {
        $platform = $this->getPlatform();
        $cast_type = $this->getUnsafeOption('autoCastType');

        /** @var SelectBuilder $data_source */
        $data_source = instance_of($data_source, SelectBuilder::class);
        $rconverter = $data_source?->getRowConverter();
        $alias_table = array_lookup($data_source?->getFromPart() ?: [], 'table');

        $getTableColumnType = function ($tableName, $columnName) use ($alias_table) {
            // クエリビルダ経由でエイリアスマップが得られるなら変換ができる
            if (isset($alias_table[$tableName])) {
                $tableName = $alias_table[$tableName];
            }
            if (!is_string($tableName)) {
                return null;
            }

            static $cache = [];
            if (!isset($cache[$tableName])) {
                $cache[$tableName] = [];
                if ($this->getSchema()->hasTable($tableName)) {
                    $cache[$tableName] = array_map(fn($column) => $column->getType(), $this->getSchema()->getTableColumns($tableName));
                }
            }
            return $cache[$tableName][$columnName] ?? null;
        };

        $cconverter = function ($typename, $value) use ($cast_type, $platform) {
            if (isset($cast_type[$typename]['select'])) {
                $converter = $cast_type[$typename]['select'];
                if ($converter instanceof \Closure) {
                    return $converter($value, $platform);
                }
                else {
                    return Type::getType($typename)->convertToPHPValue($value, $platform);
                }
            }
            return $value;
        };

        $metadataTableColumn = null;
        return function ($row, $metadata) use ($getTableColumnType, $cconverter, $rconverter, &$metadataTableColumn) {
            // 死ぬほど呼び出されるので適した形式でキャッシュしておく
            if ($metadataTableColumn === null) {
                $metadataTableColumn = [];
                foreach ($metadata as $meta) {
                    $metadataTableColumn[$meta['aliasColumnName']] = [
                        'tableColumn'  => [
                            strlen($meta['actualTableName'] ?? '') ? $meta['actualTableName'] : $meta['aliasTableName'],
                            strlen($meta['actualColumnName'] ?? '') ? $meta['actualColumnName'] : $meta['aliasColumnName'],
                        ],
                        'doctrineType' => $meta['doctrineType'] ?? null,
                    ];
                }
            }

            $newrow = [];
            foreach ($row as $c => $v) {
                // Alias|Typename 由来の変換（修飾子があればアドホックに変換できる）
                if (count($parts = explode('|', $c, 2)) === 2) {
                    [$c, $typename] = $parts;
                    $v = $cconverter($typename, $v);
                }
                // Result 由来の変換（Result がテーブル名とカラム名を返してくれるなら対応表が作れる）
                elseif (isset($metadataTableColumn[$c]) && $type = $getTableColumnType(...$metadataTableColumn[$c]['tableColumn'])) {
                    $v = $cconverter($type->getName(), $v);
                }
                // Native 由来の変換（Result が NativeType を返してくれるなら直接変換できる）
                elseif (isset($metadataTableColumn[$c]['doctrineType'])) {
                    $v = $cconverter($metadataTableColumn[$c]['doctrineType'], $v);
                }

                $newrow[$c] = $v;
            }
            if ($rconverter) {
                $newrow = $rconverter($newrow);
            }
            return $newrow;
        };
    }

    private function _getChunk()
    {
        // ステートメントが複数に分かれても全く嬉しくないので prepare 中は無効にする
        if ($this->getUnsafeOption('preparing')) {
            return null;
        }

        $chunk = $this->getUnsafeOption('defaultChunk');
        // 特殊設定: params の数でチャンク
        if (is_string($chunk) && preg_match('#^params:\s*(\d+)$#', trim($chunk), $matches)) {
            $count = 0;
            return static function ($row) use (&$count, $matches) {
                $count += count($row);
                $result = $count < $matches[1];
                if (!$result) {
                    $count = 0;
                }
                return $result;
            };
        }
        return $chunk;
    }

    private function _postaffect(AffectBuilder $builder, array $opt)
    {
        $affected = $builder->getAffectedRows();
        if ($this->getUnsafeOption('dryrun')) {
            return arrayize($affected);
        }
        if (!is_int($affected)) {
            return $affected;
        }

        if (($seq = $builder->getAutoIncrementSeq()) !== false) {
            $this->resetAutoIncrement($builder->getTable(), $seq);
        }

        // 歴史的な経緯で primary:1 は例外モード
        if (array_get($opt, 'primary') === 1 && $affected === 0) {
            throw new NonAffectedException('affected row is nothing.');
        }
        // 同上。 primary:2 は空配列返しモード
        if (array_get($opt, 'primary') === 2 && $affected === 0) {
            return [];
        }
        // 同上。 primary:3 は主キー返しモード
        if (array_get($opt, 'primary')) {
            $data = $builder->getSet() + $builder->getWhere();
            foreach ($data as $k => $v) {
                $kk = str_lchop($k, "{$builder->getTable()}.");
                if (!isset($data[$kk])) {
                    $data[$kk] = $v;
                }
            }
            $pcols = $this->getSchema()->getTablePrimaryColumns($builder->getTable());
            $primary = array_intersect_key($data, $pcols);
            $autocolumn = $this->getSchema()->getTableAutoIncrement($builder->getTable())?->getName();
            if ($autocolumn && !isset($primary[$autocolumn])) {
                $primary[$autocolumn] = $this->getLastInsertId($builder->getTable(), $autocolumn);
            }
            // Expression や SelectBuilder はどうしようもないのでクエリを投げて取得
            // 例えば modify メソッドの列に↑のようなオブジェクトが来てかつ UPDATE された場合、lastInsertId は得られない
            foreach ($primary as $val) {
                if (is_object($val)) {
                    return $this->selectTuple([$builder->getTable() => array_keys($pcols)], $primary);
                }
            }
            return $primary;
        }
        return $affected;
    }

    private function _postaffects(AffectBuilder $builder, array $affecteds)
    {
        if ($this->getUnsafeOption('dryrun')) {
            return $affecteds;
        }
        if ($this->getUnsafeOption('preparing')) {
            return $builder->getAffectedRows();
        }
        if (($seq = $builder->getAutoIncrementSeq()) !== false) {
            $this->resetAutoIncrement($builder->getTable(), $seq);
        }
        return array_sum($affecteds);
    }

    private function _setIdentityInsert(string $tableName, array $data): \Closure
    {
        $cplatform = $this->getCompatiblePlatform();
        if ($this->getUnsafeOption('autoIdentityInsert') && !$cplatform->supportsIdentityUpdate()) {
            // @codeCoverageIgnoreStart
            $autocol = $this->getSchema()->getTableAutoIncrement($tableName);
            if ($autocol && array_key_exists($autocol->getName(), $data)) {
                $this->getConnection()->executeStatement($cplatform->getIdentityInsertSQL($tableName, true));
                return fn() => $this->getConnection()->executeStatement($cplatform->getIdentityInsertSQL($tableName, false));
            }
            // @codeCoverageIgnoreEnd
        }
        return fn() => null;
    }

    private function _throwForeignKeyConstraintViolationException(ForeignKeyConstraint $fkey)
    {
        if (!$this->getUnsafeOption('dryrun')) {
            $message = vsprintf("`%s`, CONSTRAINT `%s` FOREIGN KEY (%s) REFERENCES `%s` (%s)", [
                first_key($this->getSchema()->getForeignTable($fkey)),
                $fkey->getName(),
                implode(',', array_map(fn($column) => "`$column`", $fkey->getLocalColumns())),
                $fkey->getForeignTableName(),
                implode(',', array_map(fn($column) => "`$column`", $fkey->getForeignColumns())),
            ]);
            $driverException = new class("Cannot delete or update a parent row: a foreign key constraint fails ($message)") extends \Exception implements Driver\Exception {
                public function getSQLState() { return '10000'; } // @codeCoverageIgnore
            };
            throw new ForeignKeyConstraintViolationException($driverException, null);
        }
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
     */
    public function describe(?string $objectname = null): AbstractAsset
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
     * コード補完用のアノテーショントレイトを取得する
     *
     * 存在するテーブル名や tableMapper などを利用して mixin 用のトレイトを作成する。
     * このメソッドが吐き出したトレイトを `@ mixin Hogera` などとすると補完が効くようになる。
     */
    public function echoAnnotation(?string $namespace = null, ?string $filename = null): string
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
                $carry[$column->getName()] = reflect_type_resolve($reftype) ?? $special_types[$typename] ?? 'string';
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
            $body = php_indent("\n" . implode("\n\n", array_flatten((array) $body)), ['baseline' => 0, 'indent' => 4]);
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
     */
    public function echoPhpStormMeta(?string $namespace = null, ?string $filename = null): string
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
                $vs = type_exists($v) ? "$v::class" : var_export($v, true);
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
                return reflect_type_resolve($reftype ?? null) ?? $special_types[$typename] ?? 'string';
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
        $result = "<?php\nnamespace PHPSTORM_META {\n$result\n}";

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
     */
    public function setLogger($logger): static
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
     */
    public function setAutoCastType(array $castTypes): static
    {
        $types = [];
        foreach ($castTypes as $type => $opt) {
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
     */
    public function setConnection(Connection|bool $connection): static
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
     */
    public function getConnection(): Connection
    {
        return $this->txConnection;
    }

    /**
     * マスター接続（Connection）を返す
     */
    public function getMasterConnection(): Connection
    {
        // Master はマスターを返す
        return $this->connections['master'];
    }

    /**
     * スレーブ接続（Connection）を返す
     */
    public function getSlaveConnection(): Connection
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
    public function getConnections(): array
    {
        $cons = [];
        foreach ($this->connections as $con) {
            $cons[spl_object_hash($con)] = $con;
        }
        return array_values($cons);
    }

    /**
     * {@link CompatibleConnection 互換用コネクション}を取得する
     */
    public function getCompatibleConnection(?Connection $connection = null): CompatibleConnection
    {
        $connection ??= $this->getConnection();
        return $this->cache['compatibleConnection'][spl_object_hash($connection)] ??= new CompatibleConnection($connection);
    }

    /**
     * {@link AbstractPlatform dbal のプラットフォーム}を取得する
     */
    public function getPlatform(): AbstractPlatform
    {
        return $this->getSlaveConnection()->getDatabasePlatform();
    }

    /**
     * {@link CompatiblePlatform 互換用プラットフォーム}を取得する
     */
    public function getCompatiblePlatform(): CompatiblePlatform
    {
        if (!isset($this->cache['compatiblePlatform'])) {
            $classname = $this->getUnsafeOption('compatiblePlatform');
            assert(is_a($classname, CompatiblePlatform::class, true));
            $this->cache['compatiblePlatform'] = is_object($classname) ? $classname : new $classname($this->getPlatform(), (fn() => $this->getServerVersion())->bindTo($this->getSlaveConnection(), Connection::class)());
        }
        return $this->cache['compatiblePlatform'];
    }

    /**
     * {@link Schema スキーマオブジェクト}を取得する
     */
    public function getSchema(): Schema
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
     */
    public function getEntityClass(string $tablename): string
    {
        $map = $this->_tableMap()['entityClass'];
        $entityname = $this->convertEntityName($tablename);
        return $map[$entityname] ?? Entity::class;
    }

    /**
     * テーブル名からゲートウェイクラス名を取得する
     */
    public function getGatewayClass(string $tablename): string
    {
        $map = $this->_tableMap()['gatewayClass'];
        $tablename = $this->convertTableName($tablename);
        return $map[$tablename] ?? TableGateway::class;
    }

    /**
     * エンティティ名からテーブル名へ変換する
     */
    public function convertTableName(string $entityname): string
    {
        $map = $this->_tableMap()['EtoT'];
        return $map[$entityname] ?? $entityname;
    }

    /**
     * テーブル名からエンティティ名へ変換する
     */
    public function convertEntityName(string $tablename): string
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
     */
    public function declareVirtualTable(string $vtableName, $tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []): static
    {
        assert(!$this->getSchema()->hasTable($vtableName));
        $this->vtables[$vtableName] = array_combine(SelectBuilder::CLAUSES, [$tableDescriptor, $where, $orderBy, $limit, $groupBy, $having]);
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
     */
    public function declareCommonTable(array $expressions): static
    {
        foreach ($expressions as $name => $expression) {
            if ($expression instanceof \Closure) {
                $expression = $expression($this);
            }
            if (is_array($expression)) {
                $expression = $this->createSelectBuilder()->build($expression);
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
     */
    public function getVirtualTable(string $vtableName): ?array
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
     *         ],
     *     ],
     * ]);
     * ```
     *
     * なお、実際のデータベース上の型が変わるわけではない。あくまで「php が思い込んでいる型」の上書きである。
     * php 側の型が活用される箇所はそう多くないが、例えば下記のような処理では上書きすると有用なことがある。
     *
     * - {@link Database::setAutoCastType()} による型による自動キャスト
     */
    public function overrideColumns(array $definition): static
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
                    if (!isset($def['type']) && $def['select'] instanceof SelectBuilder) {
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
     * CASCADE・RESTRICT はアプリレイヤーで可能な限りエミュレーションされる。
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
     */
    public function addRelation(array $relations): array
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
     */
    public function addForeignKey(string $localTable, string $foreignTable, string|array $fkdata, ?string $fkname = null): ForeignKeyConstraint
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
     */
    public function ignoreForeignKey(string $localTable, string $foreignTable, string|array $columnsMap): ForeignKeyConstraint
    {
        $columnsMap = array_rekey(arrayize($columnsMap), function ($k, $v) { return is_int($k) ? $v : $k; });

        // 外部キーオブジェクトの生成
        $fk = new ForeignKeyConstraint(array_keys($columnsMap), $foreignTable, array_values($columnsMap));

        return $this->getSchema()->ignoreForeignKey($fk, $localTable);
    }

    /**
     * DBレイヤーの外部キー制約の有効無効を切り替える
     *
     * RDBMS によっては制約単位で切り替えられるので、その場合は $fkey を指定する。
     *
     * 深い階層で enable -> enable -> disable ->disable した場合、最初の disable で解除されてしまうので、ネストレベルが管理される。
     * ネストレベルが 0 の時（本当に切り替わったとき）のみクエリは実行される。
     * さらにネストレベルは $fkey 指定時はそれぞれで管理される。
     */
    public function switchForeignKey(bool $enable, ForeignKeyConstraint|string $fkey): int
    {
        $fkeyname = $fkey instanceof ForeignKeyConstraint ? $fkey->getName() : $fkey;
        $table_name = first_key($this->getSchema()->getForeignTable($fkey));
        $levelkey = "$fkeyname@$table_name";

        $level = $this->foreignKeySwitchingLevels[$levelkey] ?? 0;

        if ($enable && ++$level === 0) {
            $this->getSchema()->setForeignKeyMetadata($fkeyname, ['enable' => $enable]);
            foreach ($this->getCompatiblePlatform()->getSwitchForeignKeyExpression($enable, $table_name, $fkeyname) as $sql) {
                $this->executeAffect($sql);
            }
        }
        if (!$enable && $level-- === 0) {
            $this->getSchema()->setForeignKeyMetadata($fkeyname, ['enable' => $enable]);
            foreach ($this->getCompatiblePlatform()->getSwitchForeignKeyExpression($enable, $table_name, $fkeyname) as $sql) {
                $this->executeAffect($sql);
            }
        }

        return $this->foreignKeySwitchingLevels[$levelkey] = $level;
    }

    /**
     * begin
     */
    public function begin(): int
    {
        $this->txConnection->beginTransaction();
        return $this->txConnection->getTransactionNestingLevel();
    }

    /**
     * commit
     */
    public function commit(): int
    {
        $this->txConnection->commit();
        return $this->txConnection->getTransactionNestingLevel();
    }

    /**
     * rollback
     */
    public function rollback(): int
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
     */
    public function transact(callable $main, ?callable $catch = null, array $options = [], bool $throwable = true): mixed
    {
        return $this->transaction($main, $catch, $options)->perform($throwable);
    }

    /**
     * トランザクションオブジェクトを返す
     *
     * $options は {@link Transaction} を参照。
     */
    public function transaction(?callable $main = null, ?callable $catch = null, array $options = []): Transaction
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
     */
    public function preview(callable $main, array $options = null): array
    {
        $tx = $this->transaction($main, $options);
        $tx->preview($logs);
        return $logs;
    }

    /**
     * new {@link Expression} するだけのメソッド
     *
     * 可能なら直接 new Expression せずにこのメソッド経由で生成したほうが良い（MUST ではない）。
     */
    public function raw(string $expr, mixed $params = []): Expression
    {
        return new Expression($expr, $params);
    }

    /**
     * 引数内では AND、引数間では OR する Expression を返す
     *
     * 得られる結果としては {@link SelectBuilder::where()}とほぼ同じ。
     * ただし、あちらはクエリビルダで WHERE 専用であるのに対し、こちらは Expression を返すだけなので SELECT 句に埋め込むことができる。
     *
     * ```php
     * $db->select([
     *     't_article' => [
     *         'contain_hoge' => $db->operator(['article_title:%LIKE%' => 'hoge']),
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
     */
    public function operator(...$predicates): Expression
    {
        $params = [];
        $ands = [];
        foreach ($predicates as $cond) {
            array_set($ands, Where::and(arrayize($cond))($this)->merge($params), null, function ($v) { return !Adhoc::is_empty($v); });
        }
        return new Expression('(' . implode(' OR ', Adhoc::wrapParentheses($ands)) . ')', $params);
    }

    /**
     * 文字列とパラメータから TableDescriptor を生成する
     */
    public function descriptor(string $tableDescriptor, array $params = []): TableDescriptor
    {
        $tableDescriptor = new TableDescriptor($this, $tableDescriptor, []);
        return $tableDescriptor->bind($this, $params);
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
     */
    public function binder(): callable|\ArrayObject
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
     */
    public function quote(mixed $value, ?int $type = null): mixed
    {
        // SQLSrv でテストを通すためのアドホック実装だが、全RDBMS でやってしまっても問題はないはず（テストが通るようなら消してしまって構わない）
        if (is_int($value)) {
            return "'$value'";
        }
        return Adhoc::stringifyParameter($value, fn($value) => $this->getSlaveConnection()->quote($value, $type));
    }

    /**
     * 識別子をクオートする
     *
     * {@link Connection::quoteIdentifier()} を参照。
     */
    public function quoteIdentifier(string $identifier): string
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
     */
    public function queryInto(string|Queryable $sql, iterable $params = []): string
    {
        $params = $params instanceof \Traversable ? iterator_to_array($params) : $params;

        if ($sql instanceof Queryable) {
            $sql = $sql->merge($params);
        }

        $parser = new Parser($this->getPlatform()->createSQLParser());
        return $parser->convertQuotedSQL($sql, $params, fn($v) => $this->quote($v));
    }

    /**
     * SELECT ビルダを生成して返す
     *
     * 極力 new SelectBuilder せずにこのメソッドを介すこと。
     */
    public function createSelectBuilder(): SelectBuilder
    {
        return new SelectBuilder($this);
    }

    /**
     * AFFECT ビルダを生成して返す
     *
     * 極力 new AffectBuilder せずにこのメソッドを介すこと。
     */
    public function createAffectBuilder(): AffectBuilder
    {
        return new AffectBuilder($this);
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
     * 返り値として暖気クエリの結果（現在は [table => [index => COUNT]] を返すが、COUNt 部分は変更されることがある）
     */
    public function warmup(array|string $table_names = []): array
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
                    $select->detectAutoOrder(false);
                    $select->hint($cplatform->getIndexHintSQL($index->getName()));
                    $columns[$alias] = $select;
                }
            }
        }

        $result = [];
        foreach ($this->createSelectBuilder()->addSelect($columns)->tuple() as $key => $count) {
            [$tname, $iname] = explode($DELIMITER, $key, 2);
            $result[$tname][$iname] = $count;
        }
        return $result;
    }

    /**
     * foreach で回せる何かとサブメソッド名で結果を構築する
     *
     * @ignore 難解過ぎる上内部でしか使われない
     */
    public function perform(Result|array $row_provider, string $fetch_mode, ?\Closure $converter = null): mixed
    {
        $checkSameKey = $this->getUnsafeOption('checkSameKey');

        $metadata = [];
        if ($row_provider instanceof Result) {
            $metadata = $this->getCompatibleConnection()->getMetadata($row_provider);
            $row_provider = $row_provider->fetchAllAssociative();
        }

        switch ($fetch_mode) {
            default:
                throw new \BadMethodCallException("unknown fetch method '$fetch_mode'.");

            /// 配列の配列系
            case self::METHOD_ARRAY:
                $result = [];
                foreach ($row_provider as $row) {
                    $row = $converter ? $converter($row, $metadata) : $row;
                    $result[] = $row;
                }
                return $result;
            case self::METHOD_ASSOC:
                $result = [];
                foreach ($row_provider as $row) {
                    $row = $converter ? $converter($row, $metadata) : $row;
                    foreach ($row as $e) {
                        if ($checkSameKey !== null && array_keys_exist($e, $result)) {
                            if ($checkSameKey === 'noallow') {
                                throw new \LogicException("duplicated key $e for " . self::METHOD_ASSOC);
                            }
                            if ($checkSameKey === 'skip') {
                                break;
                            }
                        }
                        $result[$e] = $row;
                        break;
                    }
                }
                return $result;

            /// 配列系
            case self::METHOD_LISTS:
                $result = [];
                foreach ($row_provider as $row) {
                    $row = $converter ? $converter($row, $metadata) : $row;
                    foreach ($row as $e) {
                        $result[] = $e;
                        break;
                    }
                }
                return $result;
            case self::METHOD_PAIRS:
                $result = [];
                foreach ($row_provider as $row) {
                    $row = $converter ? $converter($row, $metadata) : $row;
                    $key = null;
                    foreach ($row as $e) {
                        if (!isset($key)) {
                            $key = $e;
                            continue;
                        }
                        if ($checkSameKey !== null && array_keys_exist($key, $result)) {
                            if ($checkSameKey === 'noallow') {
                                throw new \LogicException("duplicated key $key for " . self::METHOD_PAIRS);
                            }
                            if ($checkSameKey === 'skip') {
                                break;
                            }
                        }
                        $result[$key] = $e;
                        break;
                    }
                    if (!array_keys_exist($key, $result)) {
                        throw new \LogicException("missing value of $key for " . self::METHOD_PAIRS);
                    }
                }
                return $result;

            /// シンプル系
            case self::METHOD_TUPLE:
                $result = false;
                $first = true;
                foreach ($row_provider as $row) {
                    $row = $converter ? $converter($row, $metadata) : $row;
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
                    $row = $converter ? $converter($row, $metadata) : $row;
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

    /**
     * フェッチメソッドとクエリとパラメータを指定して実行する
     *
     * @used-by fetchArray()
     * @used-by fetchAssoc()
     * @used-by fetchLists()
     * @used-by fetchPairs()
     * @used-by fetchTuple()
     * @used-by fetchValue()
     * @used-by fetchArrayOrThrow()
     * @used-by fetchAssocOrThrow()
     * @used-by fetchListsOrThrow()
     * @used-by fetchPairsOrThrow()
     * @used-by fetchTupleOrThrow()
     * @used-by fetchValueOrThrow()
     */
    public function fetch(string $method, $sql, iterable $params = [])
    {
        $converter = $this->_getConverter($sql);
        $stmt = $this->_sqlToStmt($sql, $params, $this->getSlaveConnection());
        $result = $this->perform($stmt, $method, $converter);

        if ($result === false) {
            return false;
        }
        if ($sql instanceof SelectBuilder) {
            if (self::METHODS[$method]['keyable'] === null) {
                $result = $sql->postselect([$result])[0];
            }
            else {
                $result = $sql->postselect($result);
            }
        }
        return $result;
    }

    public function fetchOrThrow(string $method, $sql, iterable $params = [])
    {
        $result = $this->{"fetch$method"}($sql, $params);
        // Value, Tuple は [] を返し得ないし、複数行系も false を返し得ない
        if ($result === [] || $result === false) {
            throw new NonSelectedException('record is not found.');
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
     * - see {@link SelectBuilder::column()}
     * - see {@link SelectBuilder::where()}
     * - see {@link SelectBuilder::orderBy()}
     * - see {@link SelectBuilder::limit()}
     * - see {@link SelectBuilder::groupBy()}
     * - see {@link SelectBuilder::having()}
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
     * @param array|string $where WHERE 条件（{@link SelectBuilder::where()}）
     * @param array|string $orderBy 並び順（{@link SelectBuilder::orderBy()}）
     * @param array|int $limit 取得件数（{@link SelectBuilder::limit()}）
     * @param array|string $groupBy グルーピング（{@link SelectBuilder::groupBy()}）
     * @param array|string $having HAVING 条件（{@link SelectBuilder::having()}）
     */
    public function select($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []): SelectBuilder
    {
        $builder = $this->createSelectBuilder();
        return $builder->build(array_combine(SelectBuilder::CLAUSES, [$tableDescriptor, $where, $orderBy, $limit, $groupBy, $having]), true);
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
     * - see {@link SelectBuilder::cast()}
     * - see {@link SelectBuilder::column()}
     * - see {@link SelectBuilder::where()}
     * - see {@link SelectBuilder::orderBy()}
     * - see {@link SelectBuilder::limit()}
     * - see {@link SelectBuilder::groupBy()}
     * - see {@link SelectBuilder::having()}
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
    public function entity($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []): SelectBuilder
    {
        return $this->select($tableDescriptor, $where, $orderBy, $limit, $groupBy, $having)->cast(null);
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
     */
    public function selectAggregate($aggregation, $column, $where = [], $groupBy = [], $having = []): SelectBuilder
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
     */
    public function yield($sql, iterable $params = []): Yielder
    {
        $chunk = null;
        $converter = $this->_getConverter($sql);
        if ($sql instanceof SelectBuilder) {
            $chunk = $sql->getLazyChunk();
            $converter = fn($rows, $metadata) => $sql->postselect(array_map(fn($row) => $converter($row, $metadata), $rows), !$chunk);
        }
        return new Yielder(fn($connection) => $this->_sqlToStmt($sql, $params, $connection), $this->getCompatibleConnection($this->getSlaveConnection()), null, $converter, $chunk);
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
     */
    public function export(string|AbstractGenerator $generator, $sql, iterable $params = [], array $config = [], $file = null): int
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
     */
    public function subselect($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []): SelectBuilder
    {
        $builder = $this->createSelectBuilder();
        return $builder->setLazyMode()->build(array_combine(SelectBuilder::CLAUSES, [$tableDescriptor, $where, $orderBy, $limit, $groupBy, $having]));
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
     * @param array|string $where WHERE 条件（{@link SelectBuilder::where()}）
     * @param array|string $orderBy 並び順（{@link SelectBuilder::orderBy()}）
     * @param array|int $limit 取得件数（{@link SelectBuilder::limit()}）
     * @param array|string $groupBy グルーピング（{@link SelectBuilder::groupBy()}）
     * @param array|string $having HAVING 条件（{@link SelectBuilder::having()}）
     */
    public function subquery($tableDescriptor, $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []): SelectBuilder
    {
        // build 前にあらかじめ setSubmethod して分岐する必要がある
        $builder = $this->createSelectBuilder();
        $builder->setSubmethod('query');
        return $builder->build(array_combine(SelectBuilder::CLAUSES, [$tableDescriptor, $where, $orderBy, $limit, $groupBy, $having]), true);
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
     */
    public function subaggregate($aggregate, $column, $where = []): SelectBuilder
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
     * @return string|int|array 集約結果
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
     * @param array|string|SelectBuilder $unions union サブクエリ
     * @param array|string $column 取得カラム [column]
     * @param array|string $where 条件
     * @param array|string $orderBy 単カラム名か[column=>asc/desc]な連想配列
     * @param array|int $limit 単数値か[offset=>count]な連想配列
     * @param array|string $groupBy カラム名かその配列
     * @param array|string $having 条件
     */
    public function union($unions, $column = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []): SelectBuilder
    {
        return $this->select(['' => $column], $where, $orderBy, $limit, $groupBy, $having)->union($unions);
    }

    /**
     * UNION ALL する
     *
     * ALL で UNION される以外は {@link union()} と全く同じ。
     *
     * @param array|string|SelectBuilder $unions union サブクエリ
     * @param array|string $column 取得カラム [column]
     * @param array|string $where 条件
     * @param array|string $orderBy 単カラム名か[column=>asc/desc]な連想配列
     * @param array|int $limit 単数値か[offset=>count]な連想配列
     * @param array|string $groupBy カラム名かその配列
     * @param array|string $having 条件
     */
    public function unionAll($unions, $column = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []): SelectBuilder
    {
        return $this->select(['' => $column], $where, $orderBy, $limit, $groupBy, $having)->unionAll($unions);
    }

    /**
     * 特定レコードの前後のレコードを返す
     *
     * {@link SelectBuilder::neighbor()} へのプロキシメソッド。
     *
     * @inheritdoc SelectBuilder::neighbor()
     *
     * @param array|string $tableDescriptor 取得テーブルとカラム（{@link TableDescriptor}）
     */
    public function neighbor($tableDescriptor, array $predicates, int $limit = 1): array
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
    public function gather(string $tablename, $wheres = [], $other_wheres = [], bool $parentive = false): array
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
     */
    public function differ(array $array, string $tablename, $wheres = []): array
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

        $select = $this->createSelectBuilder();
        $select->select("$tmpname.$keyname");
        $select->from(new Expression('(' . implode(' UNION ', $selects) . ')', $params), $tmpname);
        $select->leftJoinOn($tablename, array_merge(arrayize($wheres), array_sprintf($joincols, "$tablename.%1\$s = $tmpname.%1\$s")));
        $select->where(array_sprintf($pkcols, "$tablename.%2\$s IS NULL"));
        $select->detectAutoOrder(false);

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
     */
    public function dryrun(): static
    {
        return $this->context(['dryrun' => true]);
    }

    /**
     * prepare モードへ移行する
     *
     * このメソッドを呼んだ直後は、更新系メソッドが実際には実行せずに prepare されたステートメントを返すようになる。
     *
     * このメソッドは `setOption` を利用した {@link context()} メソッドで実装されている。つまり
     *
     * - `setOption('prepare', true);`
     * - `context(['prepare' => true]);`
     *
     * などと実質的にはほとんど同じ（後者に至っては全く同じ=移譲・糖衣構文）。
     * つまりは {@link dryrun()} と同じなのでそちらも参照。
     */
    public function prepare(): static
    {
        return $this->context(['preparing' => true]);
    }

    /**
     * 取得系クエリをプリペアする
     *
     * @inheritdoc Database::select()
     */
    public function prepareSelect($tableDescriptor = [], $where = [], $orderBy = [], $limit = [], $groupBy = [], $having = []): Statement
    {
        return $this->select(...func_get_args())->prepare()->getPreparedStatement();
    }

    /**
     * 取得系クエリを実行する
     *
     * @inheritdoc Connection::executeQuery()
     */
    public function executeSelect(string $query, iterable $params = [], ?int $ttl = 0)
    {
        $params = Adhoc::bindableParameters($params);

        // コンテキストを戻すための try～catch
        try {
            $result = null;
            $cache = Adhoc::cacheByHash($this->getUnsafeOption('cacheProvider'), "$query:" . json_encode($params), function () use ($query, $params, $ttl, &$result) {
                $result = $this->getSlaveConnection()->executeQuery($query, $params, Adhoc::bindableTypes($params));
                if ($ttl === 0) {
                    return null;
                }
                $cconnection = $this->getCompatibleConnection($this->getSlaveConnection());
                $cache = [
                    'meta' => $cconnection->getMetadata($result),
                    'data' => $result->fetchAllNumeric(),
                ];
                $result->free();
                return $cache;
            }, $ttl);
            if ($cache) {
                return new Result(new ArrayResult($cache['data'], $cache['meta']), $this->getSlaveConnection());
            }
            return $result;
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
    public function executeAffect(string $query, iterable $params = [], ?int $retry = null)
    {
        $bare_params = $params;
        $params = Adhoc::bindableParameters($params);

        if ($this->getUnsafeOption('dryrun')) {
            return $this->queryInto($query, $params);
        }

        if ($this->getUnsafeOption('preparing')) {
            return new Statement($query, $params, $this);
        }

        // コンテキストを戻すための try～catch
        try {
            $retry ??= $this->getUnsafeOption('defaultRetry');
            RETRY:
            try {
                $this->affectedRows = null;
                $this->affectedRows = $this->getMasterConnection()->executeStatement($query, $params, Adhoc::bindableTypes($params));
            }
            catch (\Exception $ex) {
                if ($retry-- > 0) {
                    $wait = null;
                    // id や uk がクロージャで、値が毎回変わることもある
                    if ($ex instanceof UniqueConstraintViolationException) {
                        $wait = 0.1;
                    }
                    // それ以外は doctrine に任せる
                    if ($ex instanceof RetryableException) {
                        // …としたいんだけど、今の実装は Deadlock と LockWaitTimeout が対象になっている
                        // Deadlock は例えば mysql で暗黙のロールバックが走るので勝手にリトライするわけにはいかない
                        // LockWaitTimeout は単純リトライで大丈夫だろうがリトライ間隔を見積もることができない
                        // トランザクションを見て分岐でもいいけど結構クリティカルになりがちなので安全側に倒してスルー実装としている
                        assert($ex); // @codeCoverageIgnore
                    }
                    if (isset($wait)) {
                        usleep($wait * 1000 * 1000);
                        $params = Adhoc::bindableParameters($bare_params);
                        goto RETRY;
                    }
                }

                throw $ex;
            }
            // 利便性のため $this->affectedRows には代入しない（こうしておくと mysqli においてマッチ行と変更行が得られる）
            return $this->getCompatibleConnection($this->getMasterConnection())->alternateMatchedRows() ?? $this->affectedRows;
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
     */
    public function executeSelectAsync(string $query, iterable $params = []): \Closure
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
     */
    public function executeAffectAsync(string $query, iterable $params = []): \Closure
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
     */
    public function executeAsync(array $queries, ?Connection $connection = null): object|callable
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
                    if (array_depth($params, 2) === 1) {
                        $params = [$params];
                    }
                    foreach ($params as $param) {
                        yield $query => $param;
                    }
                }
            })($queries);
        }

        return $this->getCompatibleConnection($connection)->executeAsync($queries, $this->_getConverter(null), $this->affectedRows);
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
    public function getEmptyRecord(string $tablename, $default = [])
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
     */
    public function migrate(string $tableName, string $dml, array|string $recordsOrFilename, array $opt = []): int|array
    {
        $opt += [
            'dryrun' => true,
            'ignore' => false,
            'bulk'   => false,
            'chunk'  => null,
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
     */
    public function import(array $datatree): int
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
     * @return int|string[]|Statement 基本的には affected row. dryrun 中は文字列配列、preparing 中は Statement
     */
    public function loadCsv(string|array $tableName, string $filename, array $options = [])
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

            $builder = $this->createAffectBuilder();
            $builder->build(['table' => $tableName]);
            $affecteds = [];
            foreach (iterator_chunk($file, $this->_getChunk() ?? PHP_INT_MAX, true) as $rows) {
                $builder->loadCsv(null, $column, $rows, $options);

                $affecteds[] = $builder->execute();
            }

            return $this->_postaffects($builder, $affecteds);
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
     * @param string|TableDescriptor $tableName テーブル名
     * @param string|SelectBuilder $sql SELECT クエリ
     * @param array $columns カラム定義
     * @param iterable $params bind パラメータ
     * @return int|string[]|Statement 基本的には affected row. dryrun 中は文字列配列、preparing 中は Statement
     */
    public function insertSelect($tableName, $sql, $columns = [], array $params = [])
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 5 ? func_get_arg(4) : [];

        if ($sql instanceof SelectBuilder) {
            $sql->detectAutoOrder(true);
            $sql->merge($params);
            $sql = (string) $sql;
        }

        $builder = $this->createAffectBuilder();
        $builder->build([
            'table'  => $tableName,
            'column' => $columns,
            'select' => $sql,
        ]);
        $builder->insertSelect(opt: $opt)->merge($params);

        $affected = $this->executeAffect($builder->getQuery(), $params);
        $affecteds = [$affected];

        return $this->_postaffects($builder, $affecteds);
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
     * @used-by insertArrayOrThrow()
     *
     * @param string|TableDescriptor $tableName テーブル名
     * @param array|\Generator $data カラムデータ配列あるいは Generator
     * @return int|array|string[]|Statement 基本的には affected row. 引数次第では主キー配列. dryrun 中は文字列配列、preparing 中は Statement
     */
    public function insertArray($tableName, $data)
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 3 ? func_get_arg(2) : [];

        $builder = $this->createAffectBuilder();
        $builder->build([
            'table' => $tableName,
        ]);

        $schema = $this->getSchema();
        $autocol = $schema->getTableAutoIncrement($builder->getTable());
        $autocolname = $autocol?->getName();

        // 主キー返しに対応しているのはオートインクリメントのみ
        $orPkThrow = array_get($opt, 'primary') === 1;
        if ($orPkThrow && $autocol === null) {
            throw new \UnexpectedValueException('insertArrayOrThrow supports only autoincrement table');
        }

        $pkvals = [];
        $affecteds = [];
        foreach (iterator_chunk($data, $this->_getChunk() ?? PHP_INT_MAX) as $rows) {
            $builder->build([
                'values' => $rows,
            ]);
            $builder->insertArray(opt: $opt);

            $affecteds[] = $builder->execute();

            if ($orPkThrow) {
                foreach ($builder->getValues() as $row) {
                    if (isset($row[$autocolname])) {
                        $pkvals[] = $row[$autocolname];
                    }
                }
            }
        }

        $affected = $this->_postaffects($builder, $affecteds);
        if (!$orPkThrow || !is_int($affected)) {
            return $affected;
        }

        // ignore や不測の事態で挿入されてない行があると破綻する（ので OrThrow のみの対応としている）
        if ($affected === 0) {
            throw new NonAffectedException('affected row is nothing.');
        }

        // 指定されているならそれを取ればいい
        $pkeyselect = $this->select([$builder->getTable() => [$autocolname]], [$autocolname => $pkvals]);
        // 自動採番なら後ろから（作用行 - 指定行）件を取れば追加されたやつのはず（ピッタリだと limit 0 になってエラーになるので if 分岐）
        if ($limit = ($affected - count($pkvals))) {
            $lastselect = $this->select([$builder->getTable() => [$autocolname]], ["$autocolname:!" => $pkvals], [$autocolname => false], $limit);
            // SQLITE が UNION の括弧をサポートしていないので ORDER と LIMIT が全体に掛かってしまう
            // どうしようもないので2回に分ける（条件演算子で1行化してるのはカバレッジのため）
            return $this->getCompatiblePlatform()->supportsUnionParentheses() ? $this->unionAll([$pkeyselect, $lastselect])->array() : array_merge($pkeyselect->array(), $lastselect->array());
        }
        return $pkeyselect->array();
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
     * したがって $where を指定するのは「`status_cd = 50` のもののみ」などといった「前提となるような条件」を書く。
     *
     * @used-by updateArrayIgnore()
     *
     * @param string|TableDescriptor $tableName テーブル名
     * @param array|\Generator $data カラムデータ配列あるいは Generator
     * @param array|mixed $where 束縛条件
     * @return int|string[]|Statement 基本的には affected row. dryrun 中は文字列配列、preparing 中は Statement
     */
    public function updateArray($tableName, $data, $where = [])
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 4 ? func_get_arg(3) : [];

        $builder = $this->createAffectBuilder();
        $builder->build([
            'table' => $tableName,
            'where' => $where,
        ]);

        $affecteds = [];
        foreach (iterator_chunk($data, $this->_getChunk() ?? PHP_INT_MAX) as $rows) {
            $builder->build([
                'values' => $rows,
                'column' => [], // 可変なので指定しない
            ]);
            $builder->updateArray(opt: $opt);

            $affecteds[] = $builder->execute();
        }

        return $this->_postaffects($builder, $affecteds);
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
     * # $updateData に ['*' => ?callable] を渡すと $updateData に無いカラムが $insertData を元にコールバックされる（大抵は null を渡せば事足りる。modify の example も参照）
     * $db->modifyArray('tablename', [
     *     ['id' => 1, 'name' => 'hoge'],
     *     ['id' => 2, 'name' => 'fuga'],
     *     ['id' => 3, 'name' => 'piyo'],
     * ], ['*' => null, 'data' => 'XXX']);
     * // INSERT INTO tablename (id, name) VALUES
     * //   ('1', 'hoge'),
     * //   ('2', 'fuga'),
     * //   ('3', 'piyo')
     * // ON DUPLICATE KEY UPDATE
     * //   id = VALUES(id),
     * //   name = VALUES(name),
     * //   data = 'XXX'
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
     * @param string|TableDescriptor $tableName テーブル名
     * @param array|\Generator $insertData カラムデータ配列あるいは Generator
     * @param array $updateData カラムデータ
     * @param string $uniquekey 重複チェックに使うユニークキー名
     * @return int|string[]|Statement 基本的には affected row. dryrun 中は文字列配列、preparing 中は Statement
     */
    public function modifyArray($tableName, $insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 5 ? func_get_arg(4) : [];

        $cplatform = $this->getCompatiblePlatform();
        if (!$cplatform->supportsBulkMerge()) {
            throw new \DomainException($cplatform->getName() . ' is not support modifyArray.');
        }

        $builder = $this->createAffectBuilder();
        $builder->build([
            'table'      => $tableName,
            'constraint' => $uniquekey,
        ]);

        $affecteds = [];
        foreach (iterator_chunk($insertData, $this->_getChunk() ?? PHP_INT_MAX) as $rows) {
            $builder->build([
                'values' => $rows,
                'merge'  => $updateData,
            ]);
            $builder->modifyArray(opt: $opt);

            $affecteds[] = $builder->execute();
        }

        return $this->_postaffects($builder, $affecteds);
    }

    /**
     * DELETE+INSERT+UPDATE を同時に行う
     *
     * テーブル状態を指定した配列・条件に「持っていく」メソッドとも言える。
     *
     * このメソッドは複数のステートメントが実行され、 prepare を使うことが出来ない。
     * また、可能な限りクエリを少なくかつ効率的に実行されるように構築されるので、テーブル定義や与えたデータによってはまったく構成の異なるクエリになる可能性がある（結果は同じになるが）。
     * 具体的には
     *
     * - BULK MERGE をサポートしていてカラムが完全に共通の場合：     delete + modifyArray(単一)的な動作（最も高速）
     * - BULK MERGE をサポートしていてカラムがそれなりに共通の場合： delete + modifyArray(複数)的な動作（比較的高速）
     * - merge をサポートしていてカラムが完全に共通の場合：          delete + prepareModify(単一)的な動作（標準速度）
     * - merge をサポートしていてカラムがそれなりに共通の場合：      delete + prepareModify(複数)的な動作（比較的低速）
     * - merge をサポートしていてカラムがバラバラだった場合：        delete + 各行 modify 的な動作（最も低速）
     * - merge をサポートしていなくてカラムがバラバラだった場合：    delete + 各行 select + 各行 insert/update 的な動作（最悪）
     *
     * という動作になる。
     *
     * 返り値は `[primaryKeys]` となり「その世界における主キー配列」を返す。
     *
     * $returning を渡すと 主キーをキーとしたレコード配列を返すようになる。
     * レコード配列には空文字キーで下記の値が自動で設定される。
     * - 1: レコードが作成された
     * - -1: レコードが削除された
     * - 2: レコードが更新された
     * - 0: 更新対象だが更新されなかった（mysql のみ）
     * 返ってくるレコードのソースは下記のとおりである。
     * - INSERT: 作成した後のレコード（元がないのだから新しいレコードしか返し得ない）
     * - DELETE: 削除する前のレコード（削除したのだから元のレコードしか返し得ない）
     * - UPDATE: 更新する前のレコード（「更新する値」は手元にあるわけなので更新前の値の方が有用度が高いため）
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
     * // DELETE FROM table_name WHERE (category = 'misc') AND (NOT (id IN ('1', '2', '3')))
     *
     * // BULK MERGE をサポートしている場合、下記がカラムの種類ごとに発行される
     * // INSERT INTO table_name (id, name) VALUES
     * //   ('1', 'hoge'),
     * //   ('2', 'fuga'),
     * //   ('3', 'piyo')
     * // ON DUPLICATE KEY UPDATE
     * //   id = VALUES(id),
     * //   name = VALUES(name)
     * //
     * // merge をサポートしている場合、下記がカラムの種類ごとに発行される（merge は疑似クエリ）
     * // [prepare] INSERT INTO table_name (id, name) VALUES (?, ?) ON UPDATE id = VALUES(id), name = VALUES(name)
     * // [execute] INSERT INTO table_name (id, name) VALUES (1, 'hoge') ON UPDATE id = VALUES(id), name = VALUES(name)
     * // [execute] INSERT INTO table_name (id, name) VALUES (2, 'fuga') ON UPDATE id = VALUES(id), name = VALUES(name)
     * // [execute] INSERT INTO table_name (id, name) VALUES (3, 'piyo') ON UPDATE id = VALUES(id), name = VALUES(name)
     * //
     * // merge をサポートしていない場合、全行分 select して行が無ければ insert, 行が有れば update する
     * // SELECT EXISTS (SELECT * FROM table_name WHERE id = '1')
     * // UPDATE table_name SET name = 'hoge' WHERE id = '1'
     * // SELECT EXISTS (SELECT * FROM table_name WHERE id = '2')
     * // UPDATE table_name SET name = 'fuga' WHERE id = '2'
     * // SELECT EXISTS (SELECT * FROM table_name WHERE id = '3')
     * // INSERT INTO table_name (id, name) VALUES ('3', 'piyo')
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
     * @param string|TableDescriptor $tableName テーブル名
     * @param array $dataarray カラムデータ配列あるいは Generator
     * @param array|mixed $where 束縛条件。 false を与えると DELETE 文自体を発行しない（速度向上と安全担保）
     * @param string $uniquekey 重複チェックに使うユニークキー名
     * @param ?array $returning 返り値の制御変数。配列を与えるとそのカラムの SELECT 結果を返す（null は主キーを表す）
     * @return array 基本的には主キー配列. dryrun 中は SQL をネストして返す
     */
    public function changeArray($tableName, $dataarray, $where, $uniquekey = 'PRIMARY', $returning = [])
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 6 ? func_get_arg(5) : [];
        unset($opt['primary']); // 自身で処理するので不要

        $builder = $this->createAffectBuilder();
        $builder->build([
            'table'  => $tableName,
            'where'  => $where,
            'values' => $dataarray,
            'column' => [], // 可変なので指定しない
        ]);

        $dryrun = $this->getUnsafeOption('dryrun');
        $cplatform = $this->getCompatiblePlatform();

        // 主キー情報を漁っておく
        $pcols = $this->getSchema()->getTableUniqueColumns($builder->getTable(), $uniquekey);
        $plist = array_keys($pcols);
        $autocolumn = null;
        if ($uniquekey === 'PRIMARY') {
            $autocolumn = $this->getSchema()->getTableAutoIncrement($builder->getTable())?->getName();
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
        $preparable = !$dryrun && array_get($opt, 'prepare', true) && $cplatform->supportsMerge() && !$this->getCompatibleConnection()->isEmulationMode();

        // カラムの種類でグルーピングする
        $primaries = [];
        $col_group = [];
        foreach ($builder->getValues() as $n => $row) {
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
            $pkcol = $cplatform->getConcatExpression(...array_values(array_implode($plist, $this->quote($pksep))));
            if ($where !== false) {
                $oldrecords = $this->selectAssoc([$builder->getTable() => [self::AUTO_PRIMARY_KEY => $pkcol], '' => $returning], $builder->getWhere());
            }
        }

        $sqls = [];
        $idmap = [];
        $inserteds = [];

        // 主キー外を削除（$cond を queryInto してるのは誤差レベルではなく速度に差が出るから）
        if ($where !== false) {
            $delete_ids = array_filter($primaries, fn($pkval) => count(array_filter($pkval, fn($v) => $v !== null)) === count($pcols));
            $cond = $cplatform->getPrimaryCondition($delete_ids, $builder->getTable());
            $sqls[] = $this->delete($builder->getTable(), array_merge($builder->getWhere(), $delete_ids ? [$this->queryInto("NOT ($cond)", $cond->getParams())] : []));
        }

        foreach ($col_group as $group) {
            if ($group['bulks'] ?? []) {
                $sqls[] = $this->modifyArray($builder->getTable(), $group['bulks'], [], $uniquekey, null, $opt);
            }
            if ($group['rows'] ?? []) {
                // 2件以上じゃないとプリペアの旨味が少ない
                $stmt = null;
                if ($preparable && count($group['rows']) > 1) {
                    $stmt = $this->prepare()->modify($builder->getTable(), array_map(fn($c) => ":$c", $group['cols']), [], $uniquekey, $opt);
                }
                foreach ($group['rows'] as $n => $row) {
                    if ($stmt) {
                        $affected = $sqls[] = $stmt->executeAffect($row);
                    }
                    else {
                        $affected = $sqls[] = $this->modify($builder->getTable(), $row, [], $uniquekey, $opt);
                    }

                    if ($autocolumn !== null && !isset($primaries[$n][$autocolumn])) {
                        $primaries[$n][$autocolumn] = $this->getLastInsertId($builder->getTable(), $autocolumn);
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
                $cond = $cplatform->getPrimaryCondition($inserteds, $builder->getTable());
                $newrecords = $this->selectAssoc([$builder->getTable() => [self::AUTO_PRIMARY_KEY => $pkcol], '' => $returning], [
                    [
                        $builder->getWhere(),
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
     * 各行の method キーに応じた処理を行う
     *
     * changeArray のメソッド明示版のようなもの。
     * 各行に "@method" のようなものを潜ませるとそのメソッドで各行を操作する。
     * 原則的にDML3兄弟（INSERT,UPDATE,DELETE）のみのサポート（他のメソッドも呼べるようにしてあるが非互換）。
     *
     * ```php
     * $db->affectArray('table_name', [
     *     ['@method' => 'insert', 'id' => 1, 'name' => 'hoge'], // 特に変わったことはない普通の INSERT
     *     ['@method' => 'update', 'id' => 2, 'name' => 'fuga'], // 主キーとデータを分離して UPDATE
     *     ['@method' => 'delete', 'id' => 3, 'name' => 'piyo'], // 主キーのみ有効で他は無視して DELETE
     * ]);
     * // DELETE FROM table_name WHERE (id = 3)
     * // UPDATE table_name SET name = 'fuga' WHERE (id = 2)
     * // INSERT INTO tablename (id, name) VALUES ('1', 'hoge')
     * ```
     *
     * @used-by affectArrayIgnore()
     *
     * @param string $tableName テーブル名
     * @param array $dataarray カラムデータ配列あるいは Generator
     * @return array 基本的には主キー配列. dryrun 中は SQL をネストして返す
     */
    public function affectArray($tableName, $dataarray)
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 3 ? func_get_arg(2) : [];
        $opt['method_key'] = '@method';

        $builder = $this->createAffectBuilder();
        $builder->build([
            'table' => $tableName,
        ]);

        // 処理順は下記で固定とする（概して delete 系は制約違反が少なく、insert 系が制約違反になりやすい）
        $affects = [
            'destroy' => [],
            'remove'  => [],
            'delete'  => [],
            'upgrade' => [], // 実質的な意味はなし（主キーの更新は連想配列の都合上実現できない）
            'revise'  => [], // 実質的な意味はなし（主キーの更新は連想配列の都合上実現できない）
            'update'  => [],
            'upsert'  => [],
            'modify'  => [],
            'replace' => [],
            'invalid' => [],
            'insert'  => [],
        ];
        foreach ($dataarray as $n => $row) {
            $method = $row[$opt['method_key']] ?? '';
            unset($row[$opt['method_key']]);
            if (!isset($affects[$method])) {
                throw new \InvalidArgumentException("$method is invalid($n th)");
            }

            $primary = $rowdata = [];

            $pkcols = $this->getSchema()->getTablePrimaryColumns($builder->getTable());
            foreach ($row as $col => $value) {
                if (array_key_exists($col, $pkcols)) {
                    $primary[$col] = $value;
                }
                else {
                    $rowdata[$col] = $value;
                }
            }
            if ($primary && count($pkcols) !== count($primary)) {
                throw new \UnexpectedValueException(sprintf("primary data mismatch(%s.%s != %s)", $this->table, json_encode(array_keys($pkcols)), json_encode($primary)));
            }

            $args = match ($method) {
                'update', 'revise', 'upgrade' => [$rowdata, $primary + $builder->getWhere()],
                'delete', 'remove', 'destroy' => [$primary + $builder->getWhere()],
                'invalid'                     => [$primary + $builder->getWhere(), $rowdata],
                default                       => [$row],
            };

            $affects[$method][$n] = [$builder->getTable(), ...$args];
        }

        $dryrunning = $this->getUnsafeOption('dryrun');

        $results = [];
        foreach ($affects as $method => $rows) {
            foreach ($rows as $n => $args) {
                $arguments = parameter_default([$this, $method], $args);
                $arguments[] = ['primary' => 3] + $opt;

                /** @var int|string $n */
                $results[$n] = $this->$method(...$arguments);
                if (!$dryrunning) {
                    $results[$n][''] = $this->getAffectedRows();
                }
            }
        }

        if ($dryrunning) {
            return array_flatten($results);
        }
        return $results;
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
                    array_set($result, $pkval, preg_split($SEPARATOR_REGEX, $id));
                }
            }
        }
        if ($dryrun) {
            return $sqls;
        }

        return $single_mode ? $result[$tableName][0] : $result[$tableName];
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
     * # TableDescriptor を渡すとカラムとデータを別に与えられる
     * $db->insert('tablename.name', 'hoge');
     * // INSERT INTO tablename (name) VALUES ('hoge')
     * $db->insert('tablename.id, name', ['2', 'hoge']);
     * // INSERT INTO tablename (id, name) VALUES ('2', 'hoge')
     *
     * # TableDescriptor で where を与えると条件付き INSERT になる
     * $db->insert('tablename.name[id:3]', ['name' => 'hoge']);
     * // INSERT INTO tablename (name) SELECT 'zzz' WHERE (NOT EXISTS (SELECT * FROM test WHERE id = '3'))
     * ```
     *
     * @used-by insertOrThrow()
     * @used-by insertAndPrimary()
     * @used-by insertIgnore()
     *
     * @param string|TableDescriptor $tableName テーブル名
     * @param mixed $data INSERT データ配列
     * @return int|array|string[]|Statement 基本的には affected row. 引数次第では主キー配列. dryrun 中は文字列配列、preparing 中は Statement
     */
    public function insert($tableName, $data)
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 3 ? func_get_arg(2) : [];

        $builder = $this->createAffectBuilder();
        $builder->insert($tableName, $data, opt: $opt);

        $unseter = $this->_setIdentityInsert($builder->getTable(), $builder->getSet());
        try {
            $builder->execute();
            return $this->_postaffect($builder, $opt);
        }
        finally {
            $unseter();
        }
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
     * # TableDescriptor を渡すとカラムとデータを別に与えたり条件を埋め込むことができる
     * $db->update('tablename[id: 2].name', 'hoge');
     * // UPDATE tablename SET name = 'hoge' WHERE id = '2'
     * ```
     *
     * @used-by updateOrThrow()
     * @used-by updateAndPrimary()
     * @used-by updateIgnore()
     *
     * @param string|TableDescriptor $tableName テーブル名
     * @param mixed $data UPDATE データ配列
     * @param array|mixed $where WHERE 条件
     * @return int|array|string[]|Statement 基本的には affected row. 引数次第では主キー配列. dryrun 中は文字列配列、preparing 中は Statement
     */
    public function update($tableName, $data, $where = [])
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 4 ? func_get_arg(3) : [];

        $builder = $this->createAffectBuilder();
        $builder->update($tableName, $data, $where, opt: $opt);

        $affecteds = [];

        $schema = $this->getSchema();
        $fkeys = $schema->getForeignKeys($builder->getTable(), null);
        foreach ($fkeys as $fkey) {
            // 仮想でない通常の外部キーであれば RDBMS 側で同期してくれるが、仮想外部キーは能動的に実行する必要がある
            $fkopt = $fkey->getOptions();
            if (($fkopt['virtual'] ?? false) && ($fkopt['emulatable'] ?? true) && ($fkopt['enable'] ?? true)) {
                $ltable = first_key($schema->getForeignTable($fkey));

                $onUpdate = $fkey->onUpdate();
                if ($onUpdate === null) {
                    if ($this->exists($ltable, $builder->cascadeWheres($fkey))) {
                        $this->_throwForeignKeyConstraintViolationException($fkey);
                    }
                }
                else {
                    if ($onUpdate === 'CASCADE') {
                        $subdata = $builder->cascadeValues($fkey);
                        if ($subdata) {
                            $affecteds = array_merge($affecteds, arrayize($this->update($ltable, $subdata, $builder->cascadeWheres($fkey))));
                        }
                    }
                    if ($onUpdate === 'SET NULL') {
                        $affecteds = array_merge($affecteds, arrayize($this->update($ltable, array_fill_keys($fkey->getLocalColumns(), null), $builder->cascadeWheres($fkey))));
                    }
                }
            }
        }

        $affected = $builder->execute();
        if ($this->getUnsafeOption('dryrun')) {
            return array_merge($affecteds, arrayize($affected));
        }
        return $this->_postaffect($builder, $opt);
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
     * # TableDescriptor を渡すと条件を埋め込むことができる
     * $db->delete('tablename[id: 2]');
     * // DELETE FROM tablename WHERE id = '2'
     * ```
     *
     * @used-by deleteOrThrow()
     * @used-by deleteAndPrimary()
     * @used-by deleteIgnore()
     *
     * @param string|TableDescriptor $tableName テーブル名
     * @param array|mixed $where WHERE 条件
     * @return int|array|string[]|Statement 基本的には affected row. 引数次第では主キー配列. dryrun 中は文字列配列、preparing 中は Statement
     */
    public function delete($tableName, $where = [])
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 3 ? func_get_arg(2) : [];

        $builder = $this->createAffectBuilder();
        $builder->delete($tableName, $where, opt: $opt);

        $affecteds = [];

        $schema = $this->getSchema();
        $fkeys = $schema->getForeignKeys($builder->getTable(), null);
        foreach ($fkeys as $fkey) {
            // 仮想でない通常の外部キーであれば RDBMS 側で同期してくれるが、仮想外部キーは能動的に実行する必要がある
            $fkopt = $fkey->getOptions();
            if (($fkopt['virtual'] ?? false) && ($fkopt['emulatable'] ?? true) && ($fkopt['enable'] ?? true)) {
                $ltable = first_key($schema->getForeignTable($fkey));
                $subwhere = $builder->cascadeWheres($fkey);

                $onDelete = $fkey->onDelete();
                if ($onDelete === null) {
                    if ($this->exists($ltable, $subwhere)) {
                        $this->_throwForeignKeyConstraintViolationException($fkey);
                    }
                }
                else {
                    if ($onDelete === 'CASCADE') {
                        $affecteds = array_merge($affecteds, arrayize($this->delete($ltable, $subwhere)));
                    }
                    if ($onDelete === 'SET NULL') {
                        $affecteds = array_merge($affecteds, arrayize($this->update($ltable, array_fill_keys($fkey->getLocalColumns(), null), $subwhere)));
                    }
                }
            }
        }

        $affected = $builder->execute();
        if ($this->getUnsafeOption('dryrun')) {
            return array_merge($affecteds, arrayize($affected));
        }
        return $this->_postaffect($builder, $opt);
    }

    /**
     * 論理削除構文
     *
     * 指定された論理削除カラムを更新する。
     * 簡単に言えば「単に指定カラムを更新する update メソッド」に近いが、下記のように外部キーを見て無効化が伝播する。
     *
     * - RESTRICT/NO ACTION: 紐づく子テーブルレコードが無効でない場合、例外を投げる
     * - CASCADE: 紐づく子テーブルレコードも無効にする
     *
     * 「無効化」の定義は $invalid_columns で与えるカラムで決まる。
     * $invalid_columns = ['delete_at' => fn() => date('Y-m-d H:i:s')] のように指定すれば delete_at が現在日時で UPDATE される。
     * $invalid_columns は全テーブルで共通であるが、null を与えると TableGateway::invalidColumn() -> defaultInvalidColumn オプション の優先順位で自動指定される。
     *
     * 「無効」の定義は「指定カラムが NULL でない」こと（指定カラムが NULL = 有効状態）。
     * いわゆるフラグで delete_flg=0 としても無効であることに留意。
     *
     * 相互参照外部キーでかつそれらが共に「RESTRICT/NO ACTION」だと無限ループになるので注意。
     * （そのような外部キーはおかしいと思うので特にチェックしない）。
     *
     * ```php
     * # childtable -> parenttable に CASCADE な外部キーがある場合
     * $db->invalid('parenttable', ['parent_id' => 1], ['delete_at' => date('Y-m-d H:i:s')]);
     * // UPDATE childtable  SET delete_at = '2014-12-24 12:34:56' WHERE (parent_id) IN (SELECT parenttable.parent_id FROM parenttable WHERE parenttable.parent_id = 1)
     * // UPDATE parenttable SET delete_at = '2014-12-24 12:34:56' WHERE parenttable.parent_id = 1
     * ```
     *
     * @used-by invalidOrThrow()
     * @used-by invalidAndPrimary()
     * @used-by invalidIgnore()
     *
     * @param string|TableDescriptor $tableName テーブル名
     * @param array|mixed $where WHERE 条件
     * @param ?array $invalid_columns 無効カラム値
     * @return int|array|string[]|Statement 基本的には affected row. 引数次第では主キー配列. dryrun 中は文字列配列、preparing 中は Statement
     */
    public function invalid($tableName, $where, $invalid_columns = null)
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 4 ? func_get_arg(3) : [];

        $builder = $this->createAffectBuilder();
        $builder->build(['table' => $tableName, 'where' => $where]);

        $invalid_columns ??= $this->{$builder->getTable()}->invalidColumn();
        $invalid_columns ??= $this->getUnsafeOption('defaultInvalidColumn');
        assert(!empty($invalid_columns));

        $affecteds = [];
        $schema = $this->getSchema();
        $fkeys = $schema->getForeignKeys($builder->getTable(), null);
        uasort($fkeys, fn($a, $b) => ($a->onDelete() ? 1 : 0) <=> ($b->onDelete() ? 1 : 0));
        foreach ($fkeys as $fkey) {
            // UPDATE/DELETE と違い、INVALID という DML は存在せず、アプリケーション実装確定なので virtual, emulatable は見なくてよい
            $fkopt = $fkey->getOptions();
            if (($fkopt['enable'] ?? true)) {
                $ltable = first_key($schema->getForeignTable($fkey));
                $lcolumns = array_intersect_key($invalid_columns, $schema->getTableColumns($ltable));
                if (!$lcolumns) {
                    continue;
                }
                $subwhere = $builder->cascadeWheres($fkey);

                if ($fkey->onDelete() === null) {
                    $invalid_where = array_map(fn($column) => $this->raw("$column IS NULL"), array_keys($lcolumns));
                    if ($this->exists($ltable, array_merge($subwhere, $invalid_where))) {
                        $this->_throwForeignKeyConstraintViolationException($fkey);
                    }
                }
                else {
                    $affecteds = array_merge($affecteds, arrayize($this->invalid($ltable, $subwhere, $lcolumns, array_remove($opt, ['primary']))));
                }
            }
        }

        $affected = $this->update($builder->getTable(), $invalid_columns, $builder->getWhere(), $opt);
        if ($this->getUnsafeOption('dryrun')) {
            return array_merge($affecteds, arrayize($affected));
        }
        return $affected;
    }

    /**
     * UPDATE 構文（RESTRICT/NO ACTION を除外）
     *
     * CASCADE/SET NULL はむしろ「消えて欲しい/NULL になって欲しい」状況だと考えられるので何も手を加えない。
     * 簡単に言えば「外部キーエラーにならない**ような**」 UPDATE を実行する。
     *
     * ```php
     * # childtable -> parenttable に RESTRICT な外部キーがある場合
     * $db->revise('parenttable', ['id' => 2], ['id' => 1]);
     * // UPDATE parenttable SET id = 2 WHERE id = 1 AND (NOT EXISTS (SELECT * FROM childtable WHERE parenttable.id = childtable.parent_id))
     * ```
     *
     * @used-by reviseOrThrow()
     * @used-by reviseAndPrimary()
     * @used-by reviseIgnore()
     *
     * @param string|TableDescriptor $tableName テーブル名
     * @param mixed $data UPDATE データ配列
     * @param array|mixed $where WHERE 条件
     * @return int|array|string[]|Statement 基本的には affected row. 引数次第では主キー配列. dryrun 中は文字列配列、preparing 中は Statement
     */
    public function revise($tableName, $data, $where = [])
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 4 ? func_get_arg(3) : [];

        $builder = $this->createAffectBuilder();
        $builder->build(['table' => $tableName, 'set' => $data, 'where' => $where]);
        $builder->build([
            'where' => $builder->restrictWheres('update'),
        ], true);

        return $this->update($builder->getTable(), $builder->getSet(), $builder->getWhere(), $opt);
    }

    /**
     * UPDATE 構文（RESTRICT/NO ACTION も更新）
     *
     * RESTRICT/NO ACTION な子テーブルレコードを先に更新してから実行する。
     * 簡単に言えば「外部キーエラーにならないように**してから**」 UPDATE を実行する。
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
     * $db->upgrade('parenttable', ['pk' => 2], ['pk' => 1]);
     * // UPDATE childtable SET fk = 2 WHERE (cid) IN (parenttable id FROM parenttable WHERE pk = 1)
     * // UPDATE FROM parenttable SET pk = 2 WHERE pk = 1
     * ```
     *
     * @used-by upgradeOrThrow()
     * @used-by upgradeAndPrimary()
     * @used-by upgradeIgnore()
     *
     * @param string|TableDescriptor $tableName テーブル名
     * @param mixed $data UPDATE データ配列
     * @param array|mixed $where WHERE 条件
     * @return int|array|string[]|Statement 基本的には affected row. 引数次第では主キー配列. dryrun 中は文字列配列、preparing 中は Statement
     */
    public function upgrade($tableName, $data, $where = [])
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 4 ? func_get_arg(3) : [];

        $builder = $this->createAffectBuilder();
        $builder->build(['table' => $tableName, 'set' => $data, 'where' => $where]);

        try {
            $affecteds = [];
            $recoveries = [];
            $schema = $this->getSchema();
            $fkeys = $schema->getForeignKeys($builder->getTable(), null);
            foreach ($fkeys as $fkey) {
                if ($fkey->onUpdate() === null) {
                    $subdata = $builder->cascadeValues($fkey);
                    if (!$subdata) {
                        continue;
                    }
                    // 外部キー無効を戻した瞬間に結果整合性でエラーになる RDBMS も居るため false -> update -> true にはできない
                    // かといってトランザクションイベントまで巻き込みたくないため愚直に UPDATE 後（整合後）に戻す（ために覚えておく）
                    $this->switchForeignKey(false, $fkey);
                    $recoveries[] = $fkey;

                    $ltable = first_key($schema->getForeignTable($fkey));
                    $subwhere = $builder->cascadeWheres($fkey);
                    $affecteds = array_merge($affecteds, arrayize($this->upgrade($ltable, $subdata, $subwhere, array_remove($opt, ['primary']))));
                }
            }

            $affected = $this->update($builder->getTable(), $builder->getSet(), $builder->getWhere(), $opt);
        }
        finally {
            foreach ($recoveries as $recovery) {
                $this->switchForeignKey(true, $recovery);
            }
        }

        if ($this->getUnsafeOption('dryrun')) {
            return array_merge($affecteds, arrayize($affected));
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
     * @used-by removeAndPrimary()
     * @used-by removeIgnore()
     *
     * @param string|TableDescriptor $tableName テーブル名
     * @param array|mixed $where WHERE 条件
     * @return int|array|string[]|Statement 基本的には affected row. 引数次第では主キー配列. dryrun 中は文字列配列、preparing 中は Statement
     */
    public function remove($tableName, $where = [])
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 3 ? func_get_arg(2) : [];

        $builder = $this->createAffectBuilder();
        $builder->build(['table' => $tableName, 'where' => $where]);
        $builder->build([
            'where' => $builder->restrictWheres('delete'),
        ], true);

        return $this->delete($builder->getTable(), $builder->getWhere(), $opt);
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
     * @used-by destroyAndPrimary()
     * @used-by destroyIgnore()
     *
     * @param string|TableDescriptor $tableName テーブル名
     * @param array|mixed $where WHERE 条件
     * @return int|array|string[]|Statement 基本的には affected row. 引数次第では主キー配列. dryrun 中は文字列配列、preparing 中は Statement
     */
    public function destroy($tableName, $where = [])
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 3 ? func_get_arg(2) : [];

        $builder = $this->createAffectBuilder();
        $builder->build(['table' => $tableName, 'where' => $where]);

        $affecteds = [];
        $schema = $this->getSchema();
        $fkeys = $schema->getForeignKeys($builder->getTable(), null);
        foreach ($fkeys as $fkey) {
            if ($fkey->onDelete() === null) {
                $ltable = first_key($schema->getForeignTable($fkey));
                $subwhere = $builder->cascadeWheres($fkey);
                $affecteds = array_merge($affecteds, arrayize($this->destroy($ltable, $subwhere, array_remove($opt, ['primary']))));
            }
        }

        $affected = $this->delete($builder->getTable(), $builder->getWhere(), $opt);
        if ($this->getUnsafeOption('dryrun')) {
            return array_merge($affecteds, arrayize($affected));
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
     * @param string|TableDescriptor $tableName テーブル名
     * @param ?int $limit 残す件数
     * @param string|array $orderBy 並び順
     * @param string|array $groupBy グルーピング条件
     * @param array|mixed $where WHERE 条件
     * @return int|array|string[]|Statement 基本的には affected row. 引数次第では主キー配列. dryrun 中は文字列配列、preparing 中は Statement
     */
    public function reduce($tableName, $limit = null, $orderBy = [], $groupBy = [], $where = [])
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 6 ? func_get_arg(5) : [];

        $builder = $this->createAffectBuilder();
        $builder->reduce($tableName, $limit, $orderBy, $groupBy, $where, $opt);

        $builder->execute();
        return $this->_postaffect($builder, $opt);
    }

    /**
     * 行が無かったら INSERT、有ったら UPDATE
     *
     * アプリレイヤーで INSERT し、エラーが起きたら UPDATE へフォールバックする。
     *
     * OrThrow 版の戻り値は「本当に更新した主キー配列」になる。
     * 下記のパターンがある。
     *
     * - insert が成功した (≒ lastInsertId を含む主キーを返す)
     * - update を行った (＝ 存在した行の主キーを返す)
     * - update で**主キーも含めて更新を行った** (＝ 存在した行の**更新後**の主キーを返す)
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
     * @used-by upsertAndPrimary()
     *
     * @param string|TableDescriptor $tableName テーブル名
     * @param mixed $insertData INSERT データ配列
     * @param mixed $updateData UPDATE データ配列
     * @return int|array|string[]|Statement 基本的には affected row. 引数次第では主キー配列. dryrun 中は文字列配列、preparing 中は Statement
     */
    public function upsert($tableName, $insertData, $updateData = [])
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 4 ? func_get_arg(3) : [];

        $builder = $this->createAffectBuilder();
        $builder->build(['table' => $tableName, 'constraint' => 'PRIMARY']);

        try {
            $builder->insert(data: $insertData, opt: $opt);

            $unseter = $this->_setIdentityInsert($builder->getTable(), $builder->getSet());
            try {
                $builder->execute();
                return $this->_postaffect($builder, $opt);
            }
            finally {
                $unseter();
            }
        }
        catch (UniqueConstraintViolationException) {
            $pkcols = $this->getSchema()->getTableUniqueColumns($builder->getTable(), $builder->getConstraint());
            $updateData = $builder->wildUpdate($updateData, $insertData, false);

            $builder->build([
                'set'   => $updateData ?: array_diff_key($builder->getSet(), $pkcols),
                'where' => array_intersect_key($builder->getSet(), $pkcols),
            ]);
            $builder->update(opt: $opt);

            $builder->execute();
            return $this->_postaffect($builder, $opt);
        }
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
     *
     * # $updateData に ['*' => ?callable] を渡すと $updateData に無いカラムが $insertData を元にコールバックされる
     * # 分かりにくいが要するに「$insertData の一部だけを書き換えて $updateData にすることができる」ということ
     * $db->modify('tablename', [
     *     'id'   => 1,
     *     'name' => 'hoge',
     *     'data' => 'on insert data',
     * ], ['*' => fn($column, $insertData) => $insertData[$column], 'data' => 'on update data']);
     * // INSERT INTO tablename SET
     * //   id = '1', name = 'hoge', data = 'on insert data'
     * // ON DUPLICATE KEY UPDATE
     * //   id = '1', name = 'hoge', data = 'on update data'
     *
     * # ただし、実際は値を直接使うのではなく参照構文（mysql の VALUES など）を使うだろうので、null を渡すと自動で使用される
     * $db->modify('tablename', [
     *     'id'   => 1,
     *     'name' => 'hoge',
     *     'data' => 'on insert data',
     * ], ['*' => null, 'data' => 'on update data']);
     * // INSERT INTO tablename SET
     * //   id = '1', name = 'hoge', data = 'on insert data'
     * // ON DUPLICATE KEY UPDATE
     * //   id = VALUES(id), name = VALUES(name), data = 'on update data'
     * ```
     *
     * @used-by modifyOrThrow()
     * @used-by modifyAndPrimary()
     * @used-by modifyIgnore()
     *
     * @param string|TableDescriptor $tableName テーブル名
     * @param mixed $insertData INSERT データ配列
     * @param mixed $updateData UPDATE データ配列
     * @param string $uniquekey 重複チェックに使うユニークキー名
     * @return int|array|string[]|Statement 基本的には affected row. 引数次第では主キー配列. dryrun 中は文字列配列、preparing 中は Statement
     */
    public function modify($tableName, $insertData, $updateData = [], $uniquekey = 'PRIMARY')
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 5 ? func_get_arg(4) : [];

        if (!$this->getCompatiblePlatform()->supportsMerge()) {
            return $this->upsert($tableName, $insertData, $updateData ?: [], $opt);
        }

        $builder = $this->createAffectBuilder();
        $builder->modify($tableName, $insertData, $updateData, $uniquekey, $opt);

        $builder->execute();
        return $this->_postaffect($builder, $opt);
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
     * @used-by replaceAndPrimary()
     *
     * @param string|TableDescriptor $tableName テーブル名
     * @param mixed $data REPLACE データ配列
     * @return int|array|string[]|Statement 基本的には affected row. 引数次第では主キー配列. dryrun 中は文字列配列、preparing 中は Statement
     */
    public function replace($tableName, $data)
    {
        // 隠し引数 $opt
        $opt = func_num_args() === 3 ? func_get_arg(2) : [];

        $builder = $this->createAffectBuilder();
        $builder->replace($tableName, $data, $opt);

        $builder->execute();
        return $this->_postaffect($builder, $opt);
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
     * @param string|TableDescriptor $tableName テーブル名
     * @param array $overrideData selectしたデータを上書きするデータ
     * @param array|mixed $where 検索条件
     * @param ?string $sourceTable 元となるテーブル名。省略すると $targetTable と同じになる
     * @return int|string[]|Statement 基本的には affected row. dryrun 中は文字列配列、preparing 中は Statement
     */
    public function duplicate($tableName, array $overrideData = [], $where = [], $sourceTable = null)
    {
        $builder = $this->createAffectBuilder();
        $builder->duplicate($tableName, $overrideData, $where, $sourceTable);

        $unseter = $this->_setIdentityInsert($builder->getTable(), $builder->getSet());
        try {
            return $builder->execute();
        }
        finally {
            $unseter();
        }
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
     * @return int|string[]|Statement 基本的には affected row. dryrun 中は文字列配列、preparing 中は Statement
     */
    public function truncate($tableName, $cascade = false)
    {
        $opt = ['cascade' => $cascade];

        $builder = $this->createAffectBuilder();
        $builder->truncate($tableName, $opt);

        $builder->execute();
        return $this->_postaffect($builder, $opt);
    }

    /**
     * TRUNCATE 構文（子テーブルも削除）
     *
     * ```php
     * $db->eliminate('parenttable');
     * // TRUNCATE childtable
     * // TRUNCATE parenttable
     * ```
     *
     * @param string $tableName テーブル名
     * @return int|string[]|Statement 基本的には affected row. dryrun 中は文字列配列、preparing 中は Statement
     */
    public function eliminate($tableName)
    {
        $schema = $this->getSchema();

        $builder = $this->createAffectBuilder();
        $builder->build(['table' => $tableName]);

        $relations = $schema->getForeignKeys($builder->getTable(), null);

        $affecteds = [];

        foreach ($relations as $fkey) {
            $ltable = first_key($schema->getForeignTable($fkey));
            $affecteds = array_merge($affecteds, arrayize($this->eliminate($ltable)));
        }

        foreach ($relations as $fkey) {
            $this->switchForeignKey(false, $fkey);
        }
        try {
            $affecteds = array_merge($affecteds, arrayize($this->truncate($builder->getTable())));
        }
        finally {
            foreach ($relations as $fkey) {
                $this->switchForeignKey(true, $fkey);
            }
        }

        return $this->_postaffects($builder, $affecteds);
    }

    /**
     * 最後に挿入した ID を返す
     *
     * dryrun 中は max+1 から始まる連番を返す。
     */
    public function getLastInsertId(?string $tableName = null, ?string $columnName = null): null|int|string
    {
        if ($this->getUnsafeOption('dryrun')) {
            $key = "$tableName.$columnName";
            $this->lastInsertIds[$key] = ($this->lastInsertIds[$key] ?? $this->max([$tableName => $columnName])) + 1;
            return $this->lastInsertIds[$key];
        }
        $id = $this->getMasterConnection()->lastInsertId($this->getCompatiblePlatform()->getIdentitySequenceName($tableName, $columnName));
        return $id === false ? null : $id;
    }

    /**
     * 自動採番列をリセットする
     *
     * $seq に null を与えるとMAX+1になる。
     */
    public function resetAutoIncrement(string $tableName, ?int $seq = 1)
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
     */
    public function getAffectedRows(): ?int
    {
        return $this->affectedRows;
    }

    /**
     * キャッシュの破棄と再生成
     *
     * デプロイの直後等にこのメソッドを呼べば全キャッシュが生成される。
     */
    public function recache(bool $force = true): static
    {
        // Database 以外でも getCacheProvider を使っている箇所があるので触るときは精査
        // 内部キャッシュなどは未考慮で良い。あくまでリクエストをまたぐキャッシュの暖機

        $cacher = $this->getCacheProvider();
        if ($force) {
            $cacher->clear();
        }

        $schema = $this->getSchema();

        // テーブル名とオブジェクト
        foreach ($schema->getTableNames() as $table_name) {
            // Table オブジェクト
            $schema->getTable($table_name);

            // Gateway オブジェクト（select は特別な意味はないが、呼ぶことでカラムが温まるかもしれない程度）
            $this->$table_name->select();
        }

        // 外部キーとリレーション
        foreach ($schema->getForeignKeys() as $fkey_name => $fkey) {
            // 駆動表,結合表,外部キー指定有無の全パターン
            [$ltable, $ftable] = first_keyvalue($schema->getForeignTable($fkey));
            foreach ([
                [$ltable, $ftable, null],
                [$ltable, $ftable, $fkey_name],
                [$ftable, $ltable, null],
                [$ftable, $ltable, $fkey_name],
            ] as $args) {
                try {
                    $schema->getForeignColumns(...$args);
                }
                catch (\Throwable) {
                    // through
                }
            }
        }

        return $this;
    }

    /**
     * 初期状態に戻す
     *
     * このメソッドはテスト用なので運用コードで決して呼んではならない。
     *
     * @internal
     * @ignore
     * @codeCoverageIgnore
     */
    public function refresh(): static
    {
        $this->getUnsafeOption('cacheProvider')->clear();
        $this->cache = new \ArrayObject();
        $this->txConnection = $this->getMasterConnection();
        $this->foreignKeySwitchingLevels = [];
        $this->affectedRows = null;
        $this->lastInsertIds = [];
        return $this->unstackAll();
    }
}
