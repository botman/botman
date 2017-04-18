<?php

namespace Mpociot\BotMan\Messages;

use Mpociot\Botman\Message;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Interfaces\MiddlewareInterface;

class Matcher
{

    /**
     * regular expression to capture named parameters but not quantifiers
     * captures {name}, but not {1}, {1,}, or {1,2}.
     */
    const PARAM_NAME_REGEX = '/\{((?:(?!\d+,?\d+?)\w)+?)\}/';

    /** @var array */
    protected $matches;

    /**
     * @param Message $message
     * @param string $answerText
     * @param string $pattern
     * @param MiddlewareInterface[] $middleware
     * @return int
     */
    public function isMessageMatching(Message $message, $answerText, $pattern, $middleware = [])
    {
        $this->matches = [];

        $messageText = $message->getText();
        if (is_array($answerText)) {
            $answerText = '';
        }

        $pattern = str_replace('/', '\/', $pattern);
        $text = '/^'.preg_replace(self::PARAM_NAME_REGEX, '(.*)', $pattern).'$/iu';
        $regexMatched = (bool) preg_match($text, $messageText, $this->matches) || (bool) preg_match($text, $answerText, $this->matches);

        // Try middleware first
        if (count($middleware)) {
            return Collection::make($middleware)->reject(function ($middleware) use ($message, $pattern, $regexMatched) {
                return $middleware->isMessageMatching($message, $pattern, $regexMatched);
            })->isEmpty() === true;
        }

        return $regexMatched;
    }

    /**
     * @return array
     */
    public function getMatches()
    {
        return array_slice($this->matches, 1);
    }
}
