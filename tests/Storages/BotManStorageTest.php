<?php

namespace Mpociot\BotMan\Tests\Storages;

use Mockery as m;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\BotManFactory;
use Mpociot\BotMan\Storages\Storage;
use Mpociot\BotMan\Storages\BotManStorage;

class BotManStorageTest extends PHPUnit_Framework_TestCase
{
    /** @var BotManStorage */
    protected $storage;

    protected function getBot()
    {
        $data = [
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'Hello again',
            ],
        ];

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($data));

        return BotManFactory::create([], null, $request);
    }

    public function tearDown()
    {
        exec('rm -rf '.__DIR__.'/../Fixtures/storage/*.json');
    }

    /** @test */
    public function it_creates_an_user_storage()
    {
        $bot = $this->getBot();
        $bot->hears('Hello again', function () {
        });
        $bot->listen();

        $storage = $bot->userStorage();

        $this->assertInstanceOf(Storage::class, $storage);
        $this->assertSame('user_', $storage->getPrefix());
        $this->assertSame('UX12345', $storage->getDefaultKey());
    }

    /** @test */
    public function it_creates_a_channel_storage()
    {
        $bot = $this->getBot();
        $bot->hears('Hello again', function () {
        });
        $bot->listen();

        $storage = $bot->channelStorage();

        $this->assertInstanceOf(Storage::class, $storage);
        $this->assertSame('channel_', $storage->getPrefix());
        $this->assertSame('general', $storage->getDefaultKey());
    }

    /** @test */
    public function it_creates_a_driver_storage()
    {
        $bot = $this->getBot();
        $bot->hears('Hello again', function () {
        });
        $bot->listen();

        $storage = $bot->driverStorage();

        $this->assertInstanceOf(Storage::class, $storage);
        $this->assertSame('driver_', $storage->getPrefix());
        $this->assertSame('Slack', $storage->getDefaultKey());
    }
}
