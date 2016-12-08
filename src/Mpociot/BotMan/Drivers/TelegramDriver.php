<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;

class TelegramDriver extends Driver
{
    /** @var Collection|ParameterBag */
    protected $payload;

    /** @var Collection */
    protected $event;

    const DRIVER_NAME = 'Telegram';

    /**
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        $this->payload = new ParameterBag((array) json_decode($request->getContent(), true));
        $this->event = Collection::make($this->payload->get('message'));
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
        return (! is_null($this->event->get('from')) || ! is_null($this->payload->get('callback_query'))) && ! is_null($this->payload->get('update_id'));
    }

    /**
     * @param  Message $message
     * @return Answer
     */
    public function getConversationAnswer(Message $message)
    {
        if ($this->payload->get('callback_query') !== null) {
            $callback = Collection::make($this->payload->get('callback_query'));

            // Update original message
            $this->removeInlineKeyboard($callback->get('message')['chat']['id'], $callback->get('message')['message_id']);

            return Answer::create($callback->get('data'))
                ->setInteractiveReply(true)
                ->setValue($callback->get('data'));
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
        if ($this->payload->get('callback_query') !== null) {
            $callback = Collection::make($this->payload->get('callback_query'));

            return [new Message($callback->get('data'), $callback->get('message')['chat']['id'], $callback->get('from')['id'], $callback->get('messsage'))];
        } else {
            return [new Message($this->event->get('text'), $this->event->get('chat')['id'], $this->event->get('from')['id'], $this->event)];
        }
    }

    /**
     * @return bool
     */
    public function isBot()
    {
        return $this->event->has('entities');
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
                'text' => (string) $button['text'],
                'callback_data' => (string) $button['value'],
            ];
        });

        return $replies->toArray();
    }

    /**
     * Removes the inlince keyboard from an interactive
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

        return $this->http->post('https://api.telegram.org/bot'.$this->config->get('telegram_token').'/editMessageReplyMarkup', [], $parameters);
    }

    /**
     * @param string|Question $message
     * @param Message $matchingMessage
     * @param array $additionalParameters
     * @return Response
     */
    public function reply($message, $matchingMessage, $additionalParameters = [])
    {
        $parameters = array_merge([
            'chat_id' => $matchingMessage->getUser(),
        ], $additionalParameters);
        /*
         * If we send a Question with buttons, ignore
         * the text and append the question.
         */
        if ($message instanceof Question) {
            $parameters['text'] = $message->getText();
            $parameters['reply_markup'] = json_encode([
                'inline_keyboard' => [$this->convertQuestion($message)],
            ], true);
        } else {
            $parameters['text'] = $message;
        }

        return $this->http->post('https://api.telegram.org/bot'.$this->config->get('telegram_token').'/sendMessage', [], $parameters);
    }
}
