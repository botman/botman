<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Message;

class WeChatVideoDriver extends WeChatDriver
{
    const DRIVER_NAME = 'WeChatVideo';

    /**
     * Return the driver name.
     * @return string
     */
    public function getName()
    {
        return self::DRIVER_NAME;
    }

    /**
     * Determine if the request is for this driver.
     * @return bool
     */
    public function matchesRequest()
    {
        return ! is_null($this->event->get('MsgType')) && $this->event->get('MsgType') === 'video';
    }

    /**
     * Retrieve the chat message.
     * @return array
     */
    public function getMessages()
    {
        $message = new Message(BotMan::VIDEO_PATTERN, $this->event->get('ToUserName'),
            $this->event->get('FromUserName'), $this->event);
        $message->setVideos($this->getVideo());

        return [$message];
    }

    /**
     * Create the video url from an incoming message.
     * @return array
     */
    private function getVideo()
    {
        $videoUrl = 'http://file.api.wechat.com/cgi-bin/media/get?access_token='.$this->getAccessToken().'&media_id='.$this->event->get('MediaId');

        return [$videoUrl];
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return false;
    }
}
