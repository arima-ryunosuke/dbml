<?php

namespace ryunosuke\dbml\Transaction;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\TransactionIsolationLevel;
use Psr\Log\LoggerInterface;
use ryunosuke\dbml\Database;
use ryunosuke\dbml\Logging\Logger;
use ryunosuke\dbml\Logging\LoggerChain;
use ryunosuke\dbml\Logging\Middleware;
use ryunosuke\dbml\Mixin\OptionTrait;
use ryunosuke\utility\attribute\ClassTrait\DebugInfoTrait;
use function ryunosuke\dbml\array_set;
use function ryunosuke\dbml\arrayize;
use function ryunosuke\dbml\str_exists;

/**
 * トランザクションを表すクラス
 *
 * メイン処理に加えて、
 *
 * - リトライを設定できる
 * - 各種イベント（begin, commit 等）を設定できる
 * - 分離レベルを指定できる
 *
 * などの特徴がある。
 *
 * ### リトライ
 *
 * 「リトライするか？」の判定は $retryable に「リトライ回数と例外オブジェクトを受け取り真偽値を返す」クロージャを設定する。
 * 例外発生時にそのクロージャが呼び出され、 float が返って来たらその分待機してリトライ処理を行う。
 *
 * ### イベント
 *
 * イベント系メソッドは内部的には配列で保持され、保持している分が全て実行される。
 * 例えば `main(function(){})` はイベントの**設定ではなく追加**となる。
 * 完全に置換するには `main([function(){}])` のように配列で与える必要がある。
 *
 * イベントはキーを持つ。このキーは追加/上書きの判定に使用したり、実行順を制御する。
 * main だけは特例で第2引数に前の返り値が渡ってチェーンされる（チェーンの最初は渡ってこない。つまり func_num_args などで判定可能）。
 *
 * ```php
 * $tx = new Transaction($db);
 * $tx->main(function($db, $prev) {return $prev + 1;}, 2);     // A
 * $tx->main(function($db, $prev = 0) {return $prev + 1;}, 1); // B
 * $tx->main(function($db, $prev) {return $prev + 1;}, 3);     // C
 * $tx->perform(); // =3
 * ```
 *
 * 上記はイベント名を指定して追加しているので、実行順は B -> A -> C となる。
 * かつチェーンを利用しているので、A , C にはその前段階の結果が第2引数で渡ってくる。
 * なお、イベント名は文字列でも良い。その場合の順番は SORT_REGULAR に従う。
 *
 * イベントの種類は下記。
 *
 * - トランザクションのそれぞれのイベント
 *     - begin(\Closure(Connection $c))
 *     - commit(\Closure(Connection $c))
 *     - rollback(\Closure(Connection $c))
 * - トランザクションのメイン処理
 *     - main(\Closure(Database $db, $prev_return))
 * - トランザクション失敗時のイベント（リトライ時はトランザクションのたびに実行される）
 *     - fail(\Closure(Expcetion $exception))
 * - トランザクション完了時のイベント
 *     - done(\Closure(mixed $return))
 * - トランザクションリトライ時のイベント
 *     - retry(\Closure(int $retryCount))
 * - 処理失敗時のイベント (リトライに依らず常に1回のみコール)
 *     - catch(Expcetion $exception)
 * - 処理完了時のイベント (リトライに依らず常に1回のみコール)
 *     - finish()
 *
 * いくつかよくありそうなケースの呼び出しフローを下記に例として挙げる（ネストはトランザクションを表す）。
 *
 * - **main が例外を投げなく、リトライもされない最もシンプルな例**
 *   - begin
 *       - main
 *   - commit
 *   - done
 *   - finish
 *
 * - **main が例外を投げるが、リトライはされない例**
 *   - begin
 *       - main(throw)
 *   - rollback
 *   - fail
 *   - catch
 *   - finish
 *
 * - **main が例外を投げるが、リトライで成功する例**
 *   - begin
 *       - main(throw)
 *   - rollback
 *   - fail
 *   - retry
 *   - begin
 *       - main
 *   - commit
 *   - done
 *   - finish
 *
 * - **main が例外を投げて、リトライでも失敗する例**
 *   - begin
 *       - main(throw)
 *   - rollback
 *   - fail
 *   - retry
 *   - begin
 *       - main(throw)
 *   - rollback
 *   - fail
 *   - catch
 *   - finish
 *
 * @property int|TransactionIsolationLevel $isolationLevel トランザクション分離レベル
 * @property LoggerInterface $logger ロガーインスタンス
 * @property \Closure[] $begin begin イベント配列
 * @property \Closure[] $commit commit イベント配列
 * @property \Closure[] $rollback rollback イベント配列
 * @property \Closure[] $main main イベント配列
 * @property \Closure[] $done done イベント配列
 * @property \Closure[] $fail fail イベント配列
 * @property \Closure[] $retry retry イベント配列
 * @property \Closure[] $catch catch イベント配列
 * @property \Closure[] $finish finish イベント配列
 * @property \Closure $retryable リトライ判定処理
 *
 * @method $this|int       isolationLevel($int = null) トランザクション分離レベルを設定・取得する
 * @method int             getIsolationLevel()  トランザクション分離レベルを取得する
 * @method $this           setIsolationLevel($int) トランザクション分離レベルを設定する
 * @method bool            getCheckImplicit() 暗黙コミット/ロールバックを検出するかを取得する
 * @method $this           setCheckImplicit($bool) 暗黙コミット/ロールバックを検出するかを設定する
 * @method $this|int       logger(LoggerInterface $logger = null) ロガーインスタンスを設定・取得する
 * @method LoggerInterface getLogger() ロガーインスタンスを取得する
 * @method $this           setLogger(LoggerInterface $logger) ロガーインスタンスを設定する
 * @method \Closure[]      getBegin() begin イベント配列を取得する
 * @method $this           setBegin(array $closure) begin イベント配列を設定する
 * @method \Closure[]      getCommit() commit イベント配列を取得する
 * @method $this           setCommit(array $closure) commit イベント配列を設定する
 * @method \Closure[]      getRollback() rollback イベント配列を取得する
 * @method $this           setRollback(array $closure) rollback イベント配列を設定する
 * @method \Closure[]      getMain() main イベント配列を取得する
 * @method $this           setMain(array $closure) main イベント配列を設定する
 * @method \Closure[]      getDone() done イベント配列を取得する
 * @method $this           setDone(array $closure) done イベント配列を設定する
 * @method \Closure[]      getFail() fail イベント配列を取得する
 * @method $this           setFail(array $closure) fail イベント配列を設定する
 * @method \Closure[]      getRetry() retry イベント配列を取得する
 * @method $this           setRetry(array $closure) retry イベント配列を設定する
 * @method \Closure[]      getCatch() catch イベント配列を取得する
 * @method $this           setCatch(array $closure) catch イベント配列を設定する
 * @method \Closure[]      getFinish() finish イベント配列を取得する
 * @method $this           setFinish(array $closure) finish イベント配列を設定する
 * @method $this|\Closure  retryable($closure = null) リトライ判定処理を設定・取得する
 * @method \Closure        getRetryable() リトライ判定処理を取得する
 * @method $this           setRetryable(\Closure $closure) リトライ判定処理を設定する
 */
