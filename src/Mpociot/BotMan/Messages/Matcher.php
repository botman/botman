<?php

namespace Mpociot\BotMan\Messages;

use Mpociot\Botman\Message;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Interfaces\MiddlewareInterface;

class Matcher
{
    /**
     * Pattern that messages use to identify audio uploads.
     */
    const AUDIO_PATTERN = '%%%_AUDIO_%%%';

    /**
     * Pattern that messages use to identify video uploads.
     */
    const VIDEO_PATTERN = '%%%_VIDEO_%%%';

    /**
     * Pattern that messages use to identify location attachment.
     */
    const LOCATION_PATTERN = '%%%_LOCATION_%%%';

    /**
     * Pattern that messages use to identify image uploads.
     */
    const IMAGE_PATTERN = '%%%_IMAGE_%%%';

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

        $messageText = $message->getMessage();
        if (is_array($answerText)) {
            $answerText = '';
        }

        $pattern = str_replace('/', '\/', $pattern);
        $text = '/^'.preg_replace(self::PARAM_NAME_REGEX, '(.*)', $pattern).'$/iu';
        $regexMatched = (bool) preg_match($text, $messageText, $this->matches) || (bool) preg_match($text, $answerText, $this->matches);

        // Try middleware first
        if (count($middleware)) {
            return Collection::make($middleware)->reject(function ($middleware) use ($message, $pattern, $regexMatched) {
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
    public function isDriverValid($driverName, $allowedDrivers)
    {
        if (! is_null($allowedDrivers)) {
            return Collection::make($allowedDrivers)->contains($driverName);
        }

        return true;
    }

    /**
     * @param $givenChannel
     * @param $allowedChannel
     * @return bool
     */
    public function isChannelValid($givenChannel, $allowedChannel)
    {
        return $givenChannel == $allowedChannel || $allowedChannel === null;
    }

    /**
     * @return array
     */
    public function getMatches()
    {
        return array_slice($this->matches, 1);
    }
}
