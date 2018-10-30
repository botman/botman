<?php

namespace BotMan\BotMan\Tests\Fixtures\Middleware;

use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Interfaces\Middleware\Matching as MatchingInterface;
use BotMan\BotMan\Messages\Matcher;

class Matching implements MatchingInterface
{
    /**
     * @param IncomingMessage $message
     * @param string $pattern
     * @param bool $regexMatched Indicator if the regular expression was matched too
     * @param Matcher $matcher The current Matcher instance
     * @return bool
     */
    public function matching(IncomingMessage $message, $pattern, $regexMatched, Matcher $matcher)
    {
        return $message->getText()[0] === 'o';
    }
}
