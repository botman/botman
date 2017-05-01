<?php

namespace Mpociot\BotMan\DriverEvents\Facebook;

class MessagingDeliveries extends FacebookEvent
{
    /**
     * Return the event name to match.
     *
     * @return string
     */
    public function getName()
    {
        return 'messaging_deliveries';
    }
}
