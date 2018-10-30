<?php

namespace BotMan\BotMan\Interfaces\Middleware;

use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Matcher;

interface Matching
{
    /**
     * @param IncomingMessage $message
     * @param string $pattern
     * @param bool $regexMatched Indicator if the regular expression was matched too
     * @param Matcher $matcher The current Matcher instance
     * @return bool
     */
    public function matching(IncomingMessage $message, $pattern, $regexMatched, Matcher $matcher);
}
