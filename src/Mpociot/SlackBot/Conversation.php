<?php

namespace Mpociot\SlackBot;

use Closure;

/**
 * Class Conversation.
 */
abstract class Conversation
{
    /**
     * @var SlackBot
     */
    protected $bot;

    /**
     * @var string
     */
    protected $token;

    /**
     * @param SlackBot $bot
     */
    public function setBot(SlackBot $bot)
    {
        $this->bot = $bot;
        $this->token = $bot->getToken();
    }

    /**
     * @param string|Question $question
     * @param Closure $next
     * @return $this
     */
    public function ask($question, Closure $next)
    {
        $this->bot->reply($question);
        $this->bot->storeConversation($this, $next);

        return $this;
    }

    /**
     * @param string|Question $message
     * @return $this
     */
    public function reply($message)
    {
        $this->bot->reply($message);

        return $this;
    }

    /**
     * @return mixed
     */
    abstract public function run();

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }
}
