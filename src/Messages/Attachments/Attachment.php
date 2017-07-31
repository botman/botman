<?php

namespace BotMan\BotMan\Messages\Attachments;

use BotMan\BotMan\Interfaces\WebAccess;

abstract class Attachment implements WebAccess
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
