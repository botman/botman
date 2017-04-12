<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\User;
use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;
use Mpociot\BotMan\Messages\Message as IncomingMessage;

class SlackDriver extends Driver
{
    const DRIVER_NAME = 'Slack';

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

            $this->payload = Collection::make($payloadData);
            $this->event = Collection::make([
                'channel' => $payloadData['channel']['id'],
                'user' => $payloadData['user']['id'],
            ]);
        } elseif (! is_null($request->get('team_domain'))) {
            $this->payload = $request->request;
            $this->event = Collection::make($request->request->all());
        } else {
            $this->payload = new ParameterBag((array) json_decode($request->getContent(), true));
            $this->event = Collection::make($this->payload->get('event'));
        }
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return ! is_null($this->event->get('user')) || ! is_null($this->event->get('team_domain'));
    }

    /**
     * @param  Message $message
     * @return Answer
     */
    public function getConversationAnswer(Message $message)
    {
        if ($this->payload instanceof Collection) {
            return Answer::create($this->payload['actions'][0]['name'])
                ->setInteractiveReply(true)
                ->setValue($this->payload['actions'][0]['value'])
                ->setMessage($message)
                ->setCallbackId($this->payload['callback_id']);
        }

        return Answer::create($this->event->get('text'))->setMessage($message);
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        $messageText = '';
        if (! $this->payload instanceof Collection) {
            $messageText = $this->event->get('text');
            if ($this->isSlashCommand()) {
                $messageText = $this->event->get('command').' '.$messageText;
            }
        }

        $user_id = $this->event->get('user');
        if ($this->event->has('user_id')) {
            $user_id = $this->event->get('user_id');
        }

        $channel_id = $this->event->get('channel');
        if ($this->event->has('channel_id')) {
            $channel_id = $this->event->get('channel_id');
        }

        return [new Message($messageText, $user_id, $channel_id, $this->event)];
    }

    /**
     * @return bool
     */
    public function isBot()
    {
        return $this->event->has('bot_id');
    }

    /**
     * @return bool
     */
    protected function isSlashCommand()
    {
        return $this->event->has('command');
    }

    /**
     * @param string|Question $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return $this|void
     */
    public function reply($message, $matchingMessage, $additionalParameters = [])
    {
        if (! Collection::make($matchingMessage->getPayload())->has('team_domain')) {
            $this->replyWithToken($message, $matchingMessage, $additionalParameters);
        } elseif ($this->isSlashCommand()) {
            $this->respondText($message, $matchingMessage, $additionalParameters);
        } else {
            $this->respondJSON($message, $matchingMessage, $additionalParameters);
        }
    }

    /**
     * @param $message
     * @param array $additionalParameters
     * @param Message $matchingMessage
     * @return $this
     */
    public function replyInThread($message, $additionalParameters, $matchingMessage)
    {
        $additionalParameters['thread_ts'] = $matchingMessage->getPayload()->get('ts');

        return $this->reply($message, $matchingMessage, $additionalParameters);
    }

    /**
     * @param string|Question $message
     * @param Message $matchingMessage
     * @param array $parameters
     * @return $this
     */
    protected function respondJSON($message, $matchingMessage, $parameters = [])
    {
        /*
         * If we send a Question with buttons, ignore
         * the text and append the question.
         */
        if ($message instanceof Question) {
            $parameters['text'] = $this->format($message->getText());
            $parameters['attachments'] = json_encode([$message->toArray()]);
        } elseif ($message instanceof IncomingMessage) {
            $parameters['text'] = $message->getMessage();
            if (! is_null($message->getImage())) {
                $parameters['attachments'] = json_encode(['image_url' => $message->getImage()]);
            }
        } else {
            $parameters['text'] = $this->format($message);
        }

        Response::create(json_encode($parameters))->send();
    }

    /**
     * @param string|Question $message
     * @param Message $matchingMessage
     * @param array $parameters
     * @return $this
     */
    protected function respondText($message, $matchingMessage, $parameters = [])
    {
        /*
         * If we send a Question with buttons, ignore
         * the text and append the question.
         */
        if ($message instanceof Question) {
            $text = $this->format($message->getText());
        } elseif ($message instanceof IncomingMessage) {
            $text = $message->getMessage();
        } else {
            $text = $this->format($message);
        }

        Response::create($text)->send();
    }

    /**
     * @param string|Question|IncomingMessage $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return Response
     */
    protected function replyWithToken($message, $matchingMessage, $additionalParameters = [])
    {
        $parameters = array_merge_recursive([
            'token' => $this->payload->get('token'),
            'channel' => $matchingMessage->getChannel(),
        ], $additionalParameters);
        /*
         * If we send a Question with buttons, ignore
         * the text and append the question.
         */
        if ($message instanceof Question) {
            $parameters['text'] = '';
            $parameters['attachments'] = json_encode([$message->toArray()]);
        } elseif ($message instanceof IncomingMessage) {
            $parameters['text'] = $message->getMessage();
            if (! is_null($message->getImage())) {
                $parameters['attachments'] = json_encode(['image_url' => $message->getImage()]);
            }
        } else {
            $parameters['text'] = $this->format($message);
        }

        $parameters['token'] = $this->config->get('slack_token');

        return $this->http->post('https://slack.com/api/chat.postMessage', [], $parameters);
    }

    /**
     * Formats a string for Slack.
     *
     * @param  string $string
     * @return string
     */
    private function format($string)
    {
        $string = str_replace('&', '&amp;', $string);
        $string = str_replace('<', '&lt;', $string);
        $string = str_replace('>', '&gt;', $string);

        return $string;
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return ! is_null($this->config->get('slack_token'));
    }

    /**
     * Retrieve User information.
     * @param Message $matchingMessage
     * @return User
     */
    public function getUser(Message $matchingMessage)
    {
        return new User($matchingMessage->getUser());
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
            'token' => $this->config->get('slack_token'),
        ], $parameters);

        return $this->http->post('https://slack.com/api/'.$endpoint, [], $parameters);
    }
}
