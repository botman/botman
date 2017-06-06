<?php

namespace Mpociot\BotMan\Drivers\WeChat;

use Mpociot\BotMan\Messages\Attachments\Location;
use Mpociot\BotMan\Messages\Incoming\IncomingMessage;

class WeChatLocationDriver extends WeChatDriver
{
    const DRIVER_NAME = 'WeChatLocation';

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
        return ! is_null($this->event->get('MsgType')) && $this->event->get('MsgType') === 'location';
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        $message = new IncomingMessage(Location::PATTERN, $this->event->get('ToUserName'), $this->event->get('FromUserName'),
            $this->event);
        $message->setLocation($this->getLocation());

        return [$message];
    }

    /**
     * Create a location object from an incoming message.
     * @return Location
     */
    private function getLocation()
    {
        return new Location($this->event->get('Location_X'), $this->event->get('Location_Y'), $this->event);
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return false;
    }
}
