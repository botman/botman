<?php

namespace Mpociot\BotMan\Messages\Conversations;

use Closure;
use Mpociot\BotMan\BotMan;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Interfaces\ShouldQueue;
use Mpociot\BotMan\Messages\Attachments\Audio;
use Mpociot\BotMan\Messages\Attachments\Image;
use Mpociot\BotMan\Messages\Attachments\Video;
use Mpociot\BotMan\Messages\Outgoing\Question;
use Mpociot\BotMan\Messages\Attachments\Location;
use Mpociot\BotMan\Messages\Incoming\IncomingMessage;

/**
 * Class Conversation.
 */
abstract class Conversation
{
    /**
     * @var BotMan
     */
    protected $bot;

    /**
     * @var string
     */
    protected $token;

    /**
     * @param BotMan $bot
     */
    public function setBot(BotMan $bot)
    {
        $this->bot = $bot;
    }

    /**
     * @param string|Question $question
     * @param array|Closure $next
     * @param array $additionalParameters
     * @return $this
     */
    public function ask($question, $next, $additionalParameters = [])
    {
        $this->bot->reply($question, $additionalParameters);
        $this->bot->storeConversation($this, $next, $question, $additionalParameters);

        return $this;
    }

    /**
     * @param string|\Mpociot\BotMan\Messages\Outgoing\Question $question
     * @param array|Closure $next
     * @param array|Closure $repeat
     * @param array $additionalParameters
     * @return $this
     */
    public function askForImages($question, $next, $repeat = null, $additionalParameters = [])
    {
        $additionalParameters['__getter'] = 'getImages';
        $additionalParameters['__pattern'] = Image::PATTERN;
        $additionalParameters['__repeat'] = ! is_null($repeat) ? $this->bot->serializeClosure($repeat) : $repeat;

        return $this->ask($question, $next, $additionalParameters);
    }

    /**
     * @param string|\Mpociot\BotMan\Messages\Outgoing\Question $question
     * @param array|Closure $next
     * @param array|Closure $repeat
     * @param array $additionalParameters
     * @return $this
     */
    public function askForVideos($question, $next, $repeat = null, $additionalParameters = [])
    {
        $additionalParameters['__getter'] = 'getVideos';
        $additionalParameters['__pattern'] = Video::PATTERN;
        $additionalParameters['__repeat'] = ! is_null($repeat) ? $this->bot->serializeClosure($repeat) : $repeat;

        return $this->ask($question, $next, $additionalParameters);
    }

    /**
     * @param string|\Mpociot\BotMan\Messages\Outgoing\Question $question
     * @param array|Closure $next
     * @param array|Closure $repeat
     * @param array $additionalParameters
     * @return $this
     */
    public function askForAudio($question, $next, $repeat = null, $additionalParameters = [])
    {
        $additionalParameters['__getter'] = 'getAudio';
        $additionalParameters['__pattern'] = Audio::PATTERN;
        $additionalParameters['__repeat'] = ! is_null($repeat) ? $this->bot->serializeClosure($repeat) : $repeat;

        return $this->ask($question, $next, $additionalParameters);
    }

    /**
     * @param string|\Mpociot\BotMan\Messages\Outgoing\Question $question
     * @param array|Closure $next
     * @param array|Closure $repeat
     * @param array $additionalParameters
     * @return $this
     */
    public function askForLocation($question, $next, $repeat = null, $additionalParameters = [])
    {
        $additionalParameters['__getter'] = 'getLocation';
        $additionalParameters['__pattern'] = Location::PATTERN;
        $additionalParameters['__repeat'] = ! is_null($repeat) ? $this->bot->serializeClosure($repeat) : $repeat;

        return $this->ask($question, $next, $additionalParameters);
    }

    /**
     * Repeat the previously asked question.
     * @param string|Question $question
     */
    public function repeat($question = '')
    {
        $conversation = $this->bot->getStoredConversation();

        if (! $question instanceof Question && ! $question) {
            $question = unserialize($conversation['question']);
        }

        $next = $conversation['next'];
        $additionalParameters = unserialize($conversation['additionalParameters']);

        if (is_string($next)) {
            $next = unserialize($next)->getClosure();
        } elseif (is_array($next)) {
            $next = Collection::make($next)->map(function ($callback) {
                if ($this->bot->getDriver()->serializesCallbacks() && ! $this->bot->runsOnSocket()) {
                    $callback['callback'] = unserialize($callback['callback'])->getClosure();
                }

                return $callback;
            })->toArray();
        }
        $this->ask($question, $next, $additionalParameters);
    }

    /**
     * @param string|\Mpociot\BotMan\Messages\Outgoing\Question $message
     * @param array $additionalParameters
     * @return $this
     */
    public function say($message, $additionalParameters = [])
    {
        $this->bot->reply($message, $additionalParameters);

        return $this;
    }

    /**
     * Should the conversation be skipped (temporarily).
     * @param  IncomingMessage $message
     * @return bool
     */
    public function skipsConversation(IncomingMessage $message)
    {
        //
    }

    /**
     * Should the conversation be removed and stopped (permanently).
     * @param  IncomingMessage $message
     * @return bool
     */
    public function stopsConversation(IncomingMessage $message)
    {
        //
    }

    /**
     * @return mixed
     */
    abstract public function run();

    /**
     * @return array
     */
    public function __sleep()
    {
        $properties = get_object_vars($this);
        if (! $this instanceof ShouldQueue) {
            unset($properties['bot']);
        }

        return array_keys($properties);
    }
}
