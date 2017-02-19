<?php

namespace Mpociot\BotMan;

use Illuminate\Support\Collection;

class Message
{
    /** @var string */
    protected $message;

    /** @var string */
    protected $user;

    /** @var string */
    protected $channel;

    /** @var string */
    protected $image;

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
        return 'conversation-'.sha1($this->getUser()).'-'.sha1($this->getChannel());
    }

    /**
     * @return string
     */
    public function getOriginatedConversationIdentifier()
    {
        return 'conversation--'.sha1($this->getChannel());
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
     * @param string|null $key
     * @return array
     */
    public function getExtras($key = null)
    {
        if (! is_null($key)) {
            return Collection::make($this->extras)->get($key);
        }

        return $this->extras;
    }

    /**
     * Returns the message image URL.
     * @return string|null
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Sets the message image.
     * @param string $image The image URL
     */
    public function setImage($image)
    {
        $this->image = $image;
    }
}
