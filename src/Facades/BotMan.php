<?php

namespace BotMan\BotMan\Facades;

use Illuminate\Support\Facades\Facade;

class BotMan extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'botman';
    }
}
