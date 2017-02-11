<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\User;
use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;
use Mpociot\BotMan\Messages\Message as IncomingMessage;

class TelegramPhotoDriver extends TelegramDriver
{
    const DRIVER_NAME = 'TelegramPhoto';

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return (! is_null($this->event->get('from')) && ! is_null($this->event->get('photo')));
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        return [new Message(BotMan::IMAGE_PATTERN, $this->event->get('from')['id'], $this->event->get('chat')['id'], $this->event)];
    }

    /**
     * Retrieve a image from an incoming message
     * @param  Message $matchingMessage
     * @return string A download for the image file.
     */
    public function getImage(Message $matchingMessage)
    {
        $response = $this->http->get('https://api.telegram.org/bot'.$this->config->get('telegram_token').'/getFile', [
            'file_id' => $matchingMessage->getPayload()->get('photo')[3]['file_id']
        ]);

        $path = json_decode($response->getContent());

        return 'https://api.telegram.org/file/bot'.$this->config->get('telegram_token').'/'.$path->result->file_path;
    }
}