<?php

namespace Mpociot\BotMan\Messages;

class Message
{
    /** @var string */
    protected $message;

    /** @var string */
    protected $image;

    /**
     * Message constructor.
     * @param string $message
     * @param string $image
     */
    public function __construct($message = null, $image = null)
    {
        $this->message = $message;
        $this->image = $image;
    }

    /**
     * @param string $message
     * @param string $image
     * @return Message
     */
    public static function create($message = null, $image = null)
    {
        return new self($message, $image);
    }

    /**
     * @param string $message
     * @return $this
     */
    public function message($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @param string $image
     * @return $this
     */
    public function image($image)
    {
        $this->image = $image;
        return $this;
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
    public function getImage()
    {
        return $this->image;
    }
}
