<?php

namespace BotMan\BotMan\tests;

use Exception;
use Mockery as m;
use BotMan\BotMan\BotMan;
use PHPUnit\Framework\TestCase;
use BotMan\BotMan\BotManFactory;
use Illuminate\Support\Collection;
use BotMan\BotMan\Cache\ArrayCache;
use BotMan\BotMan\Drivers\Tests\FakeDriver;
use BotMan\BotMan\Tests\Fixtures\TestClass;
use BotMan\BotMan\Exceptions\Base\BotManException;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

class ExceptionTest extends TestCase
{
    /** @var ArrayCache */
    protected $cache;

    public function tearDown()
    {
        m::close();
    }

    public function setUp()
    {
        parent::setUp();
        $this->cache = new ArrayCache();
    }

    protected function getBot($data)
    {
        $botman = BotManFactory::create([], $this->cache);

        $data = Collection::make($data);
        /** @var FakeDriver $driver */
        $driver = m::mock(FakeDriver::class)->makePartial();

        $driver->isBot = $data->get('is_from_bot', false);
        $driver->messages = [new IncomingMessage($data->get('message'), $data->get('sender'), $data->get('recipient'))];

        $botman->setDriver($driver);

        return $botman;
    }

    /** @test */
    public function it_catches_exceptions()
    {
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hi Julia',
        ]);

        $botman->exception(Exception::class, function (Exception $exception, $bot) {
            $this->assertInstanceOf(Exception::class, $exception);
            $this->assertInstanceOf(BotMan::class, $bot);
            $this->assertSame('Whoops', $exception->getMessage());
        });

        $botman->hears('Hi Julia', function () {
            throw new Exception('Whoops');
        });

        $botman->listen();
    }

    /** @test */
    public function it_catches_exceptions_without_closures()
    {
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hi Julia',
        ]);

        $botman->exception(Exception::class, TestClass::class.'@exceptionHandler');

        $botman->hears('Hi Julia', function () {
            throw new Exception('Whoops');
        });

        $botman->listen();

        $this->assertTrue(TestClass::$called);
    }

    /** @test */
    public function it_catches_inherited_exceptions()
    {
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hi Julia',
        ]);

        $botman->exception(Exception::class, function (Exception $exception, $bot) {
            $this->assertInstanceOf(BotManException::class, $exception);
            $this->assertInstanceOf(BotMan::class, $bot);
            $this->assertSame('Whoops', $exception->getMessage());
        });

        $botman->hears('Hi Julia', function () {
            throw new BotManException('Whoops');
        });

        $botman->listen();
    }
}
