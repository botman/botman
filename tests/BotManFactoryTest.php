<?php

namespace Mpociot\BotMan\Tests;

use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\BotManFactory;
use PHPUnit_Framework_TestCase;
use React\EventLoop\Factory;

class BotManFactoryTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_create_botman_instances()
    {
        $this->assertInstanceOf(BotMan::class,BotManFactory::create([]));
        $this->assertInstanceOf(BotMan::class,BotManFactory::createForRTM([], Factory::create()));
    }
}
