<?php

namespace Mpociot\BotMan\DriverEvents\Facebook;

class MessagingReads extends FacebookEvent
{
    /**
     * Return the event name to match.
     *
     * @return string
     */
    public function getName()
    {
        return 'messaging_reads';
    }
}
