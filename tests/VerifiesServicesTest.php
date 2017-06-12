<?php

namespace BotMan\BotMan\Tests;

use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Drivers\Tests\FakeDriver;
use PHPUnit_Framework_TestCase;

/**
 * Class VerifiesServicesTest.
 */
class VerifiesServicesTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_verify_drivers()
    {
        $this->assertFalse(isset($_SERVER['driver_verified']));

        DriverManager::loadDriver(FakeDriver::class);

        $botman = BotManFactory::create([]);
        $botman->listen();

        $this->assertTrue($_SERVER['driver_verified']);
    }
}