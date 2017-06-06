<?php

namespace Mpociot\BotMan\Drivers\Facebook\Events;

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
