<?php

namespace BotMan\BotMan\Messages\Attachments;

use BotMan\BotMan\Interfaces\WebAccess;
use Illuminate\Support\Collection;

abstract class Attachment implements WebAccess
{
    /** @var mixed */
    protected $payload;

    /** @var array */
    protected $extras = [];

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

    /**
     * @param string $key
     * @param mixed $value
     * @return Attachment
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
}
