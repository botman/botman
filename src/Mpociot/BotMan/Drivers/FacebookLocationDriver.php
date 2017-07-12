<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Message;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Attachments\Location;

class FacebookLocationDriver extends FacebookDriver
{
    const DRIVER_NAME = 'FacebookLocation';

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
                    return isset($attachment['type']) && $attachment['type'] === 'location';
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
            $message = new Message(BotMan::LOCATION_PATTERN, $msg['recipient']['id'], $msg['sender']['id'], $msg);
            $message->setLocation($this->getLocation($msg));

            return $message;
        })->toArray();

        if (count($messages) === 0) {
            return [new Message('', '', '')];
        }

        return $messages;
    }

    /**
     * Retrieve location from an incoming message.
     *
     * @param array $messages
     * @return array A download for the attachment location.
     */
    public function getLocation(array $messages)
    {
        $data = Collection::make($messages['message']['attachments'])->where('type', 'location')->pluck('payload')->first();

        return new Location($data['coordinates']['lat'], $data['coordinates']['long']);
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return false;
    }
}
