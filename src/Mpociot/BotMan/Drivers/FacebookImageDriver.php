<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\Attachments\Image;
use Mpociot\BotMan\Message;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Messages\Matcher;

class FacebookImageDriver extends FacebookDriver
{
    const DRIVER_NAME = 'FacebookImage';

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
            $message = new Message(Matcher::IMAGE_PATTERN, $msg['recipient']['id'], $msg['sender']['id'], $msg);
            $message->setImages($this->getImagesUrls($msg));

            return $message;
        })->toArray();

        if (count($messages) === 0) {
            return [new Message('', '', '')];
        }

        return $messages;
    }

    /**
     * Retrieve image urls from an incoming message.
     *
     * @param array $message
     * @return array A download for the image file.
     */
    public function getImagesUrls(array $message)
    {
        return Collection::make($message['message']['attachments'])->where('type', 'image')->pluck('payload')->map(function($item) {
	        return new Image($item['url'], $item);
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
