<?php
/**
 * Created by PhpStorm.
 * User: marcel
 * Date: 27/11/2016
 * Time: 11:52
 */

namespace Mpociot\SlackBot\Drivers;

use Illuminate\Support\Collection;
use Mpociot\SlackBot\Answer;
use Mpociot\SlackBot\Message;
use Mpociot\SlackBot\Question;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class FacebookDriver extends Driver
{
    /** @var Collection|ParameterBag */
    protected $payload;

    /** @var Collection */
    protected $event;

    /**
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        /*
         * If the request has a POST parameter called 'payload'
         * we're dealing with an interactive button response.
         */
        if (! is_null($request->get('payload'))) {
            $payloadData = json_decode($request->get('payload'), true);

            $this->payload = collect($payloadData);
            $this->event = collect([
                'channel' => $payloadData['channel']['id'],
                'user' => $payloadData['user']['id'],
            ]);
        } else {
            $this->payload = new ParameterBag((array) json_decode($request->getContent(), true));
            $this->event = collect((array)$this->payload->get('entry')[0]);
        }
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return $this->event->has('messaging');
    }

    /**
     * @return Answer
     */
    public function getConversationAnswer()
    {
        if ($this->payload instanceof Collection) {
            return Answer::create($this->payload['actions'][0]['name'])
                ->setValue($this->payload['actions'][0]['value'])
                ->setCallbackId($this->payload['callback_id']);
        }

        return Answer::create($this->event->get('text'));
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        if (! $this->payload instanceof Collection) {
            $messages = collect($this->event->get('messaging'));
            return $messages->transform(function($msg) {
                return new Message($msg['message']['text'], $msg['sender']['id'], $msg['recipient']['id']);
            })->toArray();
            //$messageText = $messages->first()['message']['text'];
        }

        return [new Message('', '', '')];
    }

    /**
     * @return bool
     */
    public function isBot()
    {
        // Facebook bot replies don't get returned
        return false;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->event->get('user');
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->event->get('channel');
    }

    /**
     * @param string|Question $message
     * @param array $additionalParameters
     * @return $this
     */
    public function reply($message, $additionalParameters = [])
    {
        $parameters = array_merge([
            'token' => $this->payload->get('token'),
            'channel' => $this->getChannel(),
            'text' => $message,
        ], $additionalParameters);
        /*
         * If we send a Question with buttons, ignore
         * the text and append the question.
         */
        if ($message instanceof Question) {
            $parameters['text'] = '';
            $parameters['attachments'] = json_encode([$message->toArray()]);
        }
        $this->commander->execute('chat.postMessage', $parameters);
    }
}