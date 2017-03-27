<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\User;
use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Symfony\Component\HttpFoundation\Request;

class NullDriver extends Driver
{
    /**
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
    }

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
     * Return the driver name.
     *
     * @return string
     */
    public function getName()
    {
        return '';
    }

    /**
     * @param Message $message
     *
     * @return Answer
     */
    public function getConversationAnswer(Message $message)
    {
        return Answer::create('')->setMessage($message);
    }

    /**
     * Retrieve the chat message.
     *
     * @return string
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
     * @param string|Question $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return $this
     */
    public function reply($message, $matchingMessage, $additionalParameters = [])
    {
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return false;
    }

    /**
     * Retrieve User information.
     * @param Message $matchingMessage
     * @return User
     */
    public function getUser(Message $matchingMessage)
    {
        return new User();
    }

    /**
     * Low-level method to perform driver specific API requests.
     *
     * @param string $endpoint
     * @param array $parameters
     * @param Message $matchingMessage
     * @return void
     */
    public function sendRequest($endpoint, array $parameters, Message $matchingMessage)
    {
    }
}
