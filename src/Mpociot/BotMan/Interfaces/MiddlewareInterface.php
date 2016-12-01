<?php

namespace Mpociot\BotMan\Interfaces;

use Mpociot\BotMan\Message;

interface MiddlewareInterface
{
    /**
     * Handle / modify the message
     *
     * @param Message $message
     */
    public function handle(Message &$message);

    /**
     * @param Message $message
     * @param string $test
     * @return bool
     */
    public function isMessageMatching(Message $message, $test);
}
