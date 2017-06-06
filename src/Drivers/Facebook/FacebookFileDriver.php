<?php

namespace Mpociot\BotMan\Drivers\Facebook;

use Mpociot\BotMan\Messages\Incoming\IncomingMessage;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Messages\Attachments\File;

class FacebookFileDriver extends FacebookDriver
{
    const DRIVER_NAME = 'FacebookFile';

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        $validSignature = ! $this->config->has('facebook_app_secret') || $this->validateSignature();
        $messages = Collection::make($this->event->get('messaging'))->filter(function ($msg) {
            if (isset($msg['message']) && isset($msg['message']['attachments']) && isset($msg['message']['attachments'])) {
                return Collection::make($msg['message']['attachments'])->filter(function ($attachment) {
                    return $attachment['type'] === 'file';
                })->isEmpty() === false;
            }

            return false;
        });

        return ! $messages->isEmpty() && $validSignature;
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        $messages = Collection::make($this->event->get('messaging'))->filter(function ($msg) {
            return isset($msg['message']) && isset($msg['message']['attachments']) && isset($msg['message']['attachments']);
        })->transform(function ($msg) {
            $message = new IncomingMessage(File::PATTERN, $msg['sender']['id'], $msg['recipient']['id'], $msg);
            $message->setFiles($this->getFiles($msg));

            return $message;
        })->toArray();

        if (count($messages) === 0) {
            return [new IncomingMessage('', '', '')];
        }

        return $messages;
    }

    /**
     * Retrieve file urls from an incoming message.
     *
     * @param array $message
     * @return array A download for the file.
     */
    public function getFiles(array $message)
    {
        return Collection::make($message['message']['attachments'])->where('type',
            'file')->pluck('payload')->map(function ($item) {
                return new File($item['url'], $item);
            })->toArray();
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return false;
    }
}
