<?php

namespace BotMan\BotMan\Messages;

use Illuminate\Support\Collection;
use BotMan\BotMan\Commands\Command;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Interfaces\DriverInterface;
use BotMan\BotMan\Interfaces\MiddlewareInterface;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

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
     * @param IncomingMessage $message
     * @param Answer $answer
     * @param Command $command
     * @param DriverInterface $driver
     * @param MiddlewareInterface[] $middleware
     * @return bool
     */
    public function isMessageMatching(IncomingMessage $message, Answer $answer, Command $command, DriverInterface $driver, $middleware = [])
    {
        return $this->isPatternValid($message, $answer, $command->getPattern(), $command->getMiddleware() + $middleware) &&
            $this->isDriverValid($driver->getName(), $command->getDriver()) &&
            $this->isRecipientValid($message->getRecipient(), $command->getRecipient());
    }

    /**
     * @param IncomingMessage $message
     * @param Answer $answer
     * @param string $pattern
     * @param MiddlewareInterface[] $middleware
     * @return int
     */
    public function isPatternValid(IncomingMessage $message, Answer $answer, $pattern, $middleware = [])
    {
        $this->matches = [];

        $answerText = $answer->getValue();
        if (is_array($answerText)) {
            $answerText = '';
        }

        $pattern = str_replace('/', '\/', $pattern);
        $text = '/^'.preg_replace(self::PARAM_NAME_REGEX, '(.*)', $pattern).'$/iu';

        $regexMatched = (bool) preg_match($text, $message->getText(), $this->matches) || (bool) preg_match($text, $answerText, $this->matches);

        // Try middleware first
        if (count($middleware)) {
            return Collection::make($middleware)->reject(function (MiddlewareInterface $middleware) use (
                    $message,
                    $pattern,
                    $regexMatched
                ) {
                return $middleware->matching($message, $pattern, $regexMatched);
            })->isEmpty() === true;
        }

        return $regexMatched;
    }

    /**
     * @param string $driverName
     * @param string|array $allowedDrivers
     * @return bool
     */
    protected function isDriverValid($driverName, $allowedDrivers)
    {
        if (! is_null($allowedDrivers)) {
            return Collection::make($allowedDrivers)->contains($driverName);
        }

        return true;
    }

    /**
     * @param $givenRecipient
     * @param $allowedRecipient
     * @return bool
     */
    protected function isRecipientValid($givenRecipient, $allowedRecipient)
    {
        return $givenRecipient == $allowedRecipient || $allowedRecipient === null;
    }

    /**
     * @return array
     */
    public function getMatches()
    {
        return array_slice($this->matches, 1);
    }
}
