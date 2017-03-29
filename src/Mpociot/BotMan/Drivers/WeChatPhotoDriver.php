<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Message;

class WeChatPhotoDriver extends WeChatDriver
{
    const DRIVER_NAME = 'WeChatPhoto';

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
        return ! is_null($this->event->get('MsgType')) && $this->event->get('MsgType') === 'image';
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        $message = new Message(BotMan::IMAGE_PATTERN, $this->event->get('ToUserName'), $this->event->get('FromUserName'), $this->event);
        $message->setImages($this->getImages());

        return [$message];
    }

    /**
     * Retrieve image url from an incoming message.
     * @return array
     */
    private function getImages()
    {
        $photoUrl = $this->event->get('PicUrl');

        return [$photoUrl];
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return false;
    }
}
