<?php

namespace BotMan\BotMan\tests\Drivers;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Drivers\Tests\FakeDriver;
use BotMan\BotMan\Drivers\Tests\ProxyDriver;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BotMan\BotMan\Drivers\Tests\FakeDriver
 * @covers \BotMan\BotMan\Drivers\Tests\ProxyDriver
 */
class FakeDriverTest extends TestCase
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

    private function getDriver()
    {
        return new ProxyDriver;
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver();
        $this->assertSame('Fake', $driver->getName());
    }

    /** @test */
    public function it_captures_messages()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->reply('World!');
        });

        $this->listenToFakeMessage('Hello', 'helloman123', '#helloworld');

        static::assertCount(1, $this->fakeDriver->getBotMessages());
        static::assertEquals(
            'World!',
            $this->fakeDriver->getBotMessages()[0]->getText()
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

    /**
     * @test
     */
    public function it_can_set_interactive_message_replies()
    {
        $message = new IncomingMessage('test', 'user', 'channel');
        $answer = $this->fakeDriver->getConversationAnswer($message);
        static::assertFalse($answer->isInteractiveMessageReply());

        $this->fakeDriver->isInteractiveMessageReply = true;
        $answer = $this->fakeDriver->getConversationAnswer($message);
        static::assertTrue($answer->isInteractiveMessageReply());
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
    public function it_can_fake_events()
    {
        $this->fakeDriver->setEventName('eventName');
        $this->fakeDriver->setEventPayload(['event' => 'data']);
        $this->botman->on('eventName', function ($payload, BotMan $bot) {
            $this->assertSame(['event' => 'data'], $payload);
        });

        $this->botman->listen();
    }

    /** @test */
    public function it_can_fake_users()
    {
        $userData = [
            'id' => '987',
            'first_name' => 'Marcel',
            'last_name' => 'Pociot',
            'username' => 'mpociot',
        ];
        $this->fakeDriver->setUser($userData);
        $this->botman->hears('user', function (BotMan $bot) use ($userData) {
            $user = $bot->getUser();
            $this->assertSame($userData, $user->getInfo());
            $this->assertSame('987', $user->getId());
            $this->assertSame('Marcel', $user->getFirstname());
            $this->assertSame('Pociot', $user->getLastname());
            $this->assertSame('mpociot', $user->getUsername());
        });

        $this->listenToFakeMessage('user', 'helloman123', '#helloworld');
    }

    /** @test */
    public function it_can_fake_users_from_fields_method()
    {
        $userData = [
            'id' => '987',
            'first_name' => 'Marcel',
            'last_name' => 'Pociot',
            'username' => 'mpociot',
        ];
        $this->fakeDriver->setUser($userData);
        $this->botman->hears('user', function (BotMan $bot) use ($userData) {
            $user = $bot->getUserWithFields(['first_name']);
            $this->assertSame($userData, $user->getInfo());
            $this->assertSame('987', $user->getId());
            $this->assertSame('Marcel', $user->getFirstname());
            $this->assertSame('Pociot', $user->getLastname());
            $this->assertSame('mpociot', $user->getUsername());
        });

        $this->listenToFakeMessage('user', 'helloman123', '#helloworld');
    }

    /** @test */
    public function it_can_fake_driver_name()
    {
        $this->fakeDriver->setName('custom_driver_name');
        $this->assertSame('custom_driver_name', $this->fakeDriver->getName());
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
        static::assertCount(1, $this->fakeDriver->getBotMessages());
        static::assertEquals('Who are you?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('Helloman', 'helloman123', '#helloworld');
        static::assertCount(2, $this->fakeDriver->getBotMessages());
        static::assertEquals('Who are you?', $this->fakeDriver->getBotMessages()[0]->getText());
        static::assertEquals('Hello, Helloman', $this->fakeDriver->getBotMessages()[1]->getText());
    }

    /**
     * @test
     **/
    public function it_returns_true_for_check_if_conv_callbacks_are_stored_serialized()
    {
        $this->assertTrue($this->fakeDriver->serializesCallbacks());
    }

    private function listenToFakeMessage($message, $username, $channel)
    {
        $this->fakeDriver->messages = [new IncomingMessage($message, $username, $channel)];
        $this->botman->listen();
    }

    private function replyWithFakeMessage($message, $username, $channel)
    {
        $this->fakeDriver->messages = [new IncomingMessage($message, $username, $channel)];
        $this->botman->loadActiveConversation();
    }
}