class Transaction
{
    use DebugInfoTrait;
    use OptionTrait;

    /// トランザクション分離レベル
    public const READ_UNCOMMITTED = TransactionIsolationLevel::READ_UNCOMMITTED;
    public const READ_COMMITTED   = TransactionIsolationLevel::READ_COMMITTED;
    public const REPEATABLE_READ  = TransactionIsolationLevel::REPEATABLE_READ;
    public const SERIALIZABLE     = TransactionIsolationLevel::SERIALIZABLE;

    private Database $database;

    private int $retryCount;

    public static function getDefaultOptions(): array
    {
        return [
            /** @var bool マスターで実行するか否か */
            'masterMode'     => true,
            /** @var ?int トランザクション分離レベル(REPEATABLE_READ, ...) */
            'isolationLevel' => null,
            /** @var bool 暗黙コミット/ロールバックを検出するか（余計なクエリを投げるので開発時のみを推奨） */
            'checkImplicit'  => false,
            /** @var LoggerInterface 実行クエリロガー */
            'logger'         => null,
            /** @var \Closure[] トランザクションイベント */
            'begin'          => [/* function (Connection $connection) { }*/],
            'commit'         => [/* function (Connection $connection) { }*/],
            'rollback'       => [/* function (Connection $connection) { }*/],
            /** @var \Closure[] メイン処理 */
            'main'           => [/* function (Database $database, $return = null) { }*/],
            /** @var \Closure[] 成功イベント */
            'done'           => [/* function ($return) { }*/],
            /** @var \Closure[] 失敗イベント */
            'fail'           => [/* function ($exception) { }*/],
            /** @var \Closure[] リトライイベント */
            'retry'          => [/* function ($retryCount) { }*/],
            /** @var \Closure[] 失敗イベント */
            'catch'          => [/* function () { }*/],
            /** @var \Closure[] 完了イベント */
            'finish'         => [/* function () { }*/],
            /** @var \Closure リトライ可能判定処理 */
            'retryable'      => function (int $retryCount, \Exception $ex, Connection $connection): ?float {
                // 無限リトライ防止
                if ($retryCount > 5) {
                    return null;
                }
                // id や uk がクロージャで、値が毎回変わることもある
                if ($ex instanceof UniqueConstraintViolationException) {
                    return 0.1;
                }
                // それ以外は doctrine に任せる
                if ($ex instanceof RetryableException) {
                    return 1.0;
                }
                return null;
            },
            /** @var ?bool セーブポイントを活かすか */
            'savepointable'  => null, // delete in future scope
        ];
    }

