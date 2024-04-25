<?php

namespace ryunosuke\dbml\Logging;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * ログラッパー
 *
 * 配下にある PSR-3 に委譲するだけで自身は何もしない。
 */
class LoggerChain extends AbstractLogger implements LoggerAwareInterface
{
    /** @var LoggerInterface[] */
    private array $loggers = [];

    /**
     * @inheritdoc
     */
    public function log($level, $message, array $context = []): void
    {
        foreach ($this->loggers as $logger) {
            $logger->log($level, $message, $context);
        }
    }

    /**
     * @inheritdoc
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->loggers = [$logger];
    }

    /**
     * 内部ロガーを追加して設定されていたものを返す
     *
     * @param LoggerInterface $logger 追加するロガー
     * @param ?string $name ロガーの名前
     * @return LoggerInterface[]
     */
    public function addLogger(LoggerInterface $logger, string $name = null)
    {
        $return = $this->loggers;
        if ($name === null) {
            $this->loggers[] = $logger;
        }
        else {
            $this->loggers[$name] = $logger;
        }
        return $return;
    }

    /**
     * 内部ロガーをセットして設定されていたものを返す
     *
     * @param LoggerInterface[] $loggers 追加するロガー
     * @return LoggerInterface[]
     */
    public function resetLoggers(array $loggers): array
    {
        $return = $this->loggers;
        $this->loggers = $loggers;
        return $return;
    }
}
