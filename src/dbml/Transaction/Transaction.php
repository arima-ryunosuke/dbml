<?php

namespace ryunosuke\dbml\Transaction;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\DBAL\TransactionIsolationLevel;
use Psr\Log\LoggerInterface;
use ryunosuke\dbml\Database;
use ryunosuke\dbml\Logging\Logger;
use ryunosuke\dbml\Logging\LoggerChain;
use ryunosuke\dbml\Logging\Middleware;
use ryunosuke\dbml\Mixin\OptionTrait;
use function ryunosuke\dbml\array_set;
use function ryunosuke\dbml\arrayize;

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
 * $retries を設定するとリトライ回数・間隔を指定することができる。
 * 例えば `retries([5000, 10000])` は「1回目のリトライは5秒後、2回めのリトライは10秒後」を意味する（つまりこの場合、最悪15秒かかる）。
 * 指定形式の都合上、「無限リトライ」をすることはできない（実装を検討中。Generator を返すクロージャを与えるのが無難か？）
 *
 * 「リトライするか？」の判定は $retryable に「例外オブジェクトを受け取り真偽値を返す」クロージャを設定する。
 * 例外発生時にそのクロージャが呼び出され、 true を返って来たらリトライ処理を行う。
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
 * @property int $isolationLevel トランザクション分離レベル
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
 * @property int[] $retries リトライ間隔
 * @property \Closure $retryable リトライ判定処理
 * @property bool $savepointable savepoint 有効/無効フラグ
 *
 * @method $this|int       isolationLevel($int = null) トランザクション分離レベルを設定・取得する
 * @method int             getIsolationLevel()  トランザクション分離レベルを取得する
 * @method $this           setIsolationLevel($int) トランザクション分離レベルを設定する
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
 * @method $this|int[]     retries($ints = null) リトライ間隔を設定・取得する
 * @method int[]           getRetries()リトライ間隔を取得する
 * @method $this           setRetries($ints)リトライ間隔を設定する
 * @method $this|\Closure  retryable($closure = null) リトライ判定処理を設定・取得する
 * @method \Closure        getRetryable() リトライ判定処理を取得する
 * @method $this           setRetryable(\Closure $closure) リトライ判定処理を設定する
 * @method $this|bool      savepointable($bool = null) savepoint 有効/無効フラグを設定・取得する
 * @method bool            getSavepointable() savepoint 有効/無効フラグを取得する
 * @method $this           setSavepointable($bool) savepoint 有効/無効フラグを設定する
 */
class Transaction
{
    use OptionTrait;

    /// トランザクション分離レベル
    public const READ_UNCOMMITTED = TransactionIsolationLevel::READ_UNCOMMITTED;
    public const READ_COMMITTED   = TransactionIsolationLevel::READ_COMMITTED;
    public const REPEATABLE_READ  = TransactionIsolationLevel::REPEATABLE_READ;
    public const SERIALIZABLE     = TransactionIsolationLevel::SERIALIZABLE;

    /** @var Database */
    private $database;

    /** @var int リトライ回数 */
    private $retryCount;

    public static function getDefaultOptions()
    {
        return [
            // マスターで実行するか否か
            'masterMode'     => true,
            // トランザクション分離レベル(REPEATABLE_READ, ...)
            'isolationLevel' => null,
            // 実行クエリロガー
            'logger'         => null,
            // トランザクションイベント
            'begin'          => [/* function (Connection $connection) { }*/],
            'commit'         => [/* function (Connection $connection) { }*/],
            'rollback'       => [/* function (Connection $connection) { }*/],
            // メイン処理
            'main'           => [/* function (Database $database, $return = null) { }*/],
            // 成功イベント
            'done'           => [/* function ($return) { }*/],
            // 失敗イベント
            'fail'           => [/* function ($exception) { }*/],
            // リトライイベント
            'retry'          => [/* function ($retryCount) { }*/],
            // 失敗イベント
            'catch'          => [/* function () { }*/],
            // 完了イベント
            'finish'         => [/* function () { }*/],
            // リトライ回数兼間隔
            'retries'        => [],
            // リトライ可能判定処理
            'retryable'      => null,
            // セーブポイントを活かすか
            'savepointable'  => null,
        ];
    }

    /**
     * コンストラクタ
     *
     * @param Database $database データベース
     * @param array $options オプション
     */
    public function __construct(Database $database, $options = [])
    {
        $this->database = $database;

        $default = [];
        foreach ($database->getOptions() as $key => $value) {
            $key = preg_replace('#^transaction#u', '', $key, -1, $count);
            if ($count) {
                $default[lcfirst($key)] = $value;
            }
        }
        $default['retryable'] = function ($ex) {
            return $ex instanceof RetryableException;
        };
        $this->setDefault($options + $default);
    }

    public function __get($name)
    {
        return $this->getOption($name);
    }

    public function __set($name, $value)
    {
        return $this->setOption($name, $value);
    }

    public function __call($name, $arguments)
    {
        return $this->OptionTrait__call($name, $arguments);
    }

    public function __invoke($throwable)
    {
        return $this->perform($throwable);
    }

    private function _callback($name, $callback, $key)
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

    private function _ready($previewMode)
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

