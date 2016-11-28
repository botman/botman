<?php
/**
 * Created by PhpStorm.
 * User: marcel
 * Date: 27/11/2016
 * Time: 22:03
 */

namespace Mpociot\SlackBot;


class Message
{
    /** @var string */
    protected $message;

    /** @var string */
    protected $user;

    /** @var string */
    protected $channel;

    public function __construct($message, $user, $channel)
    {
        $this->message = $message;
        $this->user = $user;
        $this->channel = $channel;
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getConversationIdentifier()
    {
        return 'conversation:'.$this->getUser().'-'.$this->getChannel();
    }

}