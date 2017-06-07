<?php

namespace BotMan\BotMan\Drivers\Facebook\Events;

class MessagingReferrals extends FacebookEvent
{
    /**
     * Return the event name to match.
     *
     * @return string
     */
    public function getName()
    {
        return 'messaging_referrals';
    }
}
