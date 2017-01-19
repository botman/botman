<?php

namespace Mpociot\BotMan;

use Closure;

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
     * @return $this
     */
    public function repeat()
    {
        $conversation = $this->bot->getStoredConversation();
        $this->ask(unserialize($conversation['question']), unserialize($conversation['next'])->getClosure(), unserialize($conversation['additionalParameters']));
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
     * @return mixed
     */
    abstract public function run();
}
