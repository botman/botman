<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Message;
use Symfony\Component\HttpFoundation\Request;

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
        return ! is_null($this->event->get('from')) && ! is_null($this->event->get('photo'));
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        $message = new Message(BotMan::IMAGE_PATTERN, $this->event->get('from')['id'], $this->event->get('chat')['id'], $this->event);
        $message->setImages($this->getImages());

        return [$message];
    }

    /**
     * Retrieve a image from an incoming message.
     * @param  Message $matchingMessage
     * @return array A download for the image file.
     */
    private function getImages()
    {
        $photos = $this->event->get('photo');
        $largetstPhoto = array_pop($photos);
        $response = $this->http->get('https://api.telegram.org/bot'.$this->config->get('telegram_token').'/getFile', [
            'file_id' => $largetstPhoto['file_id'],
        ]);

        $path = json_decode($response->getContent());

        return ['https://api.telegram.org/file/bot'.$this->config->get('telegram_token').'/'.$path->result->file_path];
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return false;
    }
}
