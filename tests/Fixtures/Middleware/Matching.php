<?php

namespace BotMan\BotMan\Tests\Fixtures\Middleware;

use BotMan\BotMan\Interfaces\Middleware\Matching as MatchingInterface;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

class Matching implements MatchingInterface
{
    /**
     * @param IncomingMessage $message
     * @param string $pattern
     * @param bool $regexMatched Indicator if the regular expression was matched too
     * @return bool
     */
    public function matching(IncomingMessage $message, $pattern, $regexMatched)
    {
        return $message->getText()[0] === 'o';
    }
}
