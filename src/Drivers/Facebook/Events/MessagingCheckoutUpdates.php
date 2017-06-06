<?php

namespace Mpociot\BotMan\Drivers\Facebook\Events;

class MessagingCheckoutUpdates extends FacebookEvent
{
    /**
     * Return the event name to match.
     *
     * @return string
     */
    public function getName()
    {
        return 'messaging_checkout_updates';
    }
}
