<?php

namespace BotMan\BotMan\Tests;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Drivers\Tests\FakeDriver;
use BotMan\BotMan\Drivers\Tests\ProxyDriver;
use BotMan\BotMan\Interfaces\DriverEventInterface;
use PHPUnit\Framework\TestCase;

class BotManDriverEventTest extends TestCase
{
    /** @var BotMan */
    private $botman;
    /** @var FakeDriver */
    private $fakeDriver;

    public static function setUpBeforeClass(): void
    {
        DriverManager::loadDriver(ProxyDriver::class);
    }

    public static function tearDownAfterClass(): void
    {
        DriverManager::unloadDriver(ProxyDriver::class);
    }

    protected function setUp(): void
    {
        $this->fakeDriver = new FakeDriver();
        ProxyDriver::setInstance($this->fakeDriver);
        $this->botman = BotManFactory::create([]);
    }

    protected function tearDown(): void
    {
        ProxyDriver::setInstance(FakeDriver::createInactive());
    }

    /** @test */
    public function it_calls_driver_events()
    {
        $called = false;
        $this->fakeDriver->hasMatchingEvent = new TestEvent([]);
        $this->botman->on('test_event', function ($data, BotMan $bot) use (&$called) {
            $called = true;
        });
        $this->botman->listen();

        $this->assertTrue($called);
    }

    /** @test */
    public function it_can_assign_same_callback_for_events()
    {
        $called = 0;
        $this->fakeDriver->hasMatchingEvent = new TestEvent([]);
        $this->botman->on(['test_event', 'test_event_two'], function ($data, BotMan $bot) use (&$called) {
            $called++;
        });

        $this->botman->listen();

        $this->assertEquals(1, $called);

        $this->fakeDriver->hasMatchingEvent = new TestEventTwo([]);

        $this->botman->listen();

        $this->assertEquals(2, $called);
    }

    /** @test */
    public function it_calls_driver_events_without_closure()
    {
        $this->fakeDriver->hasMatchingEvent = new TestEvent([]);
        $this->botman->on('test_event', '\BotMan\BotMan\Tests\TestEventClass@event');
        $this->botman->listen();

        $this->assertSame([
            'event' => 'test_event',
            'data' => 'foo',
        ], $_SERVER['event_payload']);
    }

    /** @test */
    public function it_passes_driver_event_data()
    {
        $this->fakeDriver->hasMatchingEvent = new TestEvent([]);
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
     * @param $payload
     */
    public function __construct($payload)
    {
        //
    }

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

class TestEventTwo extends TestEvent
{
    /**
     * Return the event name to match.
     *
     * @return string
     */
    public function getName()
    {
        return 'test_event_two';
    }
}

class TestEventClass
{
    public function event($payload, $bot)
    {
        $_SERVER['event_payload'] = $payload;
    }
}
