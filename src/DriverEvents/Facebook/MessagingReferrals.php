<?php

namespace Mpociot\BotMan\DriverEvents\Facebook;

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
