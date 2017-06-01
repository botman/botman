<?php

namespace Mpociot\BotMan\Drivers\Slack;

use Mpociot\BotMan\User;
use Mpociot\BotMan\Answer;
use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Attachments\Image;
use Mpociot\BotMan\Drivers\HttpDriver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;
use Mpociot\BotMan\Messages\Message as IncomingMessage;

class SlackDriver extends HttpDriver
{
    const DRIVER_NAME = 'Slack';

    const RESULT_TOKEN = 'token';

    const RESULT_JSON = 'json';

    protected $resultType = self::RESULT_JSON;

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
            $action = Collection::make($this->payload['actions'][0]);
            $name = $action->get('name');
            if ($action->get('type') === 'select') {
                $value = $action->get('selected_options');
            } else {
                $value = $action->get('value');
            }

            return Answer::create($name)
                ->setInteractiveReply(true)
                ->setValue($value)
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
     * Convert a Question object into a valid Slack response.
     *
     * @param Question $question
     * @return array
     */
    private function convertQuestion(Question $question)
    {
        $questionData = $question->toArray();

        $buttons = Collection::make($question->getButtons())->map(function ($button) {
            return array_merge([
                'name' => $button['name'],
                'text' => $button['text'],
                'image_url' => $button['image_url'],
                'type' => $button['type'],
                'value' => $button['value'],
            ], $button['additional']);
        })->toArray();
        $questionData['actions'] = $buttons;

        return $questionData;
    }

    /**
     * @param string|Question $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return array
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        if (! Collection::make($matchingMessage->getPayload())->has('team_domain')) {
            $this->resultType = self::RESULT_TOKEN;
            $payload = $this->replyWithToken($message, $matchingMessage, $additionalParameters);
        } else {
            $this->resultType = self::RESULT_JSON;
            $payload = $this->respondJSON($message, $matchingMessage, $additionalParameters);
        }

        return $payload;
    }

    /**
     * @param mixed $payload
     * @return Response
     */
    public function sendPayload($payload)
    {
        if ($this->resultType == self::RESULT_TOKEN) {
            return $this->http->post('https://slack.com/api/chat.postMessage', [], $payload);
        }

        return Response::create(json_encode($payload), 200, ['Content-Type', 'application/json'])->send();
    }

    /**
     * @param $message
     * @param array $additionalParameters
     * @param Message $matchingMessage
     * @return array
     */
    public function replyInThread($message, $additionalParameters, $matchingMessage, BotMan $bot)
    {
        $additionalParameters['thread_ts'] = ! empty($matchingMessage->getPayload()->get('thread_ts'))
            ? $matchingMessage->getPayload()->get('thread_ts')
            : $matchingMessage->getPayload()->get('ts');

        $payload = $this->buildServicePayload($message, $matchingMessage, $additionalParameters);

        return $bot->sendPayload($payload);
    }

    /**
     * @param string|Question $message
     * @param Message $matchingMessage
     * @param array $parameters
     * @return array
     */
    protected function respondJSON($message, $matchingMessage, $parameters = [])
    {
        /*
         * If we send a Question with buttons, ignore
         * the text and append the question.
         */
        if ($message instanceof Question) {
            $parameters['text'] = $this->format($message->getText());
            $parameters['attachments'] = json_encode([$this->convertQuestion($message)]);
        } elseif ($message instanceof IncomingMessage) {
            $parameters['text'] = $message->getText();
            $attachment = $message->getAttachment();
            if (! is_null($attachment)) {
                if ($attachment instanceof Image) {
                    $parameters['attachments'] = json_encode(['image_url' => $attachment->getUrl()]);
                }
            }
        } else {
            $parameters['text'] = $this->format($message);
        }

        return $parameters;
    }

    /**
     * @param string|Question|IncomingMessage $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return array
     */
    protected function replyWithToken($message, $matchingMessage, $additionalParameters = [])
    {
        $parameters = array_merge_recursive([
            'token' => $this->payload->get('token'),
            'channel' => $matchingMessage->getSender() === '' ? $matchingMessage->getRecipient() : $matchingMessage->getSender(),
        ], $additionalParameters);
        /*
         * If we send a Question with buttons, ignore
         * the text and append the question.
         */
        if ($message instanceof Question) {
            $parameters['text'] = '';
            $parameters['attachments'] = json_encode([$this->convertQuestion($message)]);
        } elseif ($message instanceof IncomingMessage) {
            $parameters['text'] = $message->getText();
            $attachment = $message->getAttachment();
            if (! is_null($attachment)) {
                if ($attachment instanceof Image) {
                    $parameters['attachments'] = json_encode(['image_url' => $attachment->getUrl()]);
                }
            }
        } else {
            $parameters['text'] = $this->format($message);
        }

        $parameters['token'] = $this->config->get('slack_token');

        return $parameters;
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
        return ! empty($this->config->get('slack_token'));
    }

    /**
     * Retrieve User information.
     * @param Message $matchingMessage
     * @return User
     */
    public function getUser(Message $matchingMessage)
    {
        $response = $this->sendRequest('users.info', [
            'user' => $matchingMessage->getSender(),
        ], $matchingMessage);
        try {
            $content = json_decode($response->getContent());

            return new User($content->user->id, $content->user->profile->first_name, $content->user->profile->last_name, $content->user->name);
        } catch (\Exception $e) {
            return new User($matchingMessage->getSender());
        }
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
