<?php

namespace Mpociot\BotMan\Interfaces;

use Mpociot\BotMan\Message;

interface MiddlewareInterface
{
    /**
     * Handle / modify the message.
     *
     * @param Message $message
     * @param DriverInterface $driver
     */
    public function handle(Message &$message, DriverInterface $driver);

    /**
     * @param Message $message
     * @param string $test
     * @param bool $regexMatched Indicator if the regular expression was matched too
     * @return bool
     */
    public function isMessageMatching(Message $message, $test, $regexMatched);
}
