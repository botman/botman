<?php

namespace Mpociot\BotMan\Drivers\BotFramework;

use Mpociot\BotMan\Users\User;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Drivers\HttpDriver;
use Mpociot\BotMan\Messages\Incoming\Answer;
use Symfony\Component\HttpFoundation\Request;
use Mpociot\BotMan\Messages\Attachments\Image;
use Mpociot\BotMan\Messages\Attachments\Video;
use Mpociot\BotMan\Messages\Outgoing\Question;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;
use Mpociot\BotMan\Messages\Incoming\IncomingMessage;
use Mpociot\BotMan\Messages\Outgoing\OutgoingMessage;

class BotFrameworkDriver extends HttpDriver
{
    const DRIVER_NAME = 'BotFramework';

    protected $apiURL;

    /**
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        $this->payload = new ParameterBag((array) json_decode($request->getContent(), true));
        $this->event = Collection::make($this->payload->all());
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
     * @param  \Mpociot\BotMan\Messages\Incoming\IncomingMessage $message
     * @return \Mpociot\BotMan\Messages\Incoming\Answer
     */
    public function getConversationAnswer(IncomingMessage $message)
    {
        if (false !== strpos($message->getText(), '<botman value="')) {
            preg_match('/<botman value="(.*)"><\/botman>/', $message->getText(), $matches);

            return Answer::create($message->getText())
                ->setInteractiveReply(true)
                ->setMessage($message)
                ->setValue($matches[1]);
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
        // replace bot's name for group chats and special characters that might be sent from Web Skype
        $pattern = '/<at id=(.*?)at>[^(\x20-\x7F)\x0A]*\s*/';
        $message = preg_replace($pattern, '', $this->event->get('text'));

        return [
            new IncomingMessage($message, $this->event->get('from')['id'], $this->event->get('conversation')['id'],
                $this->payload),
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
     * @param IncomingMessage $matchingMessage
     * @return \Mpociot\BotMan\Users\User
     */
    public function getUser(IncomingMessage $matchingMessage)
    {
        return new User($matchingMessage->getRecipient(), null, null,
            Collection::make($matchingMessage->getPayload())->get('from')['name']);
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
            return array_merge([
                'type' => 'imBack',
                'title' => $button['text'],
                'value' => $button['text'].'<botman value="'.$button['value'].'" />',
            ], $button['additional']);
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
     * @param IncomingMessage $matchingMessage
     * @param array $additionalParameters
     * @return Response
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        $parameters = array_merge_recursive([
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
        } elseif ($message instanceof OutgoingMessage) {
            $parameters['text'] = $message->getText();
            $attachment = $message->getAttachment();
            if (! is_null($attachment)) {
                if ($attachment instanceof Image) {
                    $parameters['attachments'] = [
                        [
                            'contentType' => 'image/png',
                            'contentUrl' => $attachment->getUrl(),
                        ],
                    ];
                } elseif ($attachment instanceof Video) {
                    $parameters['attachments'] = [
                        [
                            'contentType' => 'video/mp4',
                            'contentUrl' => $attachment->getUrl(),
                        ],
                    ];
                }
            }
        } else {
            $parameters['text'] = $message;
        }

        /**
         * Originated messages use the getSender method, otherwise getRecipient.
         */
        $recipient = $matchingMessage->getSender() === '' ? $matchingMessage->getRecipient() : $matchingMessage->getSender();
        $payload = is_null($matchingMessage->getPayload()) ? [] : $matchingMessage->getPayload()->all();
        $this->apiURL = Collection::make($payload)->get('serviceUrl',
                Collection::make($additionalParameters)->get('serviceUrl')).'/v3/conversations/'.urlencode($recipient).'/activities';

        if (strstr($this->apiURL, 'webchat.botframework')) {
            $parameters['from'] = [
                'id' => $this->config->get('microsoft_bot_handle'),
            ];
        }

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
            'Authorization:Bearer '.$this->getAccessToken(),
        ];

        return $this->http->post($this->apiURL, [], $payload, $headers, true);
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return ! empty($this->config->get('microsoft_app_id')) && ! empty($this->config->get('microsoft_app_key'));
    }

    /**
     * Low-level method to perform driver specific API requests.
     *
     * @param string $endpoint
     * @param array $parameters
     * @param IncomingMessage $matchingMessage
     * @return Response
     */
    public function sendRequest($endpoint, array $parameters, IncomingMessage $matchingMessage)
    {
        $headers = [
            'Content-Type:application/json',
            'Authorization:Bearer '.$this->getAccessToken(),
        ];

        $apiURL = Collection::make($matchingMessage->getPayload())->get('serviceUrl',
            Collection::make($parameters)->get('serviceUrl'));

        return $this->http->post($apiURL.'/v3/'.$endpoint, [], $parameters, $headers, true);
    }
}
