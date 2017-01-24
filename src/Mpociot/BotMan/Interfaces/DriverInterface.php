<?php

namespace Mpociot\BotMan\Interfaces;

use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Mpociot\BotMan\Interfaces\UserInterface;
use Symfony\Component\HttpFoundation\Request;

interface DriverInterface
{
    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest();

    /**
     * Retrieve the chat message(s).
     *
     * @return array
     */
    public function getMessages();

    /**
     * @return bool
     */
    public function isBot();

    /**
     * @return bool
     */
    public function isConfigured();

    /**
     * Retrieve User information
     * @param Message $matchingMessage
     * @return UserInterface
     */
    public function getUser(Message $matchingMessage);

    /**
     * @param Message $matchingMessage
     *
     * @return Answer
     */
    public function getConversationAnswer(Message $message);

    /**
     * @param string|Question $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return $this
     */
    public function reply($message, $matchingMessage, $additionalParameters = []);

    /**
     * Return the driver name.
     *
     * @return string
     */
    public function getName();

    /**
     * Send a typing indicator.
     * @param Message $matchingMessage
     * @return mixed
     */
    public function types(Message $matchingMessage);
}
