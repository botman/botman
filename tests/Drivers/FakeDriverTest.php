<?php

namespace Mpociot\BotMan\Tests\Drivers;

use Mpociot\BotMan\Answer;
use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Message;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\BotManFactory;
use Mpociot\BotMan\DriverManager;
use Mpociot\BotMan\Drivers\FakeDriver;
use Mpociot\BotMan\Drivers\ProxyDriver;

/**
 * @covers \Mpociot\BotMan\Drivers\FakeDriver
 * @covers \Mpociot\BotMan\Drivers\ProxyDriver
 */
class FakeDriverTest extends PHPUnit_Framework_TestCase
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

    private function getDriver()
    {
        return new ProxyDriver;
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver();
        $this->assertSame('Fake Driver', $driver->getName());
    }

    /** @test */
    public function it_captures_messages()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->reply('World!');
        });

        $this->listenToFakeMessage('Hello', 'helloman123', '#helloworld');

        static::assertEquals(
            ['World!'],
            $this->fakeDriver->getBotMessages()
        );

        return $this->fakeDriver;
    }

    /** @test */
    public function it_captures_typing()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->types();
        });

        $this->listenToFakeMessage('Hello', 'helloman123', '#helloworld');

        static::assertTrue(
            $this->fakeDriver->isBotTyping()
        );

        return $this->fakeDriver;
    }

    /**
     * @depends it_captures_messages
     * @test
     */
    public function it_resets_bot_replies(FakeDriver $fakeDriver)
    {
        static::assertNotEquals([], $fakeDriver->getBotMessages());
        $fakeDriver->resetBotMessages();
        static::assertEquals([], $fakeDriver->getBotMessages());
    }

    /**
     * @depends it_captures_typing
     * @test
     */
    public function it_resets_bot_typing(FakeDriver $fakeDriver)
    {
        static::assertTrue($fakeDriver->isBotTyping());
        $fakeDriver->resetBotMessages();
        static::assertFalse($fakeDriver->isBotTyping());
    }

    /** @test */
    public function it_can_identify_itself_as_bot()
    {
        $this->fakeDriver->isBot = true;
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->reply('World!');
        });

        $this->listenToFakeMessage('Hello', 'helloman123', '#helloworld');

        static::assertEquals([], $this->fakeDriver->getBotMessages(), 'Bots should not get replies');
    }

    /** @test */
    public function it_works_with_questions_and_answers()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->ask('Who are you?', function (Answer $answer) use ($bot) {
                $bot->reply(sprintf('Hello, %s', $answer->getText()));
            });
        });

        $this->listenToFakeMessage('Hello', 'helloman123', '#helloworld');
        static::assertEquals(['Who are you?'], $this->fakeDriver->getBotMessages());

        $this->replyWithFakeMessage('Helloman', 'helloman123', '#helloworld');
        static::assertEquals(['Who are you?', 'Hello, Helloman'], $this->fakeDriver->getBotMessages());
    }

    private function listenToFakeMessage($message, $username, $channel)
    {
        $this->fakeDriver->messages = [new Message($message, $username, $channel)];
        $this->botman->listen();
    }

    private function replyWithFakeMessage($message, $username, $channel)
    {
        $this->fakeDriver->messages = [new Message($message, $username, $channel)];
        $this->botman->loadActiveConversation();
    }
}
