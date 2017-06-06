<?php

namespace Mpociot\BotMan\Drivers\Telegram;

use Mpociot\BotMan\Messages\Attachments\Video;
use Mpociot\BotMan\Messages\Incoming\IncomingMessage;

class TelegramVideoDriver extends TelegramDriver
{
    const DRIVER_NAME = 'TelegramVideo';

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return ! is_null($this->event->get('from')) && ! is_null($this->event->get('video'));
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        $message = new IncomingMessage(Video::PATTERN, $this->event->get('from')['id'], $this->event->get('chat')['id'],
            $this->event);
        $message->setVideos($this->getVideos());

        return [$message];
    }

    /**
     * Retrieve a image from an incoming message.
     * @return array A download for the image file.
     */
    private function getVideos()
    {
        $video = $this->event->get('video');
        $response = $this->http->get('https://api.telegram.org/bot'.$this->config->get('telegram_token').'/getFile', [
            'file_id' => $video['file_id'],
        ]);

        $path = json_decode($response->getContent());

        // In case of file too large, this return only the attachment with the original payload, not the link.
        // This need a proper logging and exception system in the future
        $url = null;
        if (isset($path->result)) {
            $url = 'https://api.telegram.org/file/bot'.$this->config->get('telegram_token').'/'.$path->result->file_path;
        }

        return [new Video($url, $video)];
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return false;
    }
}
