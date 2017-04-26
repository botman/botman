<?php

namespace Mpociot\BotMan\Tests\Fixtures;

use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Interfaces\MiddlewareInterface;

class TestCustomMiddleware implements MiddlewareInterface
{
    /**
     * Handle a captured message.
     *
     * @param Message $message
     * @param callable $next
     * @param BotMan $bot
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
     * @param callable $next
     * @param BotMan $bot
     *
     * @return mixed
     */
    public function received(Message $message, $next, BotMan $bot)
    {
        $_SERVER['middleware_received_count'] = isset($_SERVER['middleware_received_count']) ? $_SERVER['middleware_received_count']+1 : 1;
        $_SERVER['middleware_received'] = $message->getMessage();
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
        return $regexMatched;
    }

    /**
     * Handle a message that was successfully heard, but not processed yet.
     *
     * @param Message $message
     * @param callable $next
     * @param BotMan $bot
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
     * @param mixed $payload
     * @param callable $next
     * @param BotMan $bot
     *
     * @return mixed
     */
    public function sending($payload, $next, BotMan $bot)
    {
        $payload .= ' - middleware';
        $response = $next($payload);
        $content = $response->getContent();
        $response->setContent($content.' - sending');

        return $response;
    }
}
