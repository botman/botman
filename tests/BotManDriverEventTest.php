<?php

namespace Mpociot\BotMan\Tests;

use Mpociot\BotMan\BotMan;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\BotManFactory;
use Mpociot\BotMan\DriverManager;
use Mpociot\BotMan\Drivers\Tests\FakeDriver;
use Mpociot\BotMan\Drivers\Tests\ProxyDriver;
use Mpociot\BotMan\Interfaces\DriverEventInterface;

class BotManDriverEventTest extends PHPUnit_Framework_TestCase
{
    /** @var BotMan */
    private $botman;
    /** @var FakeDriver */
    private $fakeDriver;

    public static function setUpBeforeClass()
    {
        DriverManager::loadDriver(ProxyDriver::class);
    }

    public static function tearDownAfterClass()
    {
        DriverManager::unloadDriver(ProxyDriver::class);
    }

    protected function setUp()
    {
        $this->fakeDriver = new FakeDriver();
        ProxyDriver::setInstance($this->fakeDriver);
        $this->botman = BotManFactory::create([]);
    }

    protected function tearDown()
    {
        ProxyDriver::setInstance(FakeDriver::createInactive());
    }

    /** @test */
    public function it_calls_driver_events()
    {
        $called = false;
        $this->fakeDriver->hasMatchingEvent = new TestEvent();
        $this->botman->on('test_event', function ($data, BotMan $bot) use (&$called) {
            $called = true;
        });
        $this->botman->listen();

        $this->assertTrue($called);
    }

    /** @test */
    public function it_passes_driver_event_data()
    {
        $this->fakeDriver->hasMatchingEvent = new TestEvent();
        $this->botman->on('test_event', function ($data, BotMan $bot) {
            $this->assertSame([
                'event' => 'test_event',
                'data' => 'foo',
            ], $data);
        });

        $this->botman->listen();
    }
}

class TestEvent implements DriverEventInterface
{
    /**
     * Return the event name to match.
     *
     * @return string
     */
    public function getName()
    {
        return 'test_event';
    }

    /**
     * Return the event payload.
     *
     * @return mixed
     */
    public function getPayload()
    {
        return [
            'event' => 'test_event',
            'data' => 'foo',
        ];
    }
}
