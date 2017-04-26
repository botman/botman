<?php

namespace Mpociot\BotMan\Tests;

use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Message;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\BotManFactory;
use Mpociot\BotMan\DriverManager;
use Mpociot\BotMan\Drivers\Tests\FakeDriver;
use Mpociot\BotMan\Drivers\Tests\ProxyDriver;
use Symfony\Component\HttpFoundation\Response;
use Mpociot\BotMan\Tests\Fixtures\TestCustomMiddleware;

class BotManMiddlewareTest extends PHPUnit_Framework_TestCase
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
    public function it_calls_received_middleware()
    {
        $this->botman->middleware->received(new TestCustomMiddleware());
        $this->replyWithFakeMessage('Hello');
        $this->assertEquals('Hello', $_SERVER['middleware_received']);
    }

    /** @test */
    public function it_calls_received_middleware_once_per_incoming_message()
    {
        $_SERVER['middleware_received_count'] = 0;
        $this->botman->middleware->received(new TestCustomMiddleware());

        $this->botman->hears('foo', function ($bot) {
        });
        $this->botman->hears('bar', function ($bot) {
        });

        $this->replyWithFakeMessage('Hello');
        $this->assertEquals(1, $_SERVER['middleware_received_count']);

        $_SERVER['middleware_received_count'] = 0;
        $this->fakeDriver->messages = [new Message('Hello', 'foo', 'bar'), new Message('Hello 2', 'foo', 'bar')];
        $this->botman->listen();
        $this->assertEquals(2, $_SERVER['middleware_received_count']);
    }

    /** @test */
    public function it_calls_sending_middleware()
    {
        $this->botman->middleware->sending(new TestCustomMiddleware());
        $this->botman->hears('Hello', function (BotMan $bot) {
            $response = $bot->reply('Hello youself!');
            $this->assertInstanceOf(Response::class, $response);
            $this->assertSame('"Hello youself! - middleware" - sending', $response->getContent());
        });

        $answers = ['Hello youself! - middleware'];
        $this->replyWithFakeMessage('Hello');
        $this->assertEquals($answers, $this->fakeDriver->getBotMessages());
    }

    private function replyWithFakeMessage($message, $username = 'helloman', $channel = '#helloworld')
    {
        if ($message instanceof Message) {
            $this->fakeDriver->messages = [$message];
        } else {
            $this->fakeDriver->messages = [new Message($message, $username, $channel)];
        }
        $this->botman->listen();
    }
}
