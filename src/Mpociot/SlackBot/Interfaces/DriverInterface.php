<?php

namespace Mpociot\SlackBot\Interfaces;


use Mpociot\SlackBot\Message;
use Mpociot\SlackBot\Question;
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
     * @param Request $request
     * @return void
     */
    public function buildPayload(Request $request);

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
}