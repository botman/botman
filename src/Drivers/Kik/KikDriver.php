<?php

namespace BotMan\BotMan\Drivers\Kik;

use BotMan\BotMan\Users\User;
use Illuminate\Support\Collection;
use BotMan\BotMan\Drivers\HttpDriver;
use BotMan\BotMan\Interfaces\UserInterface;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Attachments\Video;
use BotMan\BotMan\Messages\Outgoing\Question;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;

class KikDriver extends HttpDriver
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
     * @param \BotMan\BotMan\Messages\Incoming\IncomingMessage $matchingMessage
     * @return void
     */
    public function sendRequest($endpoint, array $parameters, IncomingMessage $matchingMessage)
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
            return new IncomingMessage($message['body'], $message['from'], $message['chatId'], $message);
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
     * @param \BotMan\BotMan\Messages\Incoming\IncomingMessage $matchingMessage
     * @return UserInterface
     */
    public function getUser(IncomingMessage $matchingMessage)
    {
        $response = $this->http->get('https://api.kik.com/v1/user/'.$matchingMessage->getSender(), [], [
            'Content-Type:application/json',
            'Authorization:Basic '.$this->getRequestCredentials(),
        ]);
        $profileData = json_decode($response->getContent());

        return new User($matchingMessage->getSender(), $profileData->firstName, $profileData->lastName, $matchingMessage->getSender());
    }

    /**
     * @param \BotMan\BotMan\Messages\Incoming\IncomingMessage $message
     * @return Answer
     */
    public function getConversationAnswer(IncomingMessage $message)
    {
        return Answer::create($message->getText())->setMessage($message);
    }

    /**
     * Convert a Question object into a valid Kik
     * keyboard object.
     *
     * @param \BotMan\BotMan\Messages\Outgoing\Question $question
     * @return array
     */
    private function convertQuestion(Question $question)
    {
        $buttons = $question->getButtons();
        if ($buttons) {
            return [
                [
                    'type' => 'suggested',
                    'responses' => Collection::make($buttons)->transform(function ($button) {
                        $buttonData = [
                            'type' => 'text',
                            'metadata' => [
                                'value' => $button['value'],
                            ],
                        ];
                        if ($button['image_url']) {
                            $buttonData['type'] = 'picture';
                            $buttonData['picUrl'] = $button['image_url'];
                        } else {
                            $buttonData['body'] = $button['text'];
                        }

                        return $buttonData;
                    })->toArray(),
                ],
            ];
        }
    }

    /**
     * @param OutgoingMessage|\BotMan\BotMan\Messages\Outgoing\Question $message
     * @param \BotMan\BotMan\Messages\Incoming\IncomingMessage $matchingMessage
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
     * @param \BotMan\BotMan\Messages\Incoming\IncomingMessage $matchingMessage
     * @return void
     */
    public function types(IncomingMessage $matchingMessage)
    {
        return $this->sendPayload([
            'messages' => [
                [
                    'to' => $matchingMessage->getSender(),
                    'type' => 'is-typing',
                    'chatId' => $matchingMessage->getRecipient(),
                    'isTyping' => true,
                ],
            ],
        ]);
    }
}
