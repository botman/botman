<?php

namespace Mpociot\BotMan\Drivers\Facebook;

use Illuminate\Support\Collection;
use Mpociot\BotMan\Messages\Attachments\Audio;
use Mpociot\BotMan\Messages\Incoming\IncomingMessage;

class FacebookAudioDriver extends FacebookDriver
{
    const DRIVER_NAME = 'FacebookAudio';

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
                    return $attachment['type'] === 'audio';
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
            $message = new IncomingMessage(Audio::PATTERN, $msg['sender']['id'], $msg['recipient']['id'], $msg);
            $message->setAudio($this->getAudioUrls($msg));

            return $message;
        })->toArray();

        if (count($messages) === 0) {
            return [new IncomingMessage('', '', '')];
        }

        return $messages;
    }

    /**
     * Retrieve audio file urls from an incoming message.
     *
     * @param array $message
     * @return array A download for the audio file.
     */
    public function getAudioUrls(array $message)
    {
        return Collection::make($message['message']['attachments'])->where('type',
            'audio')->pluck('payload')->map(function ($item) {
                return new Audio($item['url'], $item);
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
