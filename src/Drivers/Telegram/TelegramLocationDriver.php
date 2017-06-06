<?php

namespace Mpociot\BotMan\Drivers\Telegram;

use Mpociot\BotMan\Messages\Attachments\Location;
use Mpociot\BotMan\Messages\Incoming\IncomingMessage;

class TelegramLocationDriver extends TelegramDriver
{
    const DRIVER_NAME = 'TelegramLocation';

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return ! is_null($this->event->get('from')) && ! is_null($this->event->get('location'));
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        $message = new IncomingMessage(Location::PATTERN, $this->event->get('from')['id'], $this->event->get('chat')['id'],
            $this->event);
        $message->setLocation(new Location($this->event->get('location')['latitude'],
            $this->event->get('location')['longitude'], $this->event->get('location')));

        return [$message];
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return false;
    }
}
