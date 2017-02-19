<?php

namespace Mpociot\BotMan;

use Closure;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Interfaces\ShouldQueue;

/**
 * Class Conversation.
 */
abstract class Conversation
{
    /**
     * @var BotMan
     */
    protected $bot;

    /**
     * @var string
     */
    protected $token;

    /**
     * @param BotMan $bot
     */
    public function setBot(BotMan $bot)
    {
        $this->bot = $bot;
    }

    /**
     * @param string|Question $question
     * @param array|Closure $next
     * @param array $additionalParameters
     * @return $this
     */
    public function ask($question, $next, $additionalParameters = [])
    {
        $this->bot->reply($question, $additionalParameters);
        $this->bot->storeConversation($this, $next, $question, $additionalParameters);

        return $this;
    }

    /**
     * Repeat the previously asked question.
     * @param string|Question $question
     */
    public function repeat($question = '')
    {
        $conversation = $this->bot->getStoredConversation();

        if (! $question instanceof Question && ! $question) {
            $question = unserialize($conversation['question']);
        }

        $next = $conversation['next'];
        $additionalParameters = unserialize($conversation['additionalParameters']);

        if (is_string($next)) {
            $next = unserialize($next)->getClosure();
        } elseif (is_array($next)) {
            $next = Collection::make($next)->map(function ($callback) {
                $callback['callback'] = unserialize($callback['callback'])->getClosure();

                return $callback;
            })->toArray();
        }
        $this->ask($question, $next, $additionalParameters);
    }

    /**
     * @param string|Question $message
     * @param array $additionalParameters
     * @return $this
     */
    public function say($message, $additionalParameters = [])
    {
        $this->bot->reply($message, $additionalParameters);

        return $this;
    }

    /**
     * Should the conversation be skipped (temporarily).
     * @param  Message $message
     * @return bool
     */
    public function skipConversation(Message $message)
    {
        //
    }

    /**
     * Should the conversation be removed and stopped (permanently).
     * @param  Message $message
     * @return bool
     */
    public function stopConversation(Message $message)
    {
        //
    }

    /**
     * @return mixed
     */
    abstract public function run();

    /**
     * @return array
     */
    public function __sleep()
    {
        $properties = get_object_vars($this);
        if (! $this instanceof ShouldQueue) {
            unset($properties['bot']);
        }

        return array_keys($properties);
    }
}
