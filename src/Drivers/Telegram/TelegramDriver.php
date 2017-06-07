<?php

namespace Mpociot\BotMan\Drivers\Telegram;

use Mpociot\BotMan\Users\User;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Drivers\HttpDriver;
use Mpociot\BotMan\Messages\Incoming\Answer;
use Mpociot\BotMan\Messages\Attachments\File;
use Symfony\Component\HttpFoundation\Request;
use Mpociot\BotMan\Messages\Attachments\Audio;
use Mpociot\BotMan\Messages\Attachments\Image;
use Mpociot\BotMan\Messages\Attachments\Video;
use Mpociot\BotMan\Messages\Outgoing\Question;
use Symfony\Component\HttpFoundation\Response;
use Mpociot\BotMan\Messages\Attachments\Location;
use Symfony\Component\HttpFoundation\ParameterBag;
use Mpociot\BotMan\Messages\Incoming\IncomingMessage;
use Mpociot\BotMan\Messages\Outgoing\OutgoingMessage;

class TelegramDriver extends HttpDriver
{
    const DRIVER_NAME = 'Telegram';

    protected $endpoint = 'sendMessage';

    /**
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        $this->payload = new ParameterBag((array) json_decode($request->getContent(), true));
        $this->event = Collection::make($this->payload->get('message'));
    }

    /**
     * @param IncomingMessage $matchingMessage
     * @return \Mpociot\BotMan\Users\User
     */
    public function getUser(IncomingMessage $matchingMessage)
    {
        $parameters = [
            'chat_id' => $matchingMessage->getRecipient(),
            'user_id' => $matchingMessage->getSender(),
        ];

        $response = $this->http->post('https://api.telegram.org/bot'.$this->config->get('telegram_token').'/getChatMember',
            [], $parameters);
        $responseData = json_decode($response->getContent(), true);
        $userData = Collection::make($responseData['result']['user']);

        return new User($userData->get('id'), $userData->get('first_name'), $userData->get('last_name'),
            $userData->get('username'));
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        $noAttachments = $this->event->keys()->filter(function($key) {
            return in_array($key, ['audio', 'voice', 'video', 'photo', 'location', 'document']);
        })->isEmpty();
        
        return $noAttachments && (! is_null($this->event->get('from')) || ! is_null($this->payload->get('callback_query'))) && ! is_null($this->payload->get('update_id'));
    }

    /**
     * @param  \Mpociot\BotMan\Messages\Incoming\IncomingMessage $message
     * @return Answer
     */
    public function getConversationAnswer(IncomingMessage $message)
    {
        if ($this->payload->get('callback_query') !== null) {
            $callback = Collection::make($this->payload->get('callback_query'));

            // Update original message
            $this->removeInlineKeyboard($callback->get('message')['chat']['id'],
                $callback->get('message')['message_id']);

            return Answer::create($callback->get('data'))
                ->setInteractiveReply(true)
                ->setMessage($message)
                ->setValue($callback->get('data'));
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
        if ($this->payload->get('callback_query') !== null) {
            $callback = Collection::make($this->payload->get('callback_query'));

            return [
                new IncomingMessage($callback->get('data'), $callback->get('from')['id'],
                    $callback->get('message')['chat']['id'], $callback->get('message')),
            ];
        } else {
            return [
                new IncomingMessage($this->event->get('text'), $this->event->get('from')['id'], $this->event->get('chat')['id'],
                    $this->event),
            ];
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
     * @param IncomingMessage $matchingMessage
     * @return void
     */
    public function types(IncomingMessage $matchingMessage)
    {
        $parameters = [
            'chat_id' => $matchingMessage->getRecipient(),
            'action' => 'typing',
        ];
        $this->http->post('https://api.telegram.org/bot'.$this->config->get('telegram_token').'/sendChatAction', [],
            $parameters);
    }

    /**
     * Convert a Question object into a valid
     * quick reply response object.
     *
     * @param \Mpociot\BotMan\Messages\Outgoing\Question $question
     * @return array
     */
    private function convertQuestion(Question $question)
    {
        $replies = Collection::make($question->getButtons())->map(function ($button) {
            return [
                array_merge([
                    'text' => (string) $button['text'],
                    'callback_data' => (string) $button['value'],
                ], $button['additional']),
            ];
        });

        return $replies->toArray();
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

        return $this->http->post('https://api.telegram.org/bot'.$this->config->get('telegram_token').'/editMessageReplyMarkup',
            [], $parameters);
    }

    /**
     * @param string|Question|IncomingMessage $message
     * @param \Mpociot\BotMan\Messages\Incoming\IncomingMessage $matchingMessage
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
        } elseif ($message instanceof OutgoingMessage) {
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
        return $this->http->post('https://api.telegram.org/bot'.$this->config->get('telegram_token').'/'.$this->endpoint,
            [], $payload);
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return ! empty($this->config->get('telegram_token'));
    }

    /**
     * Low-level method to perform driver specific API requests.
     *
     * @param string $endpoint
     * @param array $parameters
     * @param \Mpociot\BotMan\Messages\Incoming\IncomingMessage $matchingMessage
     * @return Response
     */
    public function sendRequest($endpoint, array $parameters, IncomingMessage $matchingMessage)
    {
        $parameters = array_replace_recursive([
            'chat_id' => $matchingMessage->getRecipient(),
        ], $parameters);

        return $this->http->post('https://api.telegram.org/bot'.$this->config->get('telegram_token').'/'.$endpoint, [],
            $parameters);
    }
}
