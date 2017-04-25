<?php

namespace Mpociot\BotMan\Interfaces;

interface DriverEventInterface
{
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
