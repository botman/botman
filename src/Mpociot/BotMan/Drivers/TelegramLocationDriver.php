<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Attachments\Location;
use Symfony\Component\HttpFoundation\Request;

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
        $message = new Message(BotMan::LOCATION_PATTERN, $this->event->get('from')['id'], $this->event->get('chat')['id'], $this->event);
        $message->setLocation(new Location($this->event->get('location')['latitude'], $this->event->get('location')['longitude']));

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
