<?php

namespace Mpociot\BotMan\Tests;

use Mpociot\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\DriverManager;

class DriverManagerTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $driverManager = new DriverManager([], new Curl());
        $this->assertInstanceOf(DriverManager::class, $driverManager);
    }

    /** @test */
    public function it_can_get_available_drivers()
    {
        $this->assertTrue(is_array(DriverManager::getAvailableDrivers()));
    }

    /** @test */
    public function it_can_get_configured_drivers()
    {
        $this->assertCount(0, DriverManager::getConfiguredDrivers([]));

        $this->assertCount(1, DriverManager::getConfiguredDrivers([
            'slack_token' => 'foo',
        ]));

        $this->assertCount(2, DriverManager::getConfiguredDrivers([
            'slack_token' => 'foo',
            'nexmo_key' => 'foo',
            'nexmo_secret' => 'foo',
        ]));

        $this->assertCount(3, DriverManager::getConfiguredDrivers([
            'slack_token' => 'foo',
            'hipchat_urls' => ['foo'],
            'nexmo_key' => 'foo',
            'nexmo_secret' => 'foo',
        ]));
    }
}
