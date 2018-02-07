<?php

namespace BotMan\BotMan\tests\Storages;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Storages\Storage;
use BotMan\BotMan\Drivers\Tests\FakeDriver;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

class BotManStorageTest extends TestCase
{
    protected function getBot()
    {
        $botman = BotManFactory::create([]);

        /** @var FakeDriver $driver */
        $driver = m::mock(FakeDriver::class)->makePartial();
        $driver->messages = [new IncomingMessage('Hello again', 'UX12345', 'general')];

        $botman->setDriver($driver);

        return $botman;
    }

    public function tearDown()
    {
        exec('rm -rf '.__DIR__.'/../Fixtures/storage/*.json');
    }

    /** @test */
    public function it_creates_an_user_storage()
    {
        $bot = $this->getBot();
        $bot->hears('Hello again', function ($bot) {
            $storage = $bot->userStorage();
            $this->assertInstanceOf(Storage::class, $storage);
            $this->assertSame('user_', $storage->getPrefix());
            $this->assertSame('UX12345', $storage->getDefaultKey());
        });
        $bot->listen();
    }

    /** @test */
    public function it_creates_a_channel_storage()
    {
        $bot = $this->getBot();
        $bot->hears('Hello again', function ($bot) {
            $storage = $bot->channelStorage();

            $this->assertInstanceOf(Storage::class, $storage);
            $this->assertSame('channel_', $storage->getPrefix());
            $this->assertSame('general', $storage->getDefaultKey());
        });
        $bot->listen();
    }

    /** @test */
    public function it_creates_a_driver_storage()
    {
        $bot = $this->getBot();
        $bot->hears('Hello again', function ($bot) {
            $storage = $bot->driverStorage();

            $this->assertInstanceOf(Storage::class, $storage);
            $this->assertSame('driver_', $storage->getPrefix());
            $this->assertSame('Fake', $storage->getDefaultKey());
        });
        $bot->listen();
    }
}