    /**
     * コンストラクタ
     */
    public function __construct(Database $database, array $options = [])
    {
        $this->database = $database;

        $default = [];
        foreach ($database->getOptions() as $key => $value) {
            $key = preg_replace('#^transaction#u', '', $key, -1, $count);
            if ($count) {
                $default[lcfirst($key)] = $value;
            }
        }
        $this->checkUnknownOption($options);
        $this->setDefault($options + $default);
    }

    public function __get(string $name): mixed
    {
        return $this->getOption($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->setOption($name, $value);
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->OptionTrait__call($name, $arguments);
    }

    public function __invoke(bool $throwable)
    {
        return $this->perform($throwable);
    }

    private function _callback(string $name, $callback, $key): static
    {
        // 配列じゃないなら追加
        if (!is_array($callback)) {
            $callbacks = $this->getOption($name);
            array_set($callbacks, $callback, $key);
        }
        // 配列なら置換
        else {
            $callbacks = $callback;
        }

        // ソートして置換
        ksort($callbacks);
        $this->setOption($name, $callbacks);

        return $this;
    }

    private function _invokeArray($invokers, $variadic = [])
    {
        $args = array_slice(func_get_args(), 1);
        $last = count($args);
        foreach (arrayize($invokers) as $invoker) {
            $args[$last] = $invoker(...$args);
        }
        return $args[$last] ?? null;
    }

    private function _ready(bool $previewMode): \Closure
    {
        // 変数初期化
        $current_connection = $this->database->getConnection();
        $current_mode = $this->database->getMasterMode();
        $connection = $this->database->setConnection(!!$this->masterMode)->getConnection();
        if ($this->masterMode) {
            $this->database->setMasterMode(true);
        }
        $this->retryCount = 0;

        $chain_logger = null;
        foreach ($connection->getConfiguration()->getMiddlewares() as $middleware) {
            if ($middleware instanceof Middleware) {
                $logger = $middleware->getLogger();
                if ($logger instanceof LoggerChain) {
                    $chain_logger = $logger;
                }
            }
        }

        // ロガー設定
        $current_logger = null;
        if ($this->logger !== null) {
            if ($this->logger instanceof LoggerInterface) {
                assert($chain_logger !== null, 'must be LoggerChain to use transaction logger');
                if ($previewMode) {
                    $current_logger = $chain_logger->resetLoggers([$this->logger]);
                }
                else {
                    $current_logger = $chain_logger->addLogger($this->logger);
                }
            }
        }

        // @codeCoverageIgnoreStart delete in future scope
        // セーブポイント有効
        $current_savepoint = null;
        if ($this->savepointable !== null && $this->savepointable !== $connection->getNestTransactionsWithSavepoints()) {
            $current_savepoint = $connection->getNestTransactionsWithSavepoints();
            $connection->setNestTransactionsWithSavepoints($this->savepointable);
        }
        // @codeCoverageIgnoreEnd

        // 分離レベル変更
        $current_level = null;
        if ($this->isolationLevel !== null && $this->isolationLevel !== $connection->getTransactionIsolation()) {
            $current_level = $connection->getTransactionIsolation();
            $connection->setTransactionIsolation($this->isolationLevel);
        }

        // 変更を戻す(finally 句を模倣)
        return function () use ($connection, $current_connection, $chain_logger, $current_logger, $current_mode, $current_level, $current_savepoint) {
            $this->database->setConnection($current_connection);
            $this->database->setMasterMode($current_mode);
            if ($chain_logger && $current_logger) {
                $chain_logger->resetLoggers($current_logger);
            }
            if ($current_level !== null) {
                $connection->setTransactionIsolation($current_level);
            }
            // @codeCoverageIgnoreStart delete in future scope
            if ($current_savepoint !== null) {
                $connection->setNestTransactionsWithSavepoints($current_savepoint);
            }
            // @codeCoverageIgnoreEnd
        };
    }

    private function _execute(Connection $connection, bool $previewMode)
    {
        // - RDBMS によっては暗黙のコミット/ロールバックが発生することがある（truncate/deadlock）
        // - driver によっては実際のトランザクションレベルを見るものがある（php>=8.0 の PDO）
        // 上記の複合で There is no active transaction で即死してしまい正常フローにならないことがあるため、暗黙していたらスルーする
        // commit: normal1->implicit->normal2 で暗黙のコミットにより例外が飛ぶと normal1 は戻らない
        // - 暗黙のコミットと言えど成功しているならさほど実害はないのでスルーした方がマシ
        // rollback: normal1->implicit->normal2 で暗黙のロールバックにより例外が飛ぶとリトライされない
        // - try~catch を突き抜けるため。リトライで成功することもあるのでスルーすべき
        $endTransaction = function (Connection $connection, $method) {
            // そもそも暗黙のコミット/ロールバックが発生するようなトランザクションは避けるべきであり、開発時に気づけるようにチェックして例外を投げる
            if ($this->getUnsafeOption('checkImplicit') && $connection->getTransactionNestingLevel() === 1) {
                if (!($this->database->getCompatibleConnection($connection)->isTransactionActive() ?? true)) {
                    throw new \DomainException('detect transaction implicit commit/rollback');
                }
            }
            try {
                return $connection->$method();
            }
            catch (\Exception $ex) {
                // 判断する術がないので文字列ベース
                if (str_exists($ex->getMessage(), [
                    'There is no active transaction', // pdo
                    'no transaction is active',       // sqlite
                    'transaction must be started',    // mssql
                ], true)) {
                    $this->database->debug('detect transaction implicit commit/rollback');
                    // doctrine が
                    // - transactionLevel=0 後に rollback
                    // - rollbackSavepoint 後に --transactionLevel
                    // としていて一貫性がないので強制的に設定しなければならない
                    // rollback が失敗しても transactionLevel=0 になるのはバグに見えるが…
                    (fn() => $this->transactionNestingLevel = 0)->bindTo($connection, Connection::class)();
                    return true;
                }

                throw $ex; // @codeCoverageIgnore
            }
        };

        // begin
        $connection->beginTransaction();
        $this->_invokeArray($this->begin, $connection);

        try {
            // main
            $return = $this->_invokeArray($this->main, $this->database);

            // commit
            $this->_invokeArray($this->commit, $connection);
            $endTransaction($connection, $previewMode ? 'rollBack' : 'commit');
        }
        catch (\Exception $ex) {
            // rollback
            $endTransaction($connection, 'rollBack');
            $this->_invokeArray($this->rollback, $connection);

            // fail
            $this->_invokeArray($this->fail, $ex);

            // リトライ
            if (is_callable($this->retryable) && !is_null($retryable = ($this->retryable)($this->retryCount, $ex, $connection))) {
                usleep($retryable * 1000 * 1000);
                $this->_invokeArray($this->retry, ++$this->retryCount);

                return $this->_execute($connection, $previewMode);
            }

            throw $ex;
        }

        // done
        $this->_invokeArray($this->done, $return);

        return $return;
    }

    /**
     * begin イベントを設定する
     *
     * @used-by setBegin()
     * @used-by getBegin()
     */
    public function begin($callback, $key = null): static { return $this->_callback(__FUNCTION__, $callback, $key); }

    /**
     * commit イベントを設定する
     *
     * @used-by setCommit()
     * @used-by getCommit()
     */
    public function commit($callback, $key = null): static { return $this->_callback(__FUNCTION__, $callback, $key); }

    /**
     * rollback イベントを設定する
     *
     * @used-by setRollback()
     * @used-by getRollback()
     */
    public function rollback($callback, $key = null): static { return $this->_callback(__FUNCTION__, $callback, $key); }

    /**
     * main イベントを設定する
     *
     * @used-by setMain()
     * @used-by getMain()
     */
    public function main($callback, $key = null): static { return $this->_callback(__FUNCTION__, $callback, $key); }

    /**
     * done イベントを設定する
     *
     * @used-by setDone()
     * @used-by getDone()
     */
    public function done($callback, $key = null): static { return $this->_callback(__FUNCTION__, $callback, $key); }

    /**
     * fail イベントを設定する
     *
     * @used-by setFail()
     * @used-by getFail()
     */
    public function fail($callback, $key = null): static { return $this->_callback(__FUNCTION__, $callback, $key); }

    /**
     * retry イベントを設定する
     *
     * @used-by setRetry()
     * @used-by getRetry()
     */
    public function retry($callback, $key = null): static { return $this->_callback(__FUNCTION__, $callback, $key); }

    /**
     * catch イベントを設定する
     *
     * @used-by setCatch()
     * @used-by getCatch()
     */
    public function catch($callback, $key = null): static { return $this->_callback(__FUNCTION__, $callback, $key); }

    /**
     * finish イベントを設定する
     *
     * @used-by setFinish()
     * @used-by getFinish()
     */
    public function finish($callback, $key = null): static { return $this->_callback(__FUNCTION__, $callback, $key); }

    /**
     * トランザクションをマスター接続で実行するようにする
     */
    public function master(): static { return $this->setOption('masterMode', true); }

    /**
     * トランザクションをスレーブ接続で実行するようにする
     */
    public function slave(): static { return $this->setOption('masterMode', false); }

    /**
     * トランザクションとして実行する
     *
     * $throwable は catch で代替可能なので近い将来削除される。
     */
    public function perform(bool $throwable = true)
    {
        $finally = $this->_ready(false);

        try {
            return $this->_execute($this->database->getConnection(), false);
        }
        catch (\Exception $ex) {
            $this->_invokeArray($this->catch, $ex);
            if ($throwable) {
                throw $ex;
            }
            return $ex;
        }
        finally {
            $this->_invokeArray($this->finish);
            $finally();
        }
    }

    /**
     * トランザクションとして実行後、強制的に rollback する
     *
     * 一連の実行クエリが得られるが、あくまでDBレイヤーのトランザクションなので、 php的にファイルを変更したり、何かを送信したりしてもそれは戻らない。
     */
    public function preview(?array &$queries = [])
    {
        $logs = [];
        $cx = $this->context([
            'logger' => new Logger([
                'destination' => function ($log) use (&$logs) { $logs[] = $log; },
                'metadata'    => [],
            ]),
        ]);

        $finally = $cx->_ready(true);

        try {
            return $cx->_execute($this->database->getConnection(), true);
        }
        finally {
            $queries = $logs;
            $cx->_invokeArray($cx->finish);
            $finally();
        }
    }
}
