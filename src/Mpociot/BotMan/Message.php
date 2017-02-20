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

    /** @var array */
    protected $images = [];

    /** @var mixed */
    protected $payload;

    /** @var array */
    protected $extras = [];

    /** @var array */
    private $audio = [];

    /** @var array */
    private $attachments = [];

    /** @var array */
    private $location = [];

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
     * @param array $images
     */
    public function setImages(array $images)
    {
        $this->images = $images;
    }

    /**
     * Returns the message image URL.
     * @return array
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param array $audio
     */
    public function setAudio(array $audio)
    {
        $this->audio = $audio;
    }

    /**
     * @return array
     */
    public function getAudio()
    {
        return $this->audio;
    }

    /**
     * @param array $attachments
     */
    public function setAttachments(array $attachments)
    {
        $this->attachments = $attachments;
    }

    /**
     * @return array
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * @param array $location
     */
    public function setLocation(array $location)
    {
        $this->location = $location;
    }

    /**
     * @return array
     */
    public function getLocation()
    {
        return $this->location;
    }
}
