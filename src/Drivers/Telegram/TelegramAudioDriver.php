<?php

namespace Mpociot\BotMan\Drivers\Telegram;

use Mpociot\BotMan\Messages\Attachments\Audio;
use Mpociot\BotMan\Messages\Incoming\IncomingMessage;

class TelegramAudioDriver extends TelegramDriver
{
    const DRIVER_NAME = 'TelegramAudio';

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return ! is_null($this->event->get('from')) && (! is_null($this->event->get('audio')) || ! is_null($this->event->get('voice')));
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        $message = new IncomingMessage(Audio::PATTERN, $this->event->get('from')['id'], $this->event->get('chat')['id'],
            $this->event);
        $message->setAudio($this->getAudio());

        return [$message];
    }

    /**
     * Retrieve a image from an incoming message.
     * @return array A download for the audio file.
     */
    private function getAudio()
    {
        $audio = $this->event->get('audio');
        if ($this->event->has('voice')) {
            $audio = $this->event->get('voice');
        }
        $response = $this->http->get('https://api.telegram.org/bot'.$this->config->get('telegram_token').'/getFile', [
            'file_id' => $audio['file_id'],
        ]);

        $path = json_decode($response->getContent());

        // In case of file too large, this return only the attachment with the original payload, not the link.
        // This need a proper logging and exception system in the future
        $url = null;
        if (isset($path->result)) {
            $url = 'https://api.telegram.org/file/bot'.$this->config->get('telegram_token').'/'.$path->result->file_path;
        }

        return [new Audio($url, $audio)];
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return false;
    }
}
