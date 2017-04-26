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
        $this->botman->middleware->sending(new TestCustomMiddleware());
    }

    protected function tearDown()
    {
        ProxyDriver::setInstance(FakeDriver::createInactive());
    }

    /** @test */
    public function it_passes_payload_to_sending_middleware()
    {
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
