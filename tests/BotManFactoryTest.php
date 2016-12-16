<?php

namespace Mpociot\BotMan\Tests;

use Mpociot\BotMan\BotMan;
use React\EventLoop\Factory;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\BotManFactory;

class BotManFactoryTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_create_botman_instances()
    {
        $this->assertInstanceOf(BotMan::class, BotManFactory::create([]));
        $this->assertInstanceOf(BotMan::class, BotManFactory::createForRTM([], Factory::create()));
    }
}
