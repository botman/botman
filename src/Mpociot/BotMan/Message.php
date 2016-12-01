<?php

namespace Mpociot\BotMan;

class Message
{
    /** @var string */
    protected $message;

    /** @var string */
    protected $user;

    /** @var string */
    protected $channel;

    /** @var mixed */
    protected $payload;

    /** @var array */
    protected $extras = [];

    public function __construct($message, $user, $channel, $payload = null)
    {
        $this->message = $message;
        $this->user = $user;
        $this->channel = $channel;
        $this->payload = $payload;
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
    public function getPayload()
    {
        return $this->payload;
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

    /**
     * @param string $key
     * @param mixed $value
     * @return Message
     */
    public function addExtras($key, $value)
    {
        $this->extras[$key] = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function getExtras()
    {
        return $this->extras;
    }
}
