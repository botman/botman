<?php

namespace Mpociot\BotMan\Tests\Fixtures;

use Mpociot\BotMan\Interfaces\MiddlewareInterface;
use Mpociot\BotMan\Message;

class TestMiddleware implements MiddlewareInterface
{
    /**
     * Handle / modify the message.
     *
     * @param Message $message
     */
    public function handle(Message &$message)
    {
        $message->addExtras('test', 'successful');
    }

    /**
     * @param Message $message
     * @param string $test
     * @return bool
     */
    public function isMessageMatching(Message $message, $test)
    {
        return $message->getExtras()['test'] == $test;
    }
}
