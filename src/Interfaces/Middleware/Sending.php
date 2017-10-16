<?php

namespace BotMan\BotMan\Interfaces\Middleware;

use BotMan\BotMan\BotMan;

interface Sending
{
    /**
     * Handle an outgoing message payload before/after it
     * hits the message service.
     *
     * @param mixed $payload
     * @param callable $next
     * @param BotMan $bot
     *
     * @return mixed
     */
    public function sending($payload, $next, BotMan $bot);
}
