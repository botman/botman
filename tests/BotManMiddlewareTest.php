<?php

namespace BotMan\BotMan\tests;

use BotMan\BotMan\BotMan;
use PHPUnit\Framework\TestCase;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Drivers\Tests\FakeDriver;
use BotMan\BotMan\Drivers\Tests\ProxyDriver;
use Symfony\Component\HttpFoundation\Response;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Tests\Fixtures\TestCustomMiddleware;

class BotManMiddlewareTest extends TestCase
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
    public function it_calls_captured_middleware()
    {
        $this->botman->middleware->captured(new TestCustomMiddleware());

        $this->botman->hears('Foo', function ($bot) {
            $bot->ask('what', function ($answer) {
            });
        });

        $this->replyWithFakeMessage('Foo');
        $this->assertFalse(isset($_SERVER['middleware_captured']));

        $this->replyWithFakeMessage('My Answer');
        $this->assertSame('My Answer', $_SERVER['middleware_captured']);
    }

    /** @test */
    public function it_has_access_to_previous_question()
    {
        $this->botman->middleware->captured(new TestCustomMiddleware());

        $this->botman->hears('Foo', function ($bot) {
            $bot->ask('My Question', function ($answer) {
            });
        });

        $this->replyWithFakeMessage('Foo');
        $this->replyWithFakeMessage('My Answer');

        $this->assertSame('My Question', $_SERVER['middleware_captured_question']);
    }

    /** @test */
    public function it_calls_global_matching_middleware()
    {
        $this->botman->hears('Hello(.*)', function () {
        });
        $this->botman->middleware->matching(new TestCustomMiddleware());
        $this->replyWithFakeMessage('Hello middleware');
        $this->assertEquals('Hello middleware-Hello(.*)', $_SERVER['middleware_matching']);
    }

    /** @test */
    public function it_calls_heard_middleware()
    {
        $this->botman->hears('Foo', function () {
        });

        $this->botman->middleware->heard(new TestCustomMiddleware());
        $this->replyWithFakeMessage('Hello middleware');
        $this->assertFalse(isset($_SERVER['middleware_heard_count']));

        $this->replyWithFakeMessage('Foo');
        $this->assertEquals(1, $_SERVER['middleware_heard_count']);
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
        $this->fakeDriver->messages = [new IncomingMessage('Hello', 'foo', 'bar'), new IncomingMessage('Hello 2', 'foo', 'bar')];
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

        $answers = 'Hello youself! - middleware';
        $this->replyWithFakeMessage('Hello');
        $this->assertEquals($answers, $this->fakeDriver->getBotMessages()[0]->getText());
    }

    /** @test */
    public function it_can_access_outgoing_message_in_sending_middleware()
    {
        $this->botman->middleware->sending(new TestCustomMiddleware());
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->reply('Hello yourself!');
            $this->assertSame('Hello yourself! - middleware', $_SERVER['middleware_sending_outgoing']->getText());
        });

        $this->replyWithFakeMessage('Hello');
        $this->assertNull($this->botman->getOutgoingMessage());
    }

    private function replyWithFakeMessage($message, $username = 'helloman', $channel = '#helloworld')
    {
        if ($message instanceof IncomingMessage) {
            $this->fakeDriver->messages = [$message];
        } else {
            $this->fakeDriver->messages = [new IncomingMessage($message, $username, $channel)];
        }
        $this->botman->listen();
    }
}
