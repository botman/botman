<?php

namespace Mpociot\BotMan\Drivers\HipChat;

use Mpociot\BotMan\User;
use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Drivers\Driver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;
use Mpociot\BotMan\Messages\Message as IncomingMessage;

class HipChatDriver extends Driver
{
    const DRIVER_NAME = 'HipChat';

    protected $apiURL;

    /**
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        $this->payload = new ParameterBag((array) json_decode($request->getContent(), true));
        $this->event = Collection::make($this->payload->get('item'));
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return ! is_null($this->payload->get('webhook_id')) && $this->payload->get('event') === 'room_message';
    }

    /**
     * @param  Message $message
     * @return Answer
     */
    public function getConversationAnswer(Message $message)
    {
        return Answer::create($message->getText())->setMessage($message);
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        return [
            new Message($this->event->get('message')['message'], $this->event->get('message')['from']['id'],
                $this->event->get('room')['id'], $this->event),
        ];
    }

    /**
     * @return bool
     */
    public function isBot()
    {
        return false;
    }

    /**
     * @param string|Question|IncomingMessage $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return Response|null
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        $parameters = array_merge_recursive([
            'message_format' => 'text',
        ], $additionalParameters);
        /*
         * If we send a Question with buttons, ignore
         * the text and append the question.
         */
        if ($message instanceof Question) {
            $parameters['message'] = $message->getText();
        } elseif ($message instanceof IncomingMessage) {
            $parameters['message'] = $message->getText();
        } else {
            $parameters['message'] = $message;
        }

        $this->apiURL = Collection::make($this->config->get('hipchat_urls', []))->filter(function ($url) use (
            $matchingMessage
        ) {
            return strstr($url, 'room/'.$matchingMessage->getRecipient().'/notification');
        })->first();

        return $parameters;
    }

    /**
     * @param mixed $payload
     * @return Response
     */
    public function sendPayload($payload)
    {
        $headers = [
            'Content-Type:application/json',
        ];

        if (! is_null($this->apiURL)) {
            return $this->http->post($this->apiURL, [], $payload, $headers, true);
        }
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        $urls = $this->config->get('hipchat_urls');

        if (is_array($urls)) {
            $urls = array_filter($urls);
        }

        return ! empty($urls);
    }

    /**
     * Retrieve User information.
     * @param Message $matchingMessage
     * @return User
     */
    public function getUser(Message $matchingMessage)
    {
        $payload = $matchingMessage->getPayload();

        return new User($payload->get('message')['from']['id'], $payload->get('message')['from']['name'], null,
            $payload->get('message')['from']['mention_name']);
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
        //
    }
}
