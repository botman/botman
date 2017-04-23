<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\Message;
use Illuminate\Support\Collection;

class FacebookPostbackDriver extends FacebookDriver
{
    const DRIVER_NAME = 'FacebookPostback';

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        $validSignature = ! $this->config->has('facebook_app_secret') || $this->validateSignature();
        $messages = Collection::make($this->event->get('messaging'))->filter(function ($msg) {
            return isset($msg['postback']) && isset($msg['postback']['payload']);
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
        $messages = Collection::make($this->event->get('messaging'));
        $messages = $messages->transform(function ($msg) {
            return new Message($msg['postback']['payload'], $msg['recipient']['id'], $msg['sender']['id'], $msg);
        })->toArray();

        if (count($messages) === 0) {
            return [new Message('', '', '')];
        }

        return $messages;
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return false;
    }
}
