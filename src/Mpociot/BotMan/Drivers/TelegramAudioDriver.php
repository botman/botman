<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Message;
use Symfony\Component\HttpFoundation\Request;

class TelegramAudioDriver extends TelegramDriver
{
    const DRIVER_NAME = 'TelegramAudio';

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
        return ! is_null($this->event->get('from')) && ! is_null($this->event->get('document')) && substr($this->event->get('document')['mime_type'], 0, 5) === 'audio';
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        $message = new Message(BotMan::AUDIO_PATTERN, $this->event->get('from')['id'], $this->event->get('chat')['id'], $this->event);
        $message->setAudio($this->getAudio());

        return [$message];
    }

    /**
     * Retrieve a image from an incoming message.
     * @return array A download for the image file.
     */
    private function getAudio()
    {
        $audio = $this->event->get('document');
        $response = $this->http->get('https://api.telegram.org/bot'.$this->config->get('telegram_token').'/getFile', [
            'file_id' => $audio['file_id'],
        ]);

        $path = json_decode($response->getContent());

        return ['https://api.telegram.org/file/bot'.$this->config->get('telegram_token').'/'.$path->result->file_path];
    }
}
