<?php

namespace BotMan\BotMan\Interfaces;

use BotMan\BotMan\Interfaces\Middleware\Heard;
use BotMan\BotMan\Interfaces\Middleware\Sending;
use BotMan\BotMan\Interfaces\Middleware\Captured;
use BotMan\BotMan\Interfaces\Middleware\Matching;
use BotMan\BotMan\Interfaces\Middleware\Received;

interface MiddlewareInterface extends Captured, Received, Matching, Heard, Sending
{
    //
}
