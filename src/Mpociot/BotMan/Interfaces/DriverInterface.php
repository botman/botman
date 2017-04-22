<?php

namespace Mpociot\BotMan\Interfaces;

use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Symfony\Component\HttpFoundation\Response;

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
     * Retrieve User information.
     * @param Message $matchingMessage
     * @return UserInterface
     */
    public function getUser(Message $matchingMessage);

    /**
     * @param Message $message
     * @return Answer
     */
    public function getConversationAnswer(Message $message);

    /**
     * @param string|Question $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return $this
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = []);

    /**
     * @param mixed $payload
     * @return Response
     */
    public function sendPayload($payload);

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
