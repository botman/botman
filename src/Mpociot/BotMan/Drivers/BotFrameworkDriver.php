<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;

class BotFrameworkDriver extends Driver
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
     * @param  Message $message
     * @return Answer
     */
    public function getConversationAnswer(Message $message)
    {
        if (strstr($message->getMessage(), '<botman value="') !== false) {
            preg_match('/<botman value="(.*)"\/>/', $message->getMessage(), $matches);

            return Answer::create($message->getMessage())
                ->setInteractiveReply(true)
                ->setValue($matches[1]);
        }

        return Answer::create($message->getMessage());
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        return [new Message($this->event->get('text'), $this->event->get('conversation')['id'], $this->event->get('from')['id'], $this->payload)];
    }

    /**
     * @return bool
     */
    public function isBot()
    {
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
        $response = $this->http->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [], [
            'client_id' => $this->config->get('microsoft_app_id'),
            'client_secret' => $this->config->get('microsoft_app_key'),
            'grant_type' => 'client_credentials',
            'scope' => 'https://graph.microsoft.com/.default',
        ]);
        $responseData = json_decode($response->getContent());

        return $responseData->access_token;
    }

    /**
     * @param string|Question $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return Response
     */
    public function reply($message, $matchingMessage, $additionalParameters = [])
    {
        $token = $this->getAccessToken();

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
        } else {
            $parameters['text'] = $message;
        }

        $headers = [
            'Content-Type:application/json',
            'Authorization:Bearer '.$token,
        ];

        return $this->http->post($matchingMessage->getPayload()->get('serviceUrl').'/v3/conversations/'.urlencode($matchingMessage->getChannel()).'/activities', [], $parameters, $headers, true);
    }
}
