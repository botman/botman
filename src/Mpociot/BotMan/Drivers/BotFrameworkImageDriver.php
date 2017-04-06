<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Message;
use Illuminate\Support\Collection;

class BotFrameworkImageDriver extends BotFrameworkDriver
{
    const DRIVER_NAME = 'BotFrameworkImage';

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        $hasImages = ! Collection::make($this->event->get('attachments'))->where('contentType', 'image')->isEmpty();

        return parent::matchesRequest() && $hasImages;
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        $message = new Message(BotMan::IMAGE_PATTERN, $this->event->get('from')['id'], $this->event->get('conversation')['id'], $this->payload);
        $message->setImages($this->getImagesUrls());

        return [$message];
    }

    /**
     * Retrieve image urls from an incoming message.
     *
     * @return array A download for the image file.
     */
    public function getImagesUrls()
    {
        return Collection::make($this->event->get('attachments'))->where('contentType', 'image')->pluck('contentUrl')->toArray();
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return false;
    }
}
