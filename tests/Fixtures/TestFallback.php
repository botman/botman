<?php

namespace BotMan\BotMan\Tests\Fixtures;

use BotMan\BotMan\BotMan;

class TestFallback
{
    public static $called = false;

    public function foo(BotMan $bot)
    {
        self::$called = true;
    }
}
