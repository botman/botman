<?php

namespace Mpociot\BotMan\Tests\Fixtures;

use Mpociot\BotMan\Message;
use Mpociot\BotMan\Interfaces\DriverInterface;
use Mpociot\BotMan\Interfaces\MiddlewareInterface;

class TestNoMatchMiddleware implements MiddlewareInterface
{
    /**
     * Handle / modify the message.
     *
     * @param Message $message
     * @param DriverInterface $driver
     */
    public function handle(Message &$message, DriverInterface $driver)
    {
    }

    /**
     * @param Message $message
     * @param string $test
     * @param bool $regexMatched
     * @return bool
     */
    public function isMessageMatching(Message $message, $test, $regexMatched)
    {
        return false;
    }
}
