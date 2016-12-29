<?php

namespace Mpociot\BotMan\Tests\Storages;

use Mockery as m;
use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Message;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Drivers\Driver;
use Mpociot\BotMan\Storages\Storage;
use Mpociot\BotMan\Storages\BotManStorage;
use Mpociot\BotMan\Storages\Drivers\FileStorage;

class BotManStorageTest extends PHPUnit_Framework_TestCase
{
    /** @var BotManStorage */
    protected $storage;

    public function tearDown()
    {
        exec('rm -rf '.__DIR__.'/../Fixtures/storage/*.json');
    }

    /** @test */
    public function it_creates_an_user_storage()
    {
        $driver = new FileStorage(__DIR__.'/../Fixtures/storage');
        $storage = new BotManStorage($driver);

        $message = m::mock(Message::class);
        $message->shouldReceive('getUser')->once()->andReturn('foo');

        $bot = m::mock(BotMan::class);
        $bot->shouldReceive('getMessage')->once()->andReturn($message);

        $storage->setBotman($bot);

        $users = $storage->users();

        $this->assertInstanceOf(Storage::class, $users);
        $this->assertSame('user_', $users->getPrefix());
        $this->assertSame('foo', $users->getDefaultKey());
    }

    /** @test */
    public function it_creates_a_channel_storage()
    {
        $driver = new FileStorage(__DIR__.'/../Fixtures/storage');
        $storage = new BotManStorage($driver);

        $message = m::mock(Message::class);
        $message->shouldReceive('getChannel')->once()->andReturn('foo');

        $bot = m::mock(BotMan::class);
        $bot->shouldReceive('getMessage')->once()->andReturn($message);

        $storage->setBotman($bot);

        $users = $storage->channel();

        $this->assertInstanceOf(Storage::class, $users);
        $this->assertSame('channel_', $users->getPrefix());
        $this->assertSame('foo', $users->getDefaultKey());
    }

    /** @test */
    public function it_creates_a_driver_storage()
    {
        $driver = new FileStorage(__DIR__.'/../Fixtures/storage');
        $storage = new BotManStorage($driver);

        $botDriver = m::mock(Driver::class);
        $botDriver->shouldReceive('getName')->once()->andReturn('foo');

        $bot = m::mock(BotMan::class);
        $bot->shouldReceive('getDriver')->once()->andReturn($botDriver);

        $storage->setBotman($bot);

        $users = $storage->driver();

        $this->assertInstanceOf(Storage::class, $users);
        $this->assertSame('driver_', $users->getPrefix());
        $this->assertSame('foo', $users->getDefaultKey());
    }
}
