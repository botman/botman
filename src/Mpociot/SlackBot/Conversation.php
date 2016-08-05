<?php

namespace Mpociot\SlackBot;

use Closure;
use Illuminate\Queue\SerializesModels;

/**
 * Class Conversation.
 */
abstract class Conversation
{
    use SerializesModels;

    /**
     * @var SlackBot
     */
    protected $bot;

    /**
     * @var
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
     * @param $question
     * @param Closure $next
     * @return $this
     */
    public function ask($question, Closure $next)
    {
        $this->bot->respond($question);
        $this->bot->storeConversation($this, $next);

        return $this;
    }

    /**
     * @param $message
     * @return $this
     */
    public function reply($message)
    {
        $this->bot->respond($message);

        return $this;
    }

    /**
     * @return mixed
     */
    abstract public function run();
}
