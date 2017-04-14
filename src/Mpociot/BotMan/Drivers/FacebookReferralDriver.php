<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\Message;
use Illuminate\Support\Collection;

class FacebookReferralDriver extends FacebookDriver
{
    const DRIVER_NAME = 'FacebookReferral';

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        $validSignature = !$this->config->has('facebook_app_secret') || $this->validateSignature();
        $messages = Collection::make($this->event->get('messaging'))->filter(
            function ($msg) {
                return isset($msg['referral']) && isset($msg['referral']['ref']);
            }
        );

        return !$messages->isEmpty() && $validSignature;
    }

    /**
     * Retrieve the referral message.
     *
     * @return array
     */
    public function getMessages()
    {
        $messages = Collection::make($this->event->get('messaging'));
        $messages = $messages->transform(
            function ($msg) {
                return new Message($msg['referral']['ref'], $msg['recipient']['id'], $msg['sender']['id'], $msg);
            }
        )->toArray();

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
