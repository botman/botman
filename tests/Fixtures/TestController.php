<?php

namespace BotMan\BotMan\Tests\Fixtures;

use BotMan\BotMan\BotMan;
use Illuminate\Http\Request;

class TestController
{
    public function __construct(Request $request)
    {
        $_SERVER['autowiring'] = true;
    }

    public function handle(BotMan $bot)
    {
    }
}
