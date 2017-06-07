<?php

namespace BotMan\BotMan\Interfaces;

interface DriverEventInterface
{
    /**
     * @param $payload
     */
    public function __construct($payload);

    /**
     * Return the event name to match.
     *
     * @return string
     */
    public function getName();

    /**
     * Return the event payload.
     *
     * @return mixed
     */
    public function getPayload();
}
