<?php

namespace ryunosuke\Test\dbml\Logging;

use Psr\Log\AbstractLogger;
use ryunosuke\dbml\Logging\LoggerChain;

class LoggerChainTest extends \ryunosuke\Test\AbstractUnitTestCase
{
    function test_all()
    {
        $dummy_loger = new class() extends AbstractLogger {
            public array $logs = [];

            public function log($level, $message, array $context = []): void
            {
                $this->logs[] = $message;
            }
        };
        $logger = new LoggerChain();
        $logger->setLogger($dummy_loger);
        $logger->addLogger($dummy_loger, 'dummy');

        $logger->log('debug', 'message');

        $this->assertEquals([
            'message',
            'message',
        ], $dummy_loger->logs);

        $this->assertSame([
            0       => $dummy_loger,
            'dummy' => $dummy_loger,
        ], $logger->resetLoggers([]));

        $this->assertSame([], $logger->resetLoggers([]));
    }
}
