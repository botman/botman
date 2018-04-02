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
		$col = Collection::make($this->event->get('attachments'));
        $hasImages = ! $col->where('contentType', 'image')->isEmpty() ||
			! $col->where('contentType', 'image/jpeg')->isEmpty();

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
		$col = Collection::make($this->event->get('attachments'));
		$mergedResults = array_merge(
			$col->where('contentType', 'image')->pluck('contentUrl')->toArray(),
			$col->where('contentType', 'image/jpeg')->pluck('contentUrl')->toArray()
		);

        return $mergedResults;
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return false;
    }
}
