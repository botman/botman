<?php

namespace BotMan\BotMan\Messages\Incoming;

class Answer
{
    /** @var string */
    protected $text;

    /** @var string */
    protected $value;

    /** @var string */
    protected $callbackId;

    /** @var IncomingMessage */
    protected $message;

    /** @var bool */
    protected $isInteractiveReply = false;

    /**
     * @param string $text
     * @return static
     */
    public static function create($text = '')
    {
        return new static($text);
    }

    /**
     * @param string $text
     */
    public function __construct($text = '')
    {
        $this->text = $text;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->text;
    }

    /**
     * @return bool
     */
    public function isInteractiveMessageReply()
    {
        return $this->isInteractiveReply;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return string
     */
    public function getCallbackId()
    {
        return $this->callbackId;
    }

    /**
     * @param string $callbackId
     * @return $this
     */
    public function setCallbackId($callbackId)
    {
        $this->callbackId = $callbackId;

        return $this;
    }

    /**
     * @param bool $interactiveReply
     * @return $this
     */
    public function setInteractiveReply($interactiveReply)
    {
        $this->isInteractiveReply = $interactiveReply;

        return $this;
    }

    /**
     * @return IncomingMessage
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param IncomingMessage $message
     * @return $this
     */
    public function setMessage(IncomingMessage $message)
    {
        $this->message = $message;

        return $this;
    }
}
