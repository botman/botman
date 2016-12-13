<?php

namespace Mpociot\BotMan\Interfaces;

use Mpociot\BotMan\Message;
use Mpociot\BotMan\Drivers\Driver;

interface MiddlewareInterface
{
    /**
     * Handle / modify the message.
     *
     * @param Message $message
     * @param Driver $driver
     */
    public function handle(Message &$message, Driver $driver);

    /**
     * @param Message $message
     * @param string $test
     * @return bool
     */
    public function isMessageMatching(Message $message, $test);
}
