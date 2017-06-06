<?php

namespace Mpociot\BotMan\Drivers\Glip;

use Mpociot\BotMan\User;
use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Mpociot\BotMan\Drivers\Driver;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;
use Mpociot\BotMan\Messages\Message as IncomingMessage;
use RingCentral\SDK\SDK;

class GlipDriver extends Driver
{
    /** @var Collection */
    protected $event;

    /** @var config */
    protected $config;

    /** @var GlipClient */
    protected $sdk;
    protected $platform;

    const DRIVER_NAME = 'GlipDriver';

    /** @var Collection|ParameterBag */
    protected $payload;


    /**s
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        $this->payload = new ParameterBag((array) json_decode($request->getContent(), true));
        $this->event = Collection::make($this->payload->get('event'));
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
     * @param Message $matchingMessage
     * @return User
     */
    public function getUser(Message $matchingMessage)
    {
        $parameters = [
            'chat_id' => $matchingMessage->getChannel(),
            'user_id' => $matchingMessage->getUser(),
        ];

        $response = $this->$this->getPlatform()->get('/glip/persons' + $matchingMessage->getUser());
        $responseData = json_decode($response->getContent(), true);
        $userData = Collection::make($responseData['result']['user']);

        return new User($userData->get('id'), $userData->get('firstName'), $userData->get('lastName'), $userData->get('avatar'));
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return (! is_null($this->payload->get('body'))) && ! is_null($this->payload->get('event'));
    }

    /**
     * @param  Message $message
     * @return Answer
     */
    public function getConversationAnswer(Message $message)
    {
        return Answer::create($message->getMessage())->setMessage($message);
    }

    
    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        if ($this->payload->get('body') !== null) {
            $callback = Collection::make($this->payload->get('body'));

            return [new Message($callback->get('text'), $callback->get('creatorId'), $callback->get('groupId'), $this->payload->get('body'))];
        }

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
     * @return void
     */
    public function types(Message $matchingMessage)
    {
        $parameters = [
            'chat_id' => $matchingMessage->getChannel(),
            'action' => 'typing',
        ];
        $this->http->post('/glip/posts', $parameters);
    }

    /**
     * Convert a Question object into a valid
     * quick reply response object.
     *
     * @param Question $question
     * @return array
     */
    private function convertQuestion(Question $question)
    {
        $replies = Collection::make($question->getButtons())->map(function ($button) {
            return [
                [
                    'text' => (string) $button['text'],
                    'callback_data' => (string) $button['value'],
                ],
            ];
        });

        return $replies->toArray();
    }

    /**
     * @return \RingCentral\SDK\Platform\Platform
     */
    public function getPlatform()
    {

        $rcsdk = new SDK($this->config->get('GLIP_APPKEY'), $this->config->get('GLIP_APPSECRET'), $this->config->get('GLIP_SERVER'), 'Sample-Bot', '1.0.0');
        $platform = $rcsdk->platform();

        $cacheDir = __DIR__ . DIRECTORY_SEPARATOR . '_cache';
        $file = $cacheDir . DIRECTORY_SEPARATOR . 'platform.json';

        if (!file_exists($cacheDir)) {
            mkdir($cacheDir);
        }

        $cachedAuth = array();

        if (file_exists($file)) {
            $cachedAuth = json_decode(file_get_contents($file), true);
            $platform->auth()->setData($cachedAuth);

        }

        try {

            if($platform->loggedIn()) {
                return $platform;
            }

            else {
                $refresh = $platform->login($this->config->get('GLIP_USERNAME'), $this->config->get('GLIP_EXTENSION'), $this->config->get('GLIP_PASSWORD'));
                file_put_contents($file, json_encode($refresh->jsonArray(), JSON_PRETTY_PRINT));
                return $platform;
            }
        }

        catch (Exception $e) {
            $platform->login($this->config->get('GLIP_USERNAME'), $this->config->get('GLIP_EXTENSION'), $this->config->get('GLIP_PASSWORD'));
            file_put_contents($file, json_encode($platform->auth()->data(), JSON_PRETTY_PRINT));
            return $platform;
        }
    }

