<?php

namespace Mpociot\BotMan\Tests\Drivers;

use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Message;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\BotManFactory;
use Mpociot\BotMan\DriverManager;
use Mpociot\BotMan\Drivers\FakeDriver;
use Mpociot\BotMan\Drivers\ProxyDriver;
use Mpociot\BotMan\Attachments\Location;
use Mpociot\BotMan\Tests\Fixtures\TestDataConversation;

class BotManConversationTest extends PHPUnit_Framework_TestCase
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
    public function it_repeats_invalid_image_answers()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $answers = ['What do you want to test?'];
        $this->replyWithFakeMessage('Hello');
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $this->replyWithFakeMessage('images');
        $answers[] = 'Please supply an image';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $this->replyWithFakeMessage('not_an_image');
        $answers[] = 'Please supply an image';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());
    }

    /** @test */
    public function it_calls_custom_repeat_method_on_invalid_image_answers()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $answers = ['What do you want to test?'];
        $this->replyWithFakeMessage('Hello');
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $this->replyWithFakeMessage('custom_image_repeat');
        $answers[] = 'Please supply an image';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $this->replyWithFakeMessage('not_an_image');
        $answers[] = 'That is not an image...';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());
    }

    /** @test */
    public function it_returns_the_images()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $answers = ['What do you want to test?'];
        $this->replyWithFakeMessage('Hello');
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $this->replyWithFakeMessage('images');
        $answers[] = 'Please supply an image';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $message = new Message(BotMan::IMAGE_PATTERN, 'helloman', '#helloworld');
        $message->setImages(['http://foo.com/bar.png']);
        $this->replyWithFakeMessage($message);

        $answers[] = 'http://foo.com/bar.png';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());
    }

    /** @test */
    public function it_repeats_invalid_videos_answers()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $answers = ['What do you want to test?'];
        $this->replyWithFakeMessage('Hello');
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $this->replyWithFakeMessage('videos');
        $answers[] = 'Please supply a video';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $this->replyWithFakeMessage('not_a_video');
        $answers[] = 'Please supply a video';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());
    }

    /** @test */
    public function it_calls_custom_repeat_method_on_invalid_videos_answers()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $answers = ['What do you want to test?'];
        $this->replyWithFakeMessage('Hello');
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $this->replyWithFakeMessage('custom_video_repeat');
        $answers[] = 'Please supply a video';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $this->replyWithFakeMessage('not_a_video');
        $answers[] = 'That is not a video...';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());
    }

    /** @test */
    public function it_returns_the_videos()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $answers = ['What do you want to test?'];
        $this->replyWithFakeMessage('Hello');
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $this->replyWithFakeMessage('videos');
        $answers[] = 'Please supply a video';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $message = new Message(BotMan::VIDEO_PATTERN, 'helloman', '#helloworld');
        $message->setVideos(['http://foo.com/bar.mp4']);
        $this->replyWithFakeMessage($message);

        $answers[] = 'http://foo.com/bar.mp4';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());
    }

    /** @test */
    public function it_repeats_invalid_audio_answers()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $answers = ['What do you want to test?'];
        $this->replyWithFakeMessage('Hello');
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $this->replyWithFakeMessage('audio');
        $answers[] = 'Please supply an audio';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $this->replyWithFakeMessage('not_an_audio');
        $answers[] = 'Please supply an audio';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());
    }

    /** @test */
    public function it_calls_custom_repeat_method_on_invalid_audio_answers()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $answers = ['What do you want to test?'];
        $this->replyWithFakeMessage('Hello');
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $this->replyWithFakeMessage('custom_audio_repeat');
        $answers[] = 'Please supply an audio';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $this->replyWithFakeMessage('not_an_audio');
        $answers[] = 'That is not an audio...';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());
    }

    /** @test */
    public function it_returns_the_audio()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $answers = ['What do you want to test?'];
        $this->replyWithFakeMessage('Hello');
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $this->replyWithFakeMessage('audio');
        $answers[] = 'Please supply an audio';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $message = new Message(BotMan::AUDIO_PATTERN, 'helloman', '#helloworld');
        $message->setAudio(['http://foo.com/bar.mp3']);
        $this->replyWithFakeMessage($message);

        $answers[] = 'http://foo.com/bar.mp3';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());
    }

    /** @test */
    public function it_repeats_invalid_location_answers()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $answers = ['What do you want to test?'];
        $this->replyWithFakeMessage('Hello');
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $this->replyWithFakeMessage('location');
        $answers[] = 'Please supply a location';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $this->replyWithFakeMessage('not_a_location');
        $answers[] = 'Please supply a location';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());
    }

    /** @test */
    public function it_calls_custom_repeat_method_on_invalid_location_answers()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $answers = ['What do you want to test?'];
        $this->replyWithFakeMessage('Hello');
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $this->replyWithFakeMessage('custom_location_repeat');
        $answers[] = 'Please supply a location';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $this->replyWithFakeMessage('not_a_location');
        $answers[] = 'That is not a location...';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());
    }

    /** @test */
    public function it_returns_the_location()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $answers = ['What do you want to test?'];
        $this->replyWithFakeMessage('Hello');
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $this->replyWithFakeMessage('location');
        $answers[] = 'Please supply a location';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());

        $message = new Message(BotMan::LOCATION_PATTERN, 'helloman', '#helloworld');
        $location = new Location(41.123, -12.123);
        $message->setLocation($location);
        $this->replyWithFakeMessage($message);

        $answers[] = '41.123:-12.123';
        static::assertEquals($answers, $this->fakeDriver->getBotMessages());
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
