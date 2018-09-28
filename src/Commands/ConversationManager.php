<?php

namespace BotMan\BotMan\Commands;

use Illuminate\Support\Collection;
use BotMan\BotMan\Messages\Matcher;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Interfaces\DriverInterface;
use BotMan\BotMan\Messages\Attachments\Audio;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Attachments\Video;
use BotMan\BotMan\Messages\Attachments\Contact;
use BotMan\BotMan\Middleware\MiddlewareManager;
use BotMan\BotMan\Messages\Attachments\Location;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Matching\MatchingMessage;

class ConversationManager
{
    /**
     * Messages to listen to.
     * @var Command[]
     */
    protected $listenTo = [];

    public function listenTo(Command $command)
    {
        $this->listenTo[] = $command;
    }

    /**
     * Add additional data (image,video,audio,location,files) data to
     * callable parameters.
     *
     * @param IncomingMessage $message
     * @param array $parameters
     * @return array
     */
    public function addDataParameters(IncomingMessage $message, array $parameters)
    {
        $messageText = $message->getText();

        if ($messageText === Image::PATTERN) {
            $parameters[] = $message->getImages();
        } elseif ($messageText === Video::PATTERN) {
            $parameters[] = $message->getVideos();
        } elseif ($messageText === Audio::PATTERN) {
            $parameters[] = $message->getAudio();
        } elseif ($messageText === Location::PATTERN) {
            $parameters[] = $message->getLocation();
        } elseif ($messageText === Contact::PATTERN) {
            $parameters[] = $message->getContact();
        } elseif ($messageText === File::PATTERN) {
            $parameters[] = $message->getFiles();
        }

        return $parameters;
    }

    /**
     * @param IncomingMessage[] $messages
     * @param MiddlewareManager $middleware
     * @param Answer $answer
     * @param DriverInterface $driver
     * @param bool $withReceivedMiddleware
     * @return array|MatchingMessage[]
     */
    public function getMatchingMessages($messages, MiddlewareManager $middleware, Answer $answer, DriverInterface $driver, $withReceivedMiddleware = true) : array
    {
        $matcher = new Matcher();
        $messages = Collection::make($messages)->reject(function (IncomingMessage $message) {
            return $message->isFromBot();
        });

        $matchingMessages = [];
        foreach ($messages as $message) {
            if ($withReceivedMiddleware) {
                $message = $middleware->applyMiddleware('received', $message);
            }

            foreach ($this->listenTo as $command) {
                if ($matcher->isMessageMatching($message, $answer, $command, $driver, $middleware->matching())) {
                    $matchingMessages[] = new MatchingMessage($command, $message, $matcher->getMatches());
                }
            }
        }

        return $matchingMessages;
    }
}
