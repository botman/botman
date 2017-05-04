<?php

namespace Mpociot\BotMan\Drivers\Kik;

use Mpociot\BotMan\Attachments\Image;
use Mpociot\BotMan\Attachments\Video;
use Mpociot\BotMan\Button;
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
        return ! empty($this->config->get('kik_username')) && ! empty($this->config->get('kik_key'));
    }

    /**
     * Retrieve User information.
     * @param Message $matchingMessage
     * @return UserInterface
     */
    public function getUser(Message $matchingMessage)
    {
        $response = $this->http->get('https://api.kik.com/v1/user/'.$matchingMessage->getSender(), [], [
            'Content-Type:application/json',
            'Authorization:Basic '.$this->getRequestCredentials(),
        ]);
        $profileData = json_decode($response->getContent());

        return new User($matchingMessage->getSender(), $profileData->firstName, $profileData->lastName, $matchingMessage->getSender());
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
     * Convert a Question object into a valid Kik
     * keyboard object.
     *
     * @param Question $question
     * @return array
     */
    private function convertQuestion(Question $question)
    {
        $buttons = $question->getButtons();
        if ($buttons) {
            return [
                [
                    'type' => 'suggested',
                    'responses' => Collection::make($buttons)->transform(function($button) {
                        $buttonData = [
                            'type' => 'text',
                            'metadata' => [
                                'value' => $button['value']
                            ]
                        ];
                        if ($button['image_url']) {
                            $buttonData['type'] = 'picture';
                            $buttonData['picUrl'] = $button['image_url'];
                        } else {
                            $buttonData['body'] = $button['text'];
                        }
                        return $buttonData;
                    })->toArray()
                ]
            ];
        }

        return null;
    }

    /**
     * @param OutgoingMessage|Question $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return array
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        $payload = [
            'to' => $matchingMessage->getSender(),
            'chatId' => $matchingMessage->getRecipient(),
        ];

        if ($message instanceof OutgoingMessage) {
            $attachment = $message->getAttachment();
            if ($attachment instanceof Image) {
                $payload['picUrl'] = $attachment->getUrl();
                $payload['type'] = 'picture';
            } elseif ($attachment instanceof Video) {
                $payload['videoUrl'] = $attachment->getUrl();
                $payload['type'] = 'video';
            } else {
                $payload['body'] = $message->getText();
                $payload['type'] = 'text';
            }
        } elseif ($message instanceof Question) {
            $payload['body'] = $message->getText();
            $payload['keyboards'] = $this->convertQuestion($message);
            $payload['type'] = 'text';
        }
\Log::info(print_r($payload,true));
        return [
            'messages' => [$payload],
        ];
    }

    protected function getRequestCredentials()
    {
        return base64_encode($this->config->get('kik_username').':'.$this->config->get('kik_key'));
    }

    /**
     * @param mixed $payload
     * @return Response
     */
    public function sendPayload($payload)
    {
        return $this->http->post('https://api.kik.com/v1/message', [], $payload, [
            'Content-Type:application/json',
            'Authorization:Basic '.$this->getRequestCredentials(),
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
                    'to' => $matchingMessage->getSender(),
                    'type' => 'is-typing',
                    'chatId' => $matchingMessage->getRecipient(),
                    'isTyping' =>  true
                ],
            ],
        ]);
    }
}
