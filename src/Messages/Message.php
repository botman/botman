<?php

namespace Mpociot\BotMan\Messages;

class Message
{
    /** @var string */
    protected $message;

    /** @var mixed */
    protected $attachment;

    /**
     * Message constructor.
     * @param string $message
     * @param mixed $attachment
     */
    public function __construct($message = null, $attachment = null)
    {
        $this->message = $message;
        $this->attachment = $attachment;
    }

    /**
     * @param string $message
     * @param mixed $attachment
     * @return Message
     */
    public static function create($message = null, $attachment = null)
    {
        return new self($message, $attachment);
    }

    /**
     * @param string $message
     * @return $this
     */
    public function text($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @param mixed $attachment
     * @return $this
     */
    public function withAttachment($attachment)
    {
        $this->attachment = $attachment;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->message;
    }
}
