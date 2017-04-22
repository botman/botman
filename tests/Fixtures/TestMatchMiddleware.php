<?php

namespace Mpociot\BotMan\Tests\Fixtures;

use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Interfaces\DriverInterface;
use Mpociot\BotMan\Interfaces\MiddlewareInterface;

class TestMatchMiddleware implements MiddlewareInterface
{
    /**
     * Handle a captured message.
     *
     * @param Message $message
     * @param BotMan $bot
     * @param $next
     *
     * @return mixed
     */
    public function captured(Message $message, $next, BotMan $bot)
    {
        return $next($message);
    }

    /**
     * Handle an incoming message.
     *
     * @param Message $message
     * @param BotMan $bot
     * @param $next
     *
     * @return mixed
     */
    public function received(Message $message, $next, BotMan $bot)
    {
        $message->addExtras('driver_name', $bot->getDriver()->getName());
        $message->addExtras('test', 'successful');

        return $next($message);
    }

    /**
     * @param Message $message
     * @param string $pattern
     * @param bool $regexMatched Indicator if the regular expression was matched too
     * @return bool
     */
    public function matching(Message $message, $pattern, $regexMatched)
    {
        return true && $regexMatched;
    }

    /**
     * Handle a message that was successfully heard, but not processed yet.
     *
     * @param Message $message
     * @param BotMan $bot
     * @param $next
     *
     * @return mixed
     */
    public function heard(Message $message, $next, BotMan $bot)
    {
        return $next($message);
    }

    /**
     * Handle an outgoing message payload before/after it
     * hits the message service.
     *
     * @param Message $message
     * @param BotMan $bot
     * @param $next
     *
     * @return mixed
     */
    public function sending(Message $message, $next, BotMan $bot)
    {
        return $next($message);
    }
}
