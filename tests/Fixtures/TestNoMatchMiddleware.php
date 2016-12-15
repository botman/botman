<?php

namespace Mpociot\BotMan\Tests\Fixtures;

use Mpociot\BotMan\Message;
use Mpociot\BotMan\Drivers\Driver;
use Mpociot\BotMan\Interfaces\MiddlewareInterface;

class TestNoMatchMiddleware implements MiddlewareInterface
{
    /**
     * Handle / modify the message.
     *
     * @param Message $message
     * @param Driver $driver
     */
    public function handle(Message &$message, Driver $driver)
    {

    }

    /**
     * @param Message $message
     * @param string $test
     * @return bool
     */
    public function isMessageMatching(Message $message, $test)
    {
        return false;
    }
}
