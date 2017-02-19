<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Message;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Request;

class FacebookImageDriver extends FacebookDriver
{
    const DRIVER_NAME = 'FacebookImage';

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
        $validSignature = ! $this->config->has('facebook_app_secret') || $this->validateSignature();
        $messages = Collection::make($this->event->get('messaging'))->filter(function ($msg) {
            if (isset($msg['message']) && isset($msg['message']['attachments']) && isset($msg['message']['attachments'])) {
                return Collection::make($msg['message']['attachments'])->filter(function ($attachment) {
                    return $attachment['type'] === 'image';
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
            return new Message(BotMan::IMAGE_PATTERN, $msg['recipient']['id'], $msg['sender']['id'], $msg);
        })->toArray();

        if (count($messages) === 0) {
            return [new Message('', '', '')];
        }

        return $messages;
    }

    /**
     * Retrieve a image from an incoming message.
     * @param  Message $matchingMessage
     * @return array A download for the image file.
     */
    public function getImages(Message $matchingMessage)
    {
        $messageData = $matchingMessage->getPayload();

        return Collection::make($messageData['message']['attachments'])->where('type', 'image')->pluck('payload.url')->toArray();
    }
}
