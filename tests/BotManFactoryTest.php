<?php

namespace BotMan\BotMan\tests;

use BotMan\BotMan\BotMan;
use PHPUnit_Framework_TestCase;
use BotMan\BotMan\BotManFactory;

class BotManFactoryTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_create_botman_instances()
    {
        $this->assertInstanceOf(BotMan::class, BotManFactory::create([]));
    }

    /** @test */
    public function it_can_be_extended()
    {
        BotManFactory::extend('createCustomBot', function () {
            return 'foo';
        });
        $this->assertSame('foo', BotManFactory::createCustomBot());

        BotManFactory::extend('createCustomBotWithArgs', function ($arg1, $arg2) {
            return $arg1.' '.$arg2;
        });
        $this->assertSame('foo bar', BotManFactory::createCustomBotWithArgs('foo', 'bar'));
    }
}
