<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Message;
use Illuminate\Support\Collection;

class FacebookVideoDriver extends FacebookDriver
{
    const DRIVER_NAME = 'FacebookVideo';

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
                    return isset($attachment['type']) && $attachment['type'] === 'video';
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
            $message = new Message(BotMan::VIDEO_PATTERN, $msg['recipient']['id'], $msg['sender']['id'], $msg);
            $message->setVideos($this->getVideoUrls($msg));

            return $message;
        })->toArray();

        if (count($messages) === 0) {
            return [new Message('', '', '')];
        }

        return $messages;
    }

    /**
     * Retrieve video urls from an incoming message.
     *
     * @param array $message
     * @return array A download for the image file.
     */
    public function getVideoUrls(array $message)
    {
        return Collection::make($message['message']['attachments'])->where('type', 'video')->pluck('payload.url')->toArray();
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return false;
    }
}
