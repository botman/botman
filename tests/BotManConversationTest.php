<?php

namespace BotMan\BotMan\tests;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Cache\ArrayCache;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Drivers\Tests\FakeDriver;
use BotMan\BotMan\Drivers\Tests\ProxyDriver;
use BotMan\BotMan\Messages\Attachments\Audio;
use BotMan\BotMan\Messages\Attachments\Contact;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Attachments\Location;
use BotMan\BotMan\Messages\Attachments\Video;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Tests\Fixtures\TestConversation;
use BotMan\BotMan\Tests\Fixtures\TestDataConversation;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class BotManConversationTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var BotMan */
    private $botman;
    /** @var FakeDriver */
    private $fakeDriver;
    /** @var m\MockInterface */
    private $cache;

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
        $this->cache = m::mock(ArrayCache::class)->makePartial();
        ProxyDriver::setInstance($this->fakeDriver);
        $this->botman = BotManFactory::create([], $this->cache);
    }

    protected function tearDown(): void
    {
        ProxyDriver::setInstance(FakeDriver::createInactive());
        m::close();
    }

    /** @test */
    public function it_caches_conversation_for_30_minutes()
    {
        $message = new IncomingMessage('Hello', 'helloman', '#helloworld');

        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $this->cache->shouldReceive('put')
            ->with($message->getConversationIdentifier(), m::any(), 30)
            ->once();

        $this->replyWithFakeMessage('Hello');
    }

    /** @test */
    public function it_caches_conversation_for_custom_amount_of_minutes()
    {
        $this->fakeDriver = new FakeDriver();
        $this->cache = m::mock(ArrayCache::class)->makePartial();
        ProxyDriver::setInstance($this->fakeDriver);
        $this->botman = BotManFactory::create([
            'config' => [
                'conversation_cache_time' => '50',
            ],
        ], $this->cache);

        $message = new IncomingMessage('Hello', 'helloman', '#helloworld');

        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $this->cache->shouldReceive('put')
            ->with($message->getConversationIdentifier(), m::any(), 50)
            ->once();

        $this->replyWithFakeMessage('Hello');
    }

    /** @test */
    public function it_uses_conversation_defined_cache_time()
    {
        $message = new IncomingMessage('Hello', 'helloman', '#helloworld');

        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestConversation());
        });

        $this->cache->shouldReceive('put')
            ->with($message->getConversationIdentifier(), m::any(), 900)
            ->once();

        $this->replyWithFakeMessage('Hello');
    }

    /** @test */
    public function it_repeats_invalid_image_answers()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $this->replyWithFakeMessage('Hello');
        static::assertCount(1, $this->fakeDriver->getBotMessages());
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('images');

        static::assertCount(2, $this->fakeDriver->getBotMessages());
        static::assertEquals('Please supply an image', $this->fakeDriver->getBotMessages()[1]->getText());

        $this->replyWithFakeMessage('not_an_image');

        static::assertCount(3, $this->fakeDriver->getBotMessages());
        static::assertEquals('Please supply an image', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_calls_custom_repeat_method_on_invalid_image_answers()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $this->replyWithFakeMessage('Hello');
        static::assertCount(1, $this->fakeDriver->getBotMessages());
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('custom_image_repeat');
        static::assertCount(2, $this->fakeDriver->getBotMessages());
        static::assertEquals('Please supply an image', $this->fakeDriver->getBotMessages()[1]->getText());

        $this->replyWithFakeMessage('not_an_image');
        static::assertCount(3, $this->fakeDriver->getBotMessages());
        static::assertEquals('That is not an image...', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_returns_the_images()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $this->replyWithFakeMessage('Hello');
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('images');
        static::assertEquals('Please supply an image', $this->fakeDriver->getBotMessages()[1]->getText());

        $message = new IncomingMessage(Image::PATTERN, 'helloman', '#helloworld');
        $message->setImages(['http://foo.com/bar.png']);
        $this->replyWithFakeMessage($message);

        static::assertEquals('http://foo.com/bar.png', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_repeats_invalid_videos_answers()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $this->replyWithFakeMessage('Hello');
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('videos');
        static::assertEquals('Please supply a video', $this->fakeDriver->getBotMessages()[1]->getText());

        $this->replyWithFakeMessage('not_a_video');
        static::assertEquals('Please supply a video', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_calls_custom_repeat_method_on_invalid_videos_answers()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $this->replyWithFakeMessage('Hello');
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('custom_video_repeat');
        static::assertEquals('Please supply a video', $this->fakeDriver->getBotMessages()[1]->getText());

        $this->replyWithFakeMessage('not_a_video');
        static::assertEquals('That is not a video...', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_returns_the_videos()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $this->replyWithFakeMessage('Hello');
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('videos');
        static::assertEquals('Please supply a video', $this->fakeDriver->getBotMessages()[1]->getText());

        $message = new IncomingMessage(Video::PATTERN, 'helloman', '#helloworld');
        $message->setVideos(['http://foo.com/bar.mp4']);
        $this->replyWithFakeMessage($message);

        static::assertEquals('http://foo.com/bar.mp4', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_repeats_invalid_audio_answers()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $this->replyWithFakeMessage('Hello');
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('audio');
        static::assertEquals('Please supply an audio', $this->fakeDriver->getBotMessages()[1]->getText());

        $this->replyWithFakeMessage('not_an_audio');
        static::assertEquals('Please supply an audio', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_calls_custom_repeat_method_on_invalid_audio_answers()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $this->replyWithFakeMessage('Hello');
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('custom_audio_repeat');
        static::assertEquals('Please supply an audio', $this->fakeDriver->getBotMessages()[1]->getText());

        $this->replyWithFakeMessage('not_an_audio');
        static::assertEquals('That is not an audio...', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_returns_the_audio()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $this->replyWithFakeMessage('Hello');
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('audio');
        static::assertEquals('Please supply an audio', $this->fakeDriver->getBotMessages()[1]->getText());

        $message = new IncomingMessage(Audio::PATTERN, 'helloman', '#helloworld');
        $message->setAudio(['http://foo.com/bar.mp3']);
        $this->replyWithFakeMessage($message);

        static::assertEquals('http://foo.com/bar.mp3', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_repeats_invalid_location_answers()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $this->replyWithFakeMessage('Hello');
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('location');
        static::assertEquals('Please supply a location', $this->fakeDriver->getBotMessages()[1]->getText());

        $this->replyWithFakeMessage('not_a_location');
        static::assertEquals('Please supply a location', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_calls_custom_repeat_method_on_invalid_location_answers()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $this->replyWithFakeMessage('Hello');
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('custom_location_repeat');
        static::assertEquals('Please supply a location', $this->fakeDriver->getBotMessages()[1]->getText());

        $this->replyWithFakeMessage('not_a_location');
        static::assertEquals('That is not a location...', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_returns_the_location()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });
        $this->replyWithFakeMessage('Hello');
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());
        $this->replyWithFakeMessage('location');
        static::assertEquals('Please supply a location', $this->fakeDriver->getBotMessages()[1]->getText());
        $message = new IncomingMessage(Location::PATTERN, 'helloman', '#helloworld');
        $location = new Location(41.123, -12.123);
        $message->setLocation($location);
        $this->replyWithFakeMessage($message);
        static::assertEquals('41.123:-12.123', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_repeats_invalid_contact_answers()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $this->replyWithFakeMessage('Hello');
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('contact');
        static::assertEquals('Please supply a contact', $this->fakeDriver->getBotMessages()[1]->getText());

        $this->replyWithFakeMessage('not_a_contact');
        static::assertEquals('Please supply a contact', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_calls_custom_repeat_method_on_invalid_contact_answers()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $this->replyWithFakeMessage('Hello');
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('custom_contact_repeat');
        static::assertEquals('Please supply a contact', $this->fakeDriver->getBotMessages()[1]->getText());

        $this->replyWithFakeMessage('not_a_contact');
        static::assertEquals('That is not a contact...', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_returns_the_contact()
    {
        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversation(new TestDataConversation());
        });

        $this->replyWithFakeMessage('Hello');
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('contact');
        static::assertEquals('Please supply a contact', $this->fakeDriver->getBotMessages()[1]->getText());

        $message = new IncomingMessage(Contact::PATTERN, 'helloman', '#helloworld');
        $contact = new Contact('0775269856', 'Daniele', 'Rapisarda', '123');
        $message->setcontact($contact);
        $this->replyWithFakeMessage($message);

        static::assertEquals('0775269856:Daniele:Rapisarda:123', $this->fakeDriver->getBotMessages()[2]->getText());
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