    /**
     * Removes the inline keyboard from an interactive
     * message.
     * @param  int $chatId
     * @param  int $messageId
     * @return Response
     */
    private function removeInlineKeyboard($chatId, $messageId)
    {
        $parameters = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'inline_keyboard' => [],
        ];

        $this->getPlatform()->post('/glip/posts', $parameters);
    }

    /**
     * @param string|Question|IncomingMessage $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return Response
     */
    public function reply($message, $matchingMessage, $additionalParameters = [])
    {
        $endpoint = 'sendMessage';
        $parameters = array_merge([
            'groupId' => $matchingMessage->getChannel(),
        ], $additionalParameters);
        /*
         * If we send a Question with buttons, ignore
         * the text and append the question.
         */
        if ($message instanceof Question) {
            $parameters['text'] = $message->getText();
            $parameters['reply_markup'] = json_encode([
                'inline_keyboard' => $this->convertQuestion($message),
            ], true);
        } elseif ($message instanceof IncomingMessage) {
            if (! is_null($message->getImage())) {
                if (strtolower(pathinfo($message->getImage(), PATHINFO_EXTENSION)) === 'gif') {
                    $endpoint = 'sendDocument';
                    $parameters['document'] = $message->getImage();
                } else {
                    $endpoint = 'sendPhoto';
                    $parameters['photo'] = $message->getImage();
                }
                $parameters['caption'] = $message->getMessage();
            } elseif (! is_null($message->getVideo())) {
                $endpoint = 'sendVideo';
                $parameters['video'] = $message->getVideo();
                $parameters['caption'] = $message->getMessage();
            } else {
                $parameters['text'] = $message->getMessage();
            }
        } else {
            $parameters['text'] = $message;
        }

        $this->getPlatform()->post('/glip/posts', $parameters);
    }

    /**
     * @param string|Question|IncomingMessage $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return Response
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        $recipient = $matchingMessage->getRecipient() === '' ? $matchingMessage->getSender() : $matchingMessage->getRecipient();
        $parameters = array_merge_recursive([
            'chat_id' => $recipient,
        ], $additionalParameters);
        /*
         * If we send a Question with buttons, ignore
         * the text and append the question.
         */
        if ($message instanceof Question) {
            $parameters['text'] = $message->getText();
            $parameters['reply_markup'] = json_encode([
                'inline_keyboard' => $this->convertQuestion($message),
            ], true);
        } elseif ($message instanceof IncomingMessage) {
            if (! is_null($message->getAttachment())) {
                $attachment = $message->getAttachment();
                $parameters['caption'] = $message->getText();
                if ($attachment instanceof Image) {
                    if (strtolower(pathinfo($attachment->getUrl(), PATHINFO_EXTENSION)) === 'gif') {
                        $this->endpoint = 'sendDocument';
                        $parameters['document'] = $attachment->getUrl();
                    } else {
                        $this->endpoint = 'sendPhoto';
                        $parameters['photo'] = $attachment->getUrl();
                    }
                } elseif ($attachment instanceof Video) {
                    $this->endpoint = 'sendVideo';
                    $parameters['video'] = $attachment->getUrl();
                } elseif ($attachment instanceof Audio) {
                    $this->endpoint = 'sendAudio';
                    $parameters['audio'] = $attachment->getUrl();
                } elseif ($attachment instanceof File) {
                    $this->endpoint = 'sendDocument';
                    $parameters['document'] = $attachment->getUrl();
                } elseif ($attachment instanceof Location) {
                    $this->endpoint = 'sendLocation';
                    $parameters['latitude'] = $attachment->getLatitude();
                    $parameters['longitude'] = $attachment->getLongitude();
                }
            } else {
                $parameters['text'] = $message->getText();
            }
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
        return $this->getPlatform()->post($this->endpoint, $payload);
    }
    
    /**
     * @return bool
     */
    public function isConfigured()
    {
        return ! is_null($this->getPlatform()->loggedIn());
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
            'chat_id' => $matchingMessage->getRecipient(),
        ], $parameters);

        return $this->getPlatform()->post($endpoint, $parameters);
    }
}
