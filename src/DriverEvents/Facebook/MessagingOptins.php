<?php

namespace Mpociot\BotMan\DriverEvents\Facebook;

class MessagingOptins extends FacebookEvent
{
    /**
     * Return the event name to match.
     *
     * @return string
     */
    public function getName()
    {
    	return 'messaging_optins';
    }
}