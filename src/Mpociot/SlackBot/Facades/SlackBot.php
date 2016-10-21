<?php

namespace Mpociot\SlackBot\Facades;

use Illuminate\Support\Facades\Facade;

class SlackBot extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'slackbot';
    }
}