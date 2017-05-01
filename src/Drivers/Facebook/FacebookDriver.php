<?php

namespace Mpociot\BotMan\Drivers\Facebook;

use Mpociot\BotMan\User;
use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Drivers\Driver;
use Mpociot\BotMan\Attachments\File;
use Mpociot\BotMan\Attachments\Audio;
use Mpociot\BotMan\Attachments\Image;
use Mpociot\BotMan\Attachments\Video;
use Mpociot\BotMan\Facebook\ListTemplate;
use Mpociot\BotMan\Attachments\Attachment;
use Mpociot\BotMan\Facebook\ButtonTemplate;
use Mpociot\BotMan\Facebook\GenericTemplate;
use Mpociot\BotMan\Facebook\ReceiptTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;
use Mpociot\BotMan\Messages\Message as IncomingMessage;

class FacebookDriver extends Driver
{
    /** @var string */
    protected $signature;

    /** @var string */
    protected $content;

    /** @var array */
    protected $templates = [
        ButtonTemplate::class,
        GenericTemplate::class,
        ListTemplate::class,
        ReceiptTemplate::class,
    ];

    private $supportedAttachments = [
        Video::class,
        Audio::class,
        Image::class,
        File::class,
    ];

    protected $facebookProfileEndpoint = 'https://graph.facebook.com/v2.6/';

    const DRIVER_NAME = 'Facebook';

    /**
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        $this->payload = new ParameterBag((array) json_decode($request->getContent(), true));
        $this->event = Collection::make((array) $this->payload->get('entry')[0]);
        $this->signature = $request->headers->get('X_HUB_SIGNATURE', '');
        $this->content = $request->getContent();
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        $validSignature = ! $this->config->has('facebook_app_secret') || $this->validateSignature();
        $messages = Collection::make($this->event->get('messaging'))->filter(function ($msg) {
            return isset($msg['message']) && isset($msg['message']['text']);
        });

        return ! $messages->isEmpty() && $validSignature;
    }

    /**
     * @return bool|mixed
     */
    public function hasMatchingEvent()
    {
        $event = Collection::make($this->event->get('messaging'))->transform(function ($msg) {
            return Collection::make($msg)->except(['sender', 'recipient', 'timestamp', 'message', 'postback', 'referral', 'optin'])->toArray();
        });

        return $event->isEmpty() ? false : $event->first();
    }

    /**
     * @return bool
     */
    protected function validateSignature()
    {
        return hash_equals($this->signature,
            'sha1='.hash_hmac('sha1', $this->content, $this->config->get('facebook_app_secret')));
    }

    /**
     * @param Message $matchingMessage
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function types(Message $matchingMessage)
    {
        $parameters = [
            'recipient' => [
                'id' => $matchingMessage->getRecipient(),
            ],
            'access_token' => $this->config->get('facebook_token'),
            'sender_action' => 'typing_on',
        ];

        return $this->http->post('https://graph.facebook.com/v2.6/me/messages', [], $parameters);
    }

    /**
     * @param  Message $message
     * @return Answer
     */
    public function getConversationAnswer(Message $message)
    {
        $payload = $message->getPayload();
        if (isset($payload['message']['quick_reply'])) {
            return Answer::create($message->getText())->setMessage($message)->setInteractiveReply(true)->setValue($payload['message']['quick_reply']['payload']);
        }

        return Answer::create($message->getText())->setMessage($message);
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        $messages = Collection::make($this->event->get('messaging'));
        $messages = $messages->transform(function ($msg) {
            if (isset($msg['message']) && isset($msg['message']['text'])) {
                return new Message($msg['message']['text'], $msg['sender']['id'], $msg['recipient']['id'], $msg);
            }

            return new Message('', '', '');
        })->toArray();

        if (count($messages) === 0) {
            return [new Message('', '', '')];
        }

        return $messages;
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
     * Convert a Question object into a valid Facebook
     * quick reply response object.
     *
     * @param Question $question
     * @return array
     */
    private function convertQuestion(Question $question)
    {
        $questionData = $question->toArray();

        $replies = Collection::make($question->getButtons())->map(function ($button) {
            return [
                'content_type' => 'text',
                'title' => $button['text'],
                'payload' => $button['value'],
                'image_url' => $button['image_url'],
            ];
        });

        return [
            'text' => $questionData['text'],
            'quick_replies' => $replies->toArray(),
        ];
    }

    private function getAttachmentType(Attachment $attachment)
    {
        if ($attachment instanceof Image) {
            return 'image';
        }
    }

    /**
     * @param string|Question|IncomingMessage $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        $parameters = array_merge_recursive([
            'recipient' => [
                'id' => $matchingMessage->getSender(),
            ],
            'message' => [
                'text' => $message,
            ],
        ], $additionalParameters);
        /*
         * If we send a Question with buttons, ignore
         * the text and append the question.
         */
        if ($message instanceof Question) {
            $parameters['message'] = $this->convertQuestion($message);
        } elseif (is_object($message) && in_array(get_class($message), $this->templates)) {
            $parameters['message'] = $message->toArray();
        } elseif ($message instanceof IncomingMessage) {
            $attachment = $message->getAttachment();
            if (in_array(get_class($attachment), $this->supportedAttachments)) {
                $attachmentType = strtolower(basename(str_replace('\\', '/', get_class($attachment))));
                unset($parameters['message']['text']);
                $parameters['message']['attachment'] = [
                    'type' => $attachmentType,
                    'payload' => [
                        'url' => $attachment->getUrl(),
                    ],
                ];
            } else {
                $parameters['message']['text'] = $message->getText();
            }
        }

        $parameters['access_token'] = $this->config->get('facebook_token');

        return $parameters;
    }

    /**
     * @param mixed $payload
     * @return Response
     */
    public function sendPayload($payload)
    {
        return $this->http->post('https://graph.facebook.com/v2.6/me/messages', [], $payload);
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return ! empty($this->config->get('facebook_token'));
    }

    /**
     * Retrieve User information.
     *
     * @param Message $matchingMessage
     * @return User
     */
    public function getUser(Message $matchingMessage)
    {
        $profileData = $this->http->get($this->facebookProfileEndpoint.$matchingMessage->getRecipient().'?fields=first_name,last_name&access_token='.$this->config->get('facebook_token'));

        $profileData = json_decode($profileData->getContent());
        $firstName = isset($profileData->first_name) ? $profileData->first_name : null;
        $lastName = isset($profileData->last_name) ? $profileData->last_name : null;

        return new User($matchingMessage->getSender(), $firstName, $lastName);
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
            'access_token' => $this->config->get('facebook_token'),
        ], $parameters);

        return $this->http->post('https://graph.facebook.com/v2.6/'.$endpoint, [], $parameters);
    }
}
