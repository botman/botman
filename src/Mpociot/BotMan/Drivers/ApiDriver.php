<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\User;
use Mpociot\BotMan\Answer;
use Illuminate\Support\Arr;
use Mpociot\BotMan\Message;
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

    /** @var int */
    protected $replyStatusCode = 200;

    /** @var string */
    protected $errorMessage = '';

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
            'status' => $this->replyStatusCode,
            'messages' => $messages,
        ];

        if ($this->errorMessage) {
            $replyData['error'] = $this->errorMessage;
        }

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
     * Build API reply.
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
                    'text' => $message,
                ];
            }

            return $reply;
        })->toArray();

        return $this->errorMessage ? [] : $replyData;
    }

    /**
     * Build reply payload for a Question object.
     *
     * @param Question $message
     * @return array
     */
    private function buildQuestionPayload(Question $message)
    {
        $questionsData = $message->toArray();
        $reply = [
            'type' => 'text',
            'text' => $questionsData['text'],
        ];

        if ($message->getButtons()) {
            $reply['type'] = 'buttons';
            $reply['buttons'] = $this->generateButtonsPayload($message->getButtons());
        }

        return $reply;
    }

    /**
     * Build reply payload for template objects.
     *
     * @param $message
     * @return array|bool|mixed
     */
    private function buildTemplatePayload($message)
    {
        switch (true) {
            case $message instanceof ButtonTemplate:
                $reply = $this->buildFacebookButtonTemplatePayload($message);
                break;
            case $message instanceof ListTemplate:
                $reply = $this->buildFacebookListTemplatePayload($message);
                break;
            case $message instanceof GenericTemplate:
                $reply = $this->buildFacebookGenericTemplatePayload($message);
                break;
            case $message instanceof ReceiptTemplate:
                $reply = $this->buildFacebookReceiptTemplatePayload($message);
                break;
            default:
                $this->replyStatusCode = 500;
                $this->errorMessage = 'Unknown template.';
                $reply = false;
        }

        return $reply;
    }

    /**
     * Generate payload for Facebook button template.
     *
     * @param ButtonTemplate $message
     * @return mixed
     */
    private function buildFacebookButtonTemplatePayload(ButtonTemplate $message)
    {
        return [
            'type' => 'buttons',
            'text' => Arr::get($message->toArray(), 'attachment.payload.text'),
            'buttons' => $this->generateButtonsPayload(Arr::get($message->toArray(), 'attachment.payload.buttons')),
        ];
    }

    /**
     * Generate payload for Facebook list template.
     *
     * @param $message
     * @return array
     */
    private function buildFacebookListTemplatePayload($message)
    {
        return [
            'type' => 'list',
            'elements' => $this->generateElementsPayload($message),
            'globalButtons' => $this->generateButtonsPayload(Arr::get($message->toArray(),
                'attachment.payload.buttons')),
        ];
    }

    /**
     * Generate payload for Facebook generic list template
     *
     * @param $message
     * @return array
     */
    private function buildFacebookGenericTemplatePayload($message)
    {
        return [
            'type' => 'list',
            'elements' => $this->generateElementsPayload($message),
        ];
    }

    /**
     * Generate payload for Facebook receipt template
     *
     * @param $message
     * @return array
     */
    private function buildFacebookReceiptTemplatePayload($message)
    {
        $payload = Arr::get($message->toArray(), 'attachment.payload');

        return [
            'type' => 'receipt',
            'recipient_name' => $payload['recipient_name'],
            'merchant_name' => $payload['merchant_name'],
            'order_number' => $payload['order_number'],
            'currency' => $payload['currency'],
            'payment_method' => $payload['payment_method'],
            'order_url' => $payload['order_url'],
            'timestamp' => $payload['timestamp'],
            'elements' => $this->generateElementsPayload($message),
            'address' => $payload['address'],
            'summary' => $payload['summary'],
            'adjustments' => $payload['adjustments'],
        ];
    }

    /**
     * @param $message
     * @return array
     */
    private function generateElementsPayload($message)
    {
        $elements = Arr::get($message->toArray(), 'attachment.payload.elements');

        return collect($elements)->map(function ($element) use ($message) {
            $elementArray = [
                'title' => $element['title'],
                'subtitle' => $element['subtitle'],
                'imageUrl' => $element['image_url'],
            ];

            if (isset($element['item_url'])) {
                $elementArray['itemUrl'] = $element['item_url'];
            }

            if (isset($element['buttons']) && count($element['buttons']) > 0) {
                $elementArray['buttons'] = $this->generateButtonsPayload($element['buttons']);
            }

            if ($message instanceof ReceiptTemplate) {
                $elementArray['quantity'] = $element['quantity'];
                $elementArray['price'] = $element['price'];
                $elementArray['currency'] = $element['currency'];
            }

            return $elementArray;
        })->toArray();
    }

    /**
     * @param array $buttons
     * @return array
     */
    private function generateButtonsPayload(array $buttons)
    {
        return collect($buttons)->map(function ($button) {

            switch ($button['type']) {
                case 'button':
                    $button['type'] = 'postback';
                    break;
                case 'url':
                    $button['type'] = 'web_url';
                    break;
            }

            $buttonArray = [
                'type' => $button['type'],
                'text' => isset($button['text']) ? $button['text'] : $button['title'],
            ];

            if ($button['type'] === 'postback') {
                $buttonArray['value'] = isset($button['payload']) ? $button['payload'] : $button['value'];
            } elseif ($button['type'] == 'web_url') {
                $buttonArray['webUrl'] = isset($button['url']) ? $button['url'] : $button['web_url'];
            }

            return $buttonArray;
        })->toArray();
    }

    /**
     * Send pending replies.
     */
    public function afterMessagesHandled()
    {
        if (count($this->replies)) {
            $this->sendResponse();
        }
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
    }
}
