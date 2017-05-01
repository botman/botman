<?php

namespace Mpociot\BotMan\Drivers\Nexmo;

use Mpociot\BotMan\User;
use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Drivers\Driver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Mpociot\BotMan\Messages\Message as IncomingMessage;

class NexmoDriver extends Driver
{
    const DRIVER_NAME = 'Nexmo';

    /**
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        $this->payload = $request->request->all();
        $this->event = Collection::make($this->payload);
    }

    /**
     * @param Message $matchingMessage
     * @return User
     */
    public function getUser(Message $matchingMessage)
    {
        return new User($matchingMessage->getSender());
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return ! is_null($this->event->get('msisdn'));
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
            new Message($this->event->get('text'), $this->event->get('msisdn'), $this->event->get('to'), $this->payload),
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
     * @return Response
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        $parameters = array_merge_recursive([
            'api_key' => $this->config->get('nexmo_key'),
            'api_secret' => $this->config->get('nexmo_secret'),
            'to' => $matchingMessage->getSender(),
            'from' => $matchingMessage->getRecipient(),
        ], $additionalParameters);
        /*
         * If we send a Question with buttons, ignore
         * the text and append the question.
         */
        if ($message instanceof Question) {
            $parameters['text'] = $message->getText();
        } elseif ($message instanceof IncomingMessage) {
            $parameters['text'] = $message->getText();
        } else {
            $parameters['text'] = $message;
        }

        return $parameters;
    }

    /**
     * @param mixed $payload
     * @return Response
     */
    public function sendPayload($payload)
    {
        return $this->http->post('https://rest.nexmo.com/sms/json?'.http_build_query($payload));
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return ! empty($this->config->get('nexmo_key')) && ! empty($this->config->get('nexmo_secret'));
    }

    /**
     * Low-level method to perform driver specific API requests.
     *
     * @param string $endpoint
     * @param array $parameters
     * @param Message $matchingMessage
     * @return Response
     */
    public function sendRequest($endpoint, array $parameters, Message $matchingMessage)
    {
        $parameters = array_replace_recursive([
            'api_key' => $this->config->get('nexmo_key'),
            'api_secret' => $this->config->get('nexmo_secret'),
        ], $parameters);

        return $this->http->post('https://rest.nexmo.com/'.$endpoint.'?'.http_build_query($parameters));
    }
}
