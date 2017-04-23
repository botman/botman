<?php

namespace Mpociot\BotMan\Attachments;

abstract class Attachment
{

    /** @var mixed */
    protected $payload;

    /**
     * Attachment constructor.
     * @param mixed $payload
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }
}
