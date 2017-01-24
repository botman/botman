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
     * @return $this
     */
    public function reply($message, $matchingMessage, $additionalParameters = [])
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
}
