<?php

namespace ryunosuke\dbml\Driver;

use Doctrine\DBAL\Driver;

class ConnectionMiddleware implements Driver\Middleware
{
    public function __construct(
        private \Closure $retryer,
        private array $commands,
    ) {
    }

    public function wrap(Driver $driver): Driver
    {
        return new class (
            $driver,
            $this->retryer,
            $this->commands,
        ) extends Driver\Middleware\AbstractDriverMiddleware {
            private int $retry = 0;

            public function __construct(
                Driver $wrappedDriver,
                private \Closure $retryer,
                private array $commands,
            ) {
                parent::__construct($wrappedDriver);
            }

            public function connect(array $params): Driver\Connection
            {
                // 内部的に何度か呼ばれる（多分 getDatabasePlatformVersion）ようなのでリトライ回数はプロパティにしてトータルで規定以上呼ばれないようにする
                while (true) {
                    try {
                        $connection = parent::connect($params);
                        break;
                    }
                    catch (Driver\AbstractException $e) {
                        // 色々な例外・コードが飛んでくるので一概に判断ができない
                        // 例えば ConnectionFailed が来たからといってリトライすべきかというとそうでもない（認証でコケたのならリトライしても無駄）
                        // 例外オブジェクト渡してコールバックで判断する
                        $wait = ($this->retryer)(++$this->retry, $e);
                        if ($wait === null) {
                            throw $e;
                        }
                        usleep($wait * 1000 * 1000);
                    }
                }

                // （今はそんなものはないが）接続断の後の再接続なども呼ばれ得るのでリセットしておく
                $this->retry = 0;

                // 初期化コマンド実行
                foreach ($this->commands as $command) {
                    $connection->exec($command);
                }
                return $connection;
            }
        };
    }
}
