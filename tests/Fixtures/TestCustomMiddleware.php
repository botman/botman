<?php

namespace BotMan\BotMan\Tests\Fixtures;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Interfaces\MiddlewareInterface;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

class TestCustomMiddleware implements MiddlewareInterface
{
    /**
     * Handle a captured message.
     *
     * @param IncomingMessage $message
     * @param callable $next
     * @param BotMan $bot
     *
     * @return mixed
     */
    public function captured(IncomingMessage $message, $next, BotMan $bot)
    {
        $_SERVER['middleware_captured'] = $message->getText();
        $conversation = $bot->getStoredConversation($message);
        /** @var Question $question */
        $question = unserialize($conversation['question']);
        $_SERVER['middleware_captured_question'] = $question;

        return $next($message);
    }

    /**
     * Handle an incoming message.
     *
     * @param IncomingMessage $message
     * @param callable $next
     * @param BotMan $bot
     *
     * @return mixed
     */
    public function received(IncomingMessage $message, $next, BotMan $bot)
    {
        $_SERVER['middleware_received_count'] = isset($_SERVER['middleware_received_count']) ? $_SERVER['middleware_received_count'] + 1 : 1;
        $_SERVER['middleware_received'] = $message->getText();

        return $next($message);
    }

    /**
     * @param \BotMan\BotMan\Messages\Incoming\IncomingMessage $message
     * @param string $pattern
     * @param bool $regexMatched Indicator if the regular expression was matched too
     * @return bool
     */
    public function matching(IncomingMessage $message, $pattern, $regexMatched)
    {
        $_SERVER['middleware_matching'] = $message->getText().'-'.$pattern;

        return $regexMatched;
    }

    /**
     * Handle a message that was successfully heard, but not processed yet.
     *
     * @param IncomingMessage $message
     * @param callable $next
     * @param BotMan $bot
     *
     * @return mixed
     */
    public function heard(IncomingMessage $message, $next, BotMan $bot)
    {
        $_SERVER['middleware_heard_count'] = isset($_SERVER['middleware_heard_count']) ? $_SERVER['middleware_heard_count'] + 1 : 1;

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
        $_SERVER['middleware_sending_outgoing'] = $bot->getOutgoingMessage();
        $text = $payload->getText();
        $payload->text($text.' - middleware');
        $response = $next($payload);
        $content = $response->getContent();
        $response->setContent($content.' - sending');

        return $response;
    }
}
