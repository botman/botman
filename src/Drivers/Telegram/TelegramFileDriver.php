<?php

namespace BotMan\BotMan\Drivers\Telegram;

use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

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
        $message = new IncomingMessage(File::PATTERN, $this->event->get('from')['id'], $this->event->get('chat')['id'],
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

        // In case of file too large, this return only the attachment with the original payload, not the link.
        // This need a proper logging and exception system in the future
        $url = null;
        if (isset($path->result)) {
            $url = 'https://api.telegram.org/file/bot'.$this->config->get('telegram_token').'/'.$path->result->file_path;
        }

        return [new File($url, $file)];
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return false;
    }
}
