<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\Message;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Attachments\File;

class BotFrameworkAttachmentDriver extends BotFrameworkDriver
{
    const DRIVER_NAME = 'BotFrameworkAttachment';

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        $hasImages = ! Collection::make($this->event->get('attachments'))->isEmpty();

        return parent::matchesRequest() && $hasImages;
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        $message = new Message(File::PATTERN, $this->event->get('from')['id'], $this->event->get('conversation')['id'],
            $this->payload);
        $message->setAttachments($this->getAttachmentUrls());

        return [$message];
    }

    /**
     * Retrieve attachment urls from an incoming message.
     *
     * @return array A download for the attachment.
     */
    public function getAttachmentUrls()
    {
        return Collection::make($this->event->get('attachments'))->map(function ($item) {
            return new File($item['contentUrl'], $item);
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
