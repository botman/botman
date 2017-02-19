<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Message;
use Illuminate\Support\Collection;

class FacebookAttachmentDriver extends FacebookDriver
{
    const DRIVER_NAME = 'FacebookAttachment';

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
            $message = new Message(BotMan::ATTACHMENT_PATTERN, $msg['recipient']['id'], $msg['sender']['id'], $msg);
            $message->setAttachments($this->getAttachmentsUrls($msg));

            return $message;
        })->toArray();

        if (count($messages) === 0) {
            return [new Message('', '', '')];
        }

        return $messages;
    }

    /**
     * Retrieve attachment file from an incoming message.
     *
     * @param array $messages
     * @return array A download for the attachment file.
     */
    public function getAttachmentsUrls(array $messages)
    {
        return Collection::make($messages['message']['attachments'])->where('type', 'file')->pluck('payload.url')->toArray();
    }
}
