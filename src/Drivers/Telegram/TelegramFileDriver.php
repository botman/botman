<?php

namespace Mpociot\BotMan\Drivers\Telegram;

use Mpociot\BotMan\Message;
use Mpociot\BotMan\Attachments\File;

class TelegramFileDriver extends TelegramDriver
{
    const DRIVER_NAME = 'TelegramFile';

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return ! is_null($this->event->get('from')) && (! is_null($this->event->get('document')));
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        $message = new Message(File::PATTERN, $this->event->get('from')['id'], $this->event->get('chat')['id'],
            $this->event);
        $message->setFiles($this->getFiles());

        return [$message];
    }

    /**
     * Retrieve a file from an incoming message.
     * @return array A download for the files.
     */
    private function getFiles()
    {
        $file = $this->event->get('document');

        $response = $this->http->get('https://api.telegram.org/bot'.$this->config->get('telegram_token').'/getFile', [
            'file_id' => $file['file_id'],
        ]);

        $path = json_decode($response->getContent());

        return [
            new File('https://api.telegram.org/file/bot'.$this->config->get('telegram_token').'/'.$path->result->file_path,
                $file),
        ];
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return false;
    }
}