        // セーブポイント有効
        $current_savepoint = null;
        if ($this->savepointable !== null && $this->savepointable !== $connection->getNestTransactionsWithSavepoints()) {
            $current_savepoint = $connection->getNestTransactionsWithSavepoints();
            $connection->setNestTransactionsWithSavepoints($this->savepointable);
        }

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
            if ($current_savepoint !== null) {
                $connection->setNestTransactionsWithSavepoints($current_savepoint);
            }
        };
    }

    private function _execute(Connection $connection, $previewMode)
    {
        // begin
        $connection->beginTransaction();
        $this->_invokeArray($this->begin, $connection);

        try {
            // main
            $return = $this->_invokeArray($this->main, $this->database);

            // commit
            $this->_invokeArray($this->commit, $connection);
            if ($previewMode) {
                $connection->rollBack();
            }
            else {
                $connection->commit();
            }
        }
        catch (\Exception $ex) {
            // rollback
            $connection->rollBack();
            $this->_invokeArray($this->rollback, $connection);

            // fail
            $this->_invokeArray($this->fail, $ex);

            // リトライ
            $retries = $this->retries;
            if (isset($retries[$this->retryCount]) && ($this->retryable)($ex)) {
                usleep($retries[$this->retryCount] * 1000);
                $this->retryCount++;

                // retry
                $this->_invokeArray($this->retry, $this->retryCount);

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
     *
     * @param \Closure $callback begin イベント
     * @param null $key イベント名。数値や文字列なら既存を上書き（無いなら追加）、未指定時は常に追加
     * @return $this 自分自身
     */
    public function begin($callback, $key = null) { return $this->_callback(__FUNCTION__, $callback, $key); }

    /**
     * commit イベントを設定する
     *
     * @used-by setCommit()
     * @used-by getCommit()
     *
     * @param \Closure $callback commit イベント
     * @param null $key イベント名。数値や文字列なら既存を上書き（無いなら追加）、未指定時は常に追加
     * @return $this 自分自身
     */
    public function commit($callback, $key = null) { return $this->_callback(__FUNCTION__, $callback, $key); }

    /**
     * rollback イベントを設定する
     *
     * @used-by setRollback()
     * @used-by getRollback()
     *
     * @param \Closure $callback rollback イベント
     * @param null $key イベント名。数値や文字列なら既存を上書き（無いなら追加）、未指定時は常に追加
     * @return $this 自分自身
     */
    public function rollback($callback, $key = null) { return $this->_callback(__FUNCTION__, $callback, $key); }

    /**
     * main イベントを設定する
     *
     * @used-by setMain()
     * @used-by getMain()
     *
     * @param \Closure|\Closure[] $callback main イベント
     * @param null $key イベント名。数値や文字列なら既存を上書き（無いなら追加）、未指定時は常に追加
     * @return $this 自分自身
     */
    public function main($callback, $key = null) { return $this->_callback(__FUNCTION__, $callback, $key); }

    /**
     * done イベントを設定する
     *
     * @used-by setDone()
     * @used-by getDone()
     *
     * @param \Closure $callback done イベント
     * @param null $key イベント名。数値や文字列なら既存を上書き（無いなら追加）、未指定時は常に追加
     * @return $this 自分自身
     */
    public function done($callback, $key = null) { return $this->_callback(__FUNCTION__, $callback, $key); }

    /**
     * fail イベントを設定する
     *
     * @used-by setFail()
     * @used-by getFail()
     *
     * @param \Closure $callback fail イベント
     * @param null $key イベント名。数値や文字列なら既存を上書き（無いなら追加）、未指定時は常に追加
     * @return $this 自分自身
     */
    public function fail($callback, $key = null) { return $this->_callback(__FUNCTION__, $callback, $key); }

    /**
     * retry イベントを設定する
     *
     * @used-by setRetry()
     * @used-by getRetry()
     *
     * @param \Closure $callback retry イベント
     * @param null $key イベント名。数値や文字列なら既存を上書き（無いなら追加）、未指定時は常に追加
     * @return $this 自分自身
     */
    public function retry($callback, $key = null) { return $this->_callback(__FUNCTION__, $callback, $key); }

    /**
     * catch イベントを設定する
     *
     * @used-by setCatch()
     * @used-by getCatch()
     *
     * @param \Closure $callback catch イベント
     * @param null $key イベント名。数値や文字列なら既存を上書き（無いなら追加）、未指定時は常に追加
     * @return $this 自分自身
     */
    public function catch($callback, $key = null) { return $this->_callback(__FUNCTION__, $callback, $key); }

    /**
     * finish イベントを設定する
     *
     * @used-by setFinish()
     * @used-by getFinish()
     *
     * @param \Closure $callback finish イベント
     * @param null $key イベント名。数値や文字列なら既存を上書き（無いなら追加）、未指定時は常に追加
     * @return $this 自分自身
     */
    public function finish($callback, $key = null) { return $this->_callback(__FUNCTION__, $callback, $key); }

    /**
     * トランザクションをマスター接続で実行するようにする
     *
     * @return $this 自分自身
     */
    public function master() { return $this->setOption('masterMode', true); }

    /**
     * トランザクションをスレーブ接続で実行するようにする
     *
     * @return $this 自分自身
     */
    public function slave() { return $this->setOption('masterMode', false); }

    /**
     * トランザクションとして実行する
     *
     * $throwable は catch で代替可能なので近い将来削除される。
     *
     * @param bool $throwable true を指定すると例外発生時に例外が飛ぶ。 false にすると返り値で返す
     * @return mixed トランザクションコールバックの返り値
     */
    public function perform($throwable = true)
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
     *
     * @param array $queries 一連の実行クエリが格納される
     * @return mixed トランザクションコールバックの返り値
     */
    public function preview(&$queries = [])
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
