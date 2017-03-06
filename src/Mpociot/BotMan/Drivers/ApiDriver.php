<?php

namespace Mpociot\BotMan\Drivers;

use Illuminate\Support\Facades\Log;
use Mpociot\BotMan\User;
use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
use Illuminate\Support\Arr;
use Mpociot\BotMan\Question;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Facebook\ListTemplate;
use Mpociot\BotMan\Facebook\ButtonTemplate;
use Mpociot\BotMan\Facebook\GenericTemplate;
use Mpociot\BotMan\Facebook\ReceiptTemplate;
use Mpociot\BotMan\Interfaces\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;
use Mpociot\BotMan\Messages\Message as IncomingMessage;

class ApiDriver extends Driver
{
    /** @var Collection|ParameterBag */
    protected $payload;

    /** @var Collection */
    protected $event;

    /** @var array */
    protected $replies = [];

    /** @var array */
    protected $templates = [
        ButtonTemplate::class,
        GenericTemplate::class,
        ListTemplate::class,
        ReceiptTemplate::class,
    ];

    const DRIVER_NAME = 'Api';

    /**
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        $this->payload = $request->request->all();
        $this->event = Collection::make($this->payload);
    }

    /**
     * Return the driver name.
     *
     * @return string
     */
    public function getName()
    {
        return self::DRIVER_NAME;
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return $this->event->get('driver') === 'api';
    }

    /**
     * @param  Message $message
     * @return Answer
     */
    public function getConversationAnswer(Message $message)
    {
        return Answer::create($message->getMessage());
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        $message = $this->event->get('message');
        $userId = $this->event->get('userId');

        return [new Message($message, $userId, $userId, $this->payload)];
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
     * @return $this|void
     */
    public function reply($message, $matchingMessage, $additionalParameters = [])
    {
        $this->replies[] = $message;
    }

    /**
     * Send all pending replies and reset them.
     */
    protected function sendResponse()
    {
        $messages = $this->buildReply($this->replies);

        $this->replies = [];

        $replyData = [
            'status' => 200,
            'messages' => $messages,
        ];

        Response::create(json_encode($replyData), 200, [
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Credentials' => true,
            'Access-Control-Allow-Origin' => '*',
        ])->send();
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return true;
    }

    /**
     * Retrieve User information.
     *
     * @param Message $matchingMessage
     * @return UserInterface
     */
    public function getUser(Message $matchingMessage)
    {
        return new User($matchingMessage->getChannel());
    }

    /**
     * Build API reply
     *
     * @param $messages
     * @return array
     */
    private function buildReply($messages)
    {
        $replyData = collect($messages)->transform(function ($message) {

            if ($message instanceof Question) {
                $reply = $this->buildQuestionPayload($message);
            } elseif (is_object($message) && in_array(get_class($message), $this->templates)) {
                $reply = $this->buildTemplatePayload($message);
            } else {
                $reply = [
                    'type' => 'text',
                    'text' => str_replace('##end##', '', $message),
                ];
            }

            return $reply;
        })->toArray();

        return $replyData;
    }

    /**
     * Build reply payload for a Question object
     *
     * @param Question $message
     * @return array
     */
    private function buildQuestionPayload(Question $message)
    {
        $questionsData = $message->toArray();
        $reply = [
            'type' => 'text',
            'text' => str_replace('##end##', '', $questionsData['text']),
        ];

        if (! empty($message->getButtons())) {
            $reply['type'] = 'buttons';

            $reply['buttons'] = Collection::make($message->getButtons())->map(function ($button) {
                return [
                    'text' => $button['text'],
                    'keyword' => $button['value'],
                    'imageUrl' => $button['image_url'],
                ];
            })->toArray();
        }

        return $reply;
    }

    private function buildTemplatePayload($message)
    {
        $reply = [];

        if ($message instanceof ButtonTemplate) {
            $reply['type'] = 'buttonlist';
            $reply['text'] = str_replace('##end##', '', Arr::get($message->toArray(), 'attachment.payload.text'));
            $reply['buttons'] = Collection::make(Arr::get($message->toArray(),
                'attachment.payload.buttons'))->map(function ($button) {
                $returnArray = [
                    'type' => $button['type'] === 'web_url' ? 'url' : 'postback',
                    'text' => $button['title'],
                    'keyword' => $button['payload'] ?: null,
                ];

                if (isset($button['url'])) {
                    $returnArray['url'] = $button['url'];
                }

                return $returnArray;
            });
        } else {
            $reply = [
                'status' => 500,
                'message' => 'unknown tempalte',
            ];
        }

        return $reply;
    }

    /**
     * Send pending replies.
     */
    public function __destruct()
    {
        if (count($this->replies)) {
            $this->sendResponse();
        }
    }
}
