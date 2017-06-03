<?php

namespace Mpociot\BotMan\Tests\Fixtures;

use Mpociot\BotMan\User;
use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Mpociot\BotMan\Interfaces\DriverInterface;

class TestDriver implements DriverInterface
{
    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return true;
    }

    /**
     * Retrieve the chat message(s).
     *
     * @return array
     */
    public function getMessages()
    {
        return [new Message('', '', '')];
    }

    /**
     * @return bool
     */
    public function isBot()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return true;
    }

    /**
     * @param Message $matchingMessage
     *
     * @return Answer
     */
    public function getConversationAnswer(Message $message)
    {
        return Answer::create();
    }

    /**
     * @param string|Question $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return mixed
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        return [];
    }

    /**
     * @param mixed $payload
     * @return mixed
     */
    public function sendPayload($payload)
    {
        return $this;
    }

    /**
     * @param Message $matchingMessage
     * @return string
     */
    public function types(Message $matchingMessage)
    {
    }

    /**
     * @return bool
     */
    public function hasMatchingEvent()
    {
        return false;
    }

    /**
     * Return the driver name.
     *
     * @return string
     */
    public function getName()
    {
        return 'Test';
    }

    public function dummyMethod()
    {
    }

    /**
     * Retrieve User information.
     * @param Message $matchingMessage
     * @return UserInterface
     */
    public function getUser(Message $matchingMessage)
    {
        return new User();
    }

    /**
     * Tells if the stored conversation callbacks are serialized.
     *
     * @return bool
     */
    public function serializesCallbacks()
    {
        return false;
    }
}
