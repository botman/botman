<?php

namespace BotMan\BotMan\Drivers\Facebook;

use Illuminate\Support\Collection;
use BotMan\BotMan\Messages\Attachments\Location;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

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
                    return $attachment['type'] === 'location';
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
            $message = new IncomingMessage(Location::PATTERN, $msg['sender']['id'], $msg['recipient']['id'], $msg);
            $message->setLocation($this->getLocation($msg));

            return $message;
        })->toArray();

        if (count($messages) === 0) {
            return [new IncomingMessage('', '', '')];
        }

        return $messages;
    }

    /**
     * Retrieve location from an incoming message.
     *
     * @param array $messages
     * @return \BotMan\BotMan\Messages\Attachments\Location
     */
    public function getLocation(array $messages)
    {
        $data = Collection::make($messages['message']['attachments'])->where('type',
            'location')->pluck('payload')->first();

        return new Location($data['coordinates']['lat'], $data['coordinates']['long'], $data);
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return false;
    }
}
