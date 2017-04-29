<?php

namespace Mpociot\BotMan\Drivers\Kik;

use Mpociot\BotMan\User;
use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Drivers\Driver;
use Mpociot\BotMan\Interfaces\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Mpociot\BotMan\Messages\Message as OutgoingMessage;

class KikDriver extends Driver
{
    protected $headers = [];

    const DRIVER_NAME = 'Kik';

    /**
     * @param Request $request
     * @return void
     */
    public function buildPayload(Request $request)
    {
        $this->payload = $request->request;
        $this->headers = $request->headers->all();
        $this->event = Collection::make($this->payload->get('messages'));
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
        // TODO: Implement sendRequest() method.
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return isset($this->headers['x-kik-username']) && Collection::make($this->event->first())->has('body');
    }

    /**
     * Retrieve the chat message(s).
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->event->map(function ($message) {
            return new Message($message['body'], $message['from'], $message['chatId']);
        })->toArray();
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
        // TODO: Implement isConfigured() method.
    }

    /**
     * Retrieve User information.
     * @param Message $matchingMessage
     * @return UserInterface
     */
    public function getUser(Message $matchingMessage)
    {
        $response = $this->http->get('https://api.kik.com/v1/user/'.$matchingMessage->getUser(), [], [
            'Content-Type:application/json',
            'Authorization:Basic '.base64_encode(''),
        ]);
        $profileData = json_decode($response->getContent());

        return new User($matchingMessage->getUser(), $profileData->firstName, $profileData->lastName, $matchingMessage->getUser());
    }

    /**
     * @param Message $message
     * @return Answer
     */
    public function getConversationAnswer(Message $message)
    {
        return Answer::create($message->getText())->setMessage($message);
    }

    /**
     * @param OutgoingMessage|Question $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return array
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        return [
            'messages' => [
                [
                    'body' => $message->getText(),
                    'to' => $matchingMessage->getUser(),
                    'type' => 'text',
                    'chatId' => $matchingMessage->getChannel(),
                ],
            ],
        ];
    }

    /**
     * @param mixed $payload
     * @return Response
     */
    public function sendPayload($payload)
    {
        return $this->http->post('https://api.kik.com/v1/message', [], $payload, [
            'Content-Type:application/json',
            'Authorization:Basic '.base64_encode(''),
        ], true);
    }

    /**
     * @param Message $matchingMessage
     * @return void
     */
    public function types(Message $matchingMessage)
    {
        return $this->sendPayload([
            'messages' => [
                [
                    'to' => $matchingMessage->getUser(),
                    'type' => 'text',
                    'chatId' => $matchingMessage->getChannel(),
                ],
            ],
        ]);
    }
}
