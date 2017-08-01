<?php

namespace BotMan\BotMan\Tests\Fixtures;

use BotMan\BotMan\BotMan;

class TestClass
{
    public static $called = false;

    private $botman;

    public function __construct(BotMan $bot)
    {
        $this->botman = $bot;
    }

    public function foo()
    {
        self::$called = true;
    }

    public function exceptionHandler($exception, $bot)
    {
        self::$called = true;
    }

    public function __invoke(BotMan $bot)
    {
        self::$called = true;
    }
}
