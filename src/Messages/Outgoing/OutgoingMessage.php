<?php

namespace BotMan\BotMan\Messages\Outgoing;

use BotMan\BotMan\Interfaces\TranslatableInterface;
use BotMan\BotMan\Messages\Attachments\Attachment;

class OutgoingMessage implements TranslatableInterface
{
    /** @var string */
    protected $message;

    /** @var \BotMan\BotMan\Messages\Attachments\Attachment */
    protected $attachment;

    /** @var bool */
    protected $isTranslated;

    /**
     * IncomingMessage constructor.
     * @param string $message
     * @param Attachment $attachment
     */
    public function __construct($message = null, Attachment $attachment = null)
    {
        $this->message = $message;
        $this->attachment = $attachment;
    }

    /**
     * @param string $message
     * @param Attachment $attachment
     * @return OutgoingMessage
     */
    public static function create($message = null, Attachment $attachment = null)
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
     * @param \BotMan\BotMan\Messages\Attachments\Attachment $attachment
     * @return $this
     */
    public function withAttachment(Attachment $attachment)
    {
        $this->attachment = $attachment;

        return $this;
    }

    /**
     * @return \BotMan\BotMan\Messages\Attachments\Attachment
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

    /**
     * @param callable $callable
     */
    public function translate(callable $callable)
    {
        if ($this->attachment instanceof TranslatableInterface) {
            $this->attachment->translate($callable);
        }
        if ($this->isTranslated) {
            return;
        }

        $this->message = $callable($this->message);
        $this->isTranslated = true;
    }
}
