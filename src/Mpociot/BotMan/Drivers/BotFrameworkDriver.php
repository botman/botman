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

class BotFrameworkDriver extends Driver
{
    /** @var Collection|ParameterBag */
    protected $payload;

    /** @var Collection */
    protected $event;

    const DRIVER_NAME = 'BotFramework';

    /**
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        $this->payload = new ParameterBag((array) json_decode($request->getContent(), true));
        $this->event = Collection::make($this->payload->all());
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
        return ! is_null($this->event->get('recipient')) && ! is_null($this->event->get('serviceUrl'));
    }

    /**
     * @param  Message $message
     * @return Answer
     */
    public function getConversationAnswer(Message $message)
    {
        if (strstr($message->getMessage(), '<botman value="') !== false) {
            preg_match('/<botman value="(.*)"\/>/', $message->getMessage(), $matches);

            return Answer::create($message->getMessage())
                ->setInteractiveReply(true)
                ->setMessage($message)
                ->setValue($matches[1]);
        }

        return Answer::create($message->getMessage())->setMessage($message);
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        // replace bot's name for group chats and special characters that might be sent from Web Skype
        $pattern = '/<at id=(.*?)at>[^(\x20-\x7F)\x0A]*\s*/';
        $message = preg_replace($pattern, '', $this->event->get('text'));

        return [new Message($message, $this->event->get('from')['id'], $this->event->get('conversation')['id'], $this->payload)];
    }

    /**
     * @return bool
     */
    public function isBot()
    {
        return false;
    }

    /**
     * @param Message $matchingMessage
     * @return User
     */
    public function getUser(Message $matchingMessage)
    {
        return new User($matchingMessage->getChannel(), null, null, Collection::make($matchingMessage->getPayload())->get('from')['name']);
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
        $replies = Collection::make($question->getButtons())->map(function ($button) {
            return [
                'type' => 'imBack',
                'title' => $button['text'],
                'value' => $button['text'].'<botman value="'.$button['value'].'" />',
            ];
        });

        return $replies->toArray();
    }

    public function getAccessToken()
    {
        $response = $this->http->post('https://login.microsoftonline.com/botframework.com/oauth2/v2.0/token', [], [
            'client_id' => $this->config->get('microsoft_app_id'),
            'client_secret' => $this->config->get('microsoft_app_key'),
            'grant_type' => 'client_credentials',
            'scope' => 'https://api.botframework.com/.default',
        ]);
        $responseData = json_decode($response->getContent());

        return $responseData->access_token;
    }

    /**
     * @param string|Question|IncomingMessage $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return Response
     */
    public function reply($message, $matchingMessage, $additionalParameters = [])
    {
        $parameters = array_merge([
            'type' => 'message',
        ], $additionalParameters);
        /*
         * If we send a Question with buttons, ignore
         * the text and append the question.
         */
        if ($message instanceof Question) {
            $parameters['attachments'] = [
                [
                    'contentType' => 'application/vnd.microsoft.card.hero',
                    'content' => [
                        'text' => $message->getText(),
                        'buttons' => $this->convertQuestion($message),
                    ],
                ],
            ];
        } elseif ($message instanceof IncomingMessage) {
            $parameters['text'] = $message->getMessage();

            if (! is_null($message->getImage())) {
                $parameters['attachments'] = [
                    [
                        'contentType' => 'image/png',
                        'contentUrl' => $message->getImage(),
                    ],
                ];
            } elseif (! is_null($message->getVideo())) {
                $parameters['attachments'] = [
                    [
                        'contentType' => 'video/mp4',
                        'contentUrl' => $message->getVideo(),
                    ],
                ];
            }
        } else {
            $parameters['text'] = $message;
        }

        $headers = [
            'Content-Type:application/json',
            'Authorization:Bearer '.$this->getAccessToken(),
        ];

        $apiURL = Collection::make($matchingMessage->getPayload())->get('serviceUrl', Collection::make($additionalParameters)->get('serviceUrl'));

        if (strstr($apiURL, 'webchat.botframework')) {
            $parameters['from'] = [
                'id' => $this->config->get('microsoft_bot_handle'),
            ];
        }

        return $this->http->post($apiURL.'/v3/conversations/'.urlencode($matchingMessage->getChannel()).'/activities', [], $parameters, $headers, true);
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return ! is_null($this->config->get('microsoft_app_id')) && ! is_null($this->config->get('microsoft_app_key'));
    }
}
