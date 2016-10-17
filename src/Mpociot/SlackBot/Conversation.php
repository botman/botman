<?php
namespace Mpociot\SlackBot;

use Closure;
use Illuminate\Queue\SerializesModels;

/**
 * Class Conversation
 * @package Mpociot\SlackBot
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
        $this->bot->storeConversation($this,$next);

        return $this;
    }

    public function askWithAttachments($question, $attachments = [], Closure $next)
    {
        $this->bot->respond($question, $attachments);
        $this->bot->storeConversation($this,$next);

        return $this;
    }

    /**
     * @param $message
     * @param array $attachments
     * @return $this
     */
    public function reply($message, $attachments = [])
    {
        $this->bot->respond($message, $attachments);
        return $this;
    }

    /**
     * @return mixed
     */
    abstract public function run();
}