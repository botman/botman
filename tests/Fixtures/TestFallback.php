<?php

namespace Mpociot\BotMan\Tests\Fixtures;

use Mpociot\BotMan\BotMan;

class TestFallback
{
    public static $called = false;

    public function foo(BotMan $bot)
    {
        self::$called = true;
    }
}
