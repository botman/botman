<?php

namespace BotMan\BotMan\tests;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Cache\ArrayCache;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Drivers\NullDriver;
use BotMan\BotMan\Drivers\Tests\FakeDriver;
use BotMan\BotMan\Drivers\Tests\ProxyDriver;
use BotMan\BotMan\Exceptions\Core\ContainerNotSetException;
use BotMan\BotMan\Messages\Attachments\Audio;
use BotMan\BotMan\Messages\Attachments\Contact;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Attachments\Location;
use BotMan\BotMan\Messages\Attachments\Video;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Tests\Fixtures\TestConversation;
use BotMan\BotMan\Tests\Fixtures\TestConversationService;
use BotMan\BotMan\Tests\Fixtures\TestConversationServiceDependency;
use BotMan\BotMan\Tests\Fixtures\TestDataConversation;
use Illuminate\Support\Collection;
use Mockery as m;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class BotManTest.
 */
class BotManConversationServiceTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var BotMan */
    private $botman;
    /** @var MockInterface */
    protected $commander;
    /** @var ArrayCache */
    protected $cache;
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

    public function tearDown()
    {
        ProxyDriver::setInstance(FakeDriver::createInactive());
        m::close();
    }

    public function setUp()
    {
        parent::setUp();
        $this->fakeDriver = new FakeDriver();
        $this->cache = m::mock(ArrayCache::class)->makePartial();
        ProxyDriver::setInstance($this->fakeDriver);
        $this->botman = BotManFactory::create([], $this->cache);
    }

    protected function getBot($data)
    {
        $botman = BotManFactory::create([], $this->cache);

        $data = Collection::make($data);
        /** @var FakeDriver $driver */
        $driver = m::mock(FakeDriver::class)->makePartial();

        $driver->isBot    = $data->get('is_from_bot', false);
        $driver->messages = [new IncomingMessage($data->get('message'), $data->get('sender'), $data->get('recipient'))];

        $botman->setDriver($driver);

        return $botman;
    }

    protected function getContainerWithConversationService()
    {

        /** @var ContainerInterface|m\Mock $containerMock */
        $containerMock = m::mock(ContainerInterface::class);
        $containerMock->shouldReceive('get')
            ->with(TestConversation::class)
            ->once()
            ->andReturn(new TestConversation());

        return $containerMock;
    }

    protected function getContainerWithDataConversationService()
    {

        /** @var ContainerInterface|m\Mock $containerMock */
        $containerMock = m::mock(ContainerInterface::class);
        $containerMock->shouldReceive('get')
            ->with(TestDataConversation::class)
            ->once()
            ->andReturn(new TestDataConversation());

        return $containerMock;
    }

    protected function getBotWithInteractiveData($payload)
    {
        /** @var \Illuminate\Http\Request $request */
        $request = new \Illuminate\Http\Request();
        $request->replace([
            'payload' => $payload,
        ]);

        return BotManFactory::create([], $this->cache, $request);
    }

    /** @test */
    public function it_can_start_conversations_from_container_services()
    {
        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'general',
            'message'   => 'foo',
        ]);

        $conversationService = m::mock(TestConversationService::class);
        $conversationService->shouldReceive('setBot')
            ->once()
            ->with($botman);

        /** @var ContainerInterface|m\Mock $containerMock */
        $containerMock = m::mock(ContainerInterface::class);
        $containerMock->shouldReceive('get')
            ->with('conversationService')
            ->once()
            ->andReturn($conversationService);

        $botman->setContainer($containerMock);

        $botman->hears('foo', function () {
        });
        $botman->listen();


        $conversationService->shouldReceive('run')
            ->once();

        $botman->startConversationService('conversationService');
    }

    /** @test */
    public function it_can_start_conversations_from_container_services_and_those_conversations_can_call_dependencies()
    {
        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'general',
            'message'   => 'foo',
        ]);

        $conversationServiceDependency = m::mock();
        $conversationServiceDependency->shouldReceive('foo')
            ->once()
            ->andReturn('bar');

        $conversationService = new TestConversationService($conversationServiceDependency);

        /** @var ContainerInterface|m\Mock $containerMock */
        $containerMock = m::mock(ContainerInterface::class);
        $containerMock->shouldReceive('get')
            ->with(TestConversationService::class)
            ->once()
            ->andReturn($conversationService);

        $botman->setContainer($containerMock);

        $botman->hears('foo', function ($bot) {
            $bot->startConversationService(TestConversationService::class);
        });
        $botman->listen();
    }

    /** @test */
    public function it_can_call_dependencies_in_a_question_in_a_service()
    {
        $driver = m::mock(NullDriver::class)->makePartial();

        $driver->shouldReceive('getMessages')
            ->andReturn([new IncomingMessage('Hi Julia', 'UX12345', 'general')]);

        $driver->shouldReceive('buildServicePayload')
            ->once()
            ->withArgs(function ($message, $match, $arguments) {
                return $message->getText() === 'This is a test question' && ($match instanceof IncomingMessage) && $arguments === [];
            });

        $driver->shouldReceive('sendPayload')
            ->once();

        $conversationServiceDependency = m::mock();
        $conversationServiceDependency->shouldReceive('foo', 'baz')
            ->once()
            ->andReturn('bar', 'foobar');

        $conversationService = new TestConversationService($conversationServiceDependency);

        /** @var ContainerInterface|m\Mock $containerMock */
        $containerMock = m::mock(ContainerInterface::class);
        $containerMock->shouldReceive('get')
            ->with(TestConversationService::class)
            ->once()
            ->andReturn($conversationService);


        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'C12345',
            'message'   => 'Hi Julia',
        ]);
        $botman->setContainer($containerMock);
        $botman->setDriver($driver);

        $botman->hears('Hi Julia', function ($bot) {
            $bot->startConversationService(TestConversationService::class);
        });
        $botman->listen();

        /*
         * Now that the first message is saved, fake a reply
         */
        $botman = $this->getBot([]);

        $driver->shouldReceive('getConversationAnswer')
            ->andReturn(Answer::create('dependency'));

        $driver->shouldReceive('getMessages')
            ->andReturn([new IncomingMessage('dependency', 'UX12345', 'general')]);

        $driver->shouldReceive('buildServicePayload')
            ->once()
            ->withArgs(function ($message, $match, $arguments) {
                return $message->getText() === 'bar' && ($match instanceof IncomingMessage) && $arguments === [];
            });

        $driver->shouldReceive('sendPayload')
            ->once();

        $botman->setDriver($driver);

        $botman->listen();
    }

    /** @test */
    public function it_throws_not_found_exception_when_conversation_service_is_not_registered_in_container()
    {
        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'general',
            'message'   => 'foo',
        ]);

        /** @var ContainerInterface|m\Mock $containerMock */
        $containerMock = m::mock(ContainerInterface::class);
        $exceptionMock = new class() extends \Exception implements NotFoundExceptionInterface {
        };
        $containerMock->shouldReceive('get')->once()->andThrow($exceptionMock);

        $botman->setContainer($containerMock);

        $this->expectException(NotFoundExceptionInterface::class);

        $botman->hears('foo', function () {
        });
        $botman->listen();

        $botman->startConversationService('conversationService');
    }

    /** @test */
    public function it_throws_exception_when_conversation_service_is_called_but_there_is_no_container()
    {
        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'general',
            'message'   => 'foo',
        ]);

        $this->expectException(ContainerNotSetException::class);

        $botman->hears('foo', function () {
        });
        $botman->listen();

        $botman->startConversationService('conversationService');
    }

    /** @test */
    public function it_can_repeat_a_question_in_a_service()
    {
        $driver = m::mock(NullDriver::class)->makePartial();

        $driver->shouldReceive('getMessages')
            ->andReturn([new IncomingMessage('Hi Julia', 'UX12345', 'general')]);

        $driver->shouldReceive('buildServicePayload')
            ->once()
            ->withArgs(function ($message, $match, $arguments) {
                return $message->getText() === 'This is a test question' && ($match instanceof IncomingMessage) && $arguments === [];
            });

        $driver->shouldReceive('sendPayload')
            ->once();

        /** @var ContainerInterface|m\Mock $containerMock */
        $containerMock = m::mock(ContainerInterface::class);
        $containerMock->shouldReceive('get')
            ->with(TestConversationService::class)
            ->once()
            ->andReturn(new TestConversationService());


        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'C12345',
            'message'   => 'Hi Julia',
        ]);
        $botman->setContainer($containerMock);
        $botman->setDriver($driver);

        $botman->hears('Hi Julia', function ($bot) {
            $bot->startConversationService(TestConversationService::class);
        });
        $botman->listen();

        /*
         * Now that the first message is saved, fake a reply
         */
        $botman = $this->getBot([]);

        $driver->shouldReceive('getConversationAnswer')
            ->andReturn(Answer::create('repeat'));

        $driver->shouldReceive('getMessages')
            ->andReturn([new IncomingMessage('repeat', 'UX12345', 'general')]);

        $driver->shouldReceive('buildServicePayload')
            ->once()
            ->withArgs(function ($message, $match, $arguments) {
                return $message->getText() === 'This is a test question' && ($match instanceof IncomingMessage) && $arguments === [];
            });

        $driver->shouldReceive('sendPayload')
            ->once();

        $botman->setDriver($driver);

        $botman->listen();
    }

    /** @test */
    public function it_can_repeat_a_modified_question_in_a_service()
    {
        $driver = m::mock(NullDriver::class)->makePartial();

        $driver->shouldReceive('getMessages')
            ->andReturn([new IncomingMessage('Hi Julia', 'UX12345', 'general')]);

        $driver->shouldReceive('buildServicePayload')
            ->once()
            ->withArgs(function ($message, $match, $arguments) {
                return $message->getText() === 'This is a test question' && ($match instanceof IncomingMessage) && $arguments === [];
            });

        $driver->shouldReceive('sendPayload')
            ->once();

        /** @var ContainerInterface|m\Mock $containerMock */
        $containerMock = m::mock(ContainerInterface::class);
        $containerMock->shouldReceive('get')
            ->with(TestConversationService::class)
            ->once()
            ->andReturn(new TestConversationService());

        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'C12345',
            'message'   => 'Hi Julia',
        ]);

        $botman->setContainer($containerMock);

        $botman->setDriver($driver);

        $botman->hears('Hi Julia', function ($bot) {
            $bot->startConversationService(TestConversationService::class);
        });
        $botman->listen();

        /*
         * Now that the first message is saved, fake a reply
         */
        $botman = $this->getBot([]);

        $driver->shouldReceive('getConversationAnswer')
            ->andReturn(Answer::create('repeat_modified'));

        $driver->shouldReceive('getMessages')
            ->andReturn([new IncomingMessage('repeat_modified', 'UX12345', 'general')]);

        $driver->shouldReceive('buildServicePayload')
            ->once()
            ->withArgs(function ($message, $match, $arguments) {
                return $message->getText() === 'This is a modified test question' && ($match instanceof IncomingMessage) && $arguments === [];
            });

        $driver->shouldReceive('sendPayload')
            ->once();

        $botman->setDriver($driver);

        $botman->listen();
    }

    /** @test */
    public function it_can_skip_a_running_conversation_in_a_service()
    {
        $called = false;

        /** @var ContainerInterface|m\Mock $containerMock */
        $containerMock = m::mock(ContainerInterface::class);
        $containerMock->shouldReceive('get')
            ->with(TestConversationService::class)
            ->once()
            ->andReturn(new TestConversationService());

        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'general',
            'message'   => 'Hi Julia',
        ]);

        $botman->setContainer($containerMock);

        $botman->hears('Hi Julia', function ($bot) {
            $bot->startConversationService(TestConversationService::class);
        });
        $botman->listen();

        /*
         * Now that the first message is saved, fake a reply.
         * This should get skipped!
         */
        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'general',
            'message'   => 'skip_keyword',
        ]);

        $botman->hears('skip_keyword', function ($bot) use (&$called) {
            $called = true;
        });

        $botman->listen();

        $this->assertTrue($called);

        $cacheKey = 'conversation-' . sha1('UX12345') . '-' . sha1('general');
        $this->assertTrue($this->cache->has($cacheKey));

        /*
         * This should now get picked up the usual way.
         */
        $called = false;
        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'general',
            'message'   => 'repeat',
        ]);

        $botman->hears('repeat', function ($bot) use (&$called) {
            $called = true;
        });

        $botman->listen();

        $this->assertFalse($called);
    }

    /** @test */
    public function it_can_skip_a_running_conversation_fluently_in_a_service()
    {
        $called = false;
        $driver = m::mock(NullDriver::class)->makePartial();

        $driver->shouldReceive('getMessages')
            ->andReturn([new IncomingMessage('Hi Julia', 'UX12345', 'general')]);

        $driver->shouldReceive('buildServicePayload')
            ->once()
            ->withArgs(function ($message, $match, $arguments) {
                return $message->getText() === 'This is a test question' && ($match instanceof IncomingMessage) && $arguments === [];
            });

        $driver->shouldReceive('sendPayload')
            ->once();


        /** @var ContainerInterface|m\Mock $containerMock */
        $containerMock = m::mock(ContainerInterface::class);
        $containerMock->shouldReceive('get')
            ->with(TestConversationService::class)
            ->once()
            ->andReturn(new TestConversationService());

        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'general',
            'message'   => 'Hi Julia',
        ]);

        $botman->setContainer($containerMock);

        $botman->setDriver($driver);

        $botman->hears('Hi Julia', function ($bot) {
            $bot->startConversationService(TestConversationService::class);
        });
        $botman->listen();

        /*
         * Now that the first message is saved, fake a reply.
         * This should get skipped!
         */
        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'general',
            'message'   => 'fluent_skip_keyword',
        ]);

        $botman->hears('fluent_skip_keyword', function ($bot) use (&$called) {
            $called = true;
        })->skipsConversation();

        $botman->listen();

        $this->assertTrue($called);

        $cacheKey = 'conversation-' . sha1('UX12345') . '-' . sha1('general');
        $this->assertTrue($this->cache->has($cacheKey));

        /*
         * This should now get picked up the usual way.
         */
        $called = false;
        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'general',
            'message'   => 'repeat',
        ]);

        $botman->hears('repeat', function ($bot) use (&$called) {
            $called = true;
        });

        $botman->listen();

        $this->assertFalse($called);
    }

    /** @test */
    public function it_can_skip_a_running_conversation_with_group_attribute_in_a_service()
    {
        $called = false;
        $driver = m::mock(NullDriver::class)->makePartial();

        $driver->shouldReceive('getMessages')
            ->andReturn([new IncomingMessage('Hi Julia', 'UX12345', 'general')]);

        $driver->shouldReceive('buildServicePayload')
            ->once()
            ->withArgs(function ($message, $match, $arguments) {
                return $message->getText() === 'This is a test question' && ($match instanceof IncomingMessage) && $arguments === [];
            });

        $driver->shouldReceive('sendPayload')
            ->once();

        /** @var ContainerInterface|m\Mock $containerMock */
        $containerMock = m::mock(ContainerInterface::class);
        $containerMock->shouldReceive('get')
            ->with(TestConversationService::class)
            ->once()
            ->andReturn(new TestConversationService());

        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'general',
            'message'   => 'Hi Julia',
        ]);

        $botman->setContainer($containerMock);

        $botman->setDriver($driver);

        $botman->hears('Hi Julia', function ($bot) {
            $bot->startConversationService(TestConversationService::class);
        });
        $botman->listen();

        /*
         * Now that the first message is saved, fake a reply.
         * This should get skipped!
         */
        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'general',
            'message'   => 'group_skip_keyword',
        ]);

        $botman->group(['skip_conversation' => true], function ($bot) use (&$called) {
            $bot->hears('group_skip_keyword', function ($bot) use (&$called) {
                $called = true;
            });
        });

        $botman->listen();

        $this->assertTrue($called);

        $cacheKey = 'conversation-' . sha1('UX12345') . '-' . sha1('general');
        $this->assertTrue($this->cache->has($cacheKey));

        /*
         * This should now get picked up the usual way.
         */
        $called = false;
        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'general',
            'message'   => 'repeat',
        ]);

        $botman->hears('repeat', function ($bot) use (&$called) {
            $called = true;
        });

        $botman->listen();

        $this->assertFalse($called);
    }

    /** @test */
    public function it_can_stop_a_running_conversation_fluently_in_a_service()
    {
        $called = false;
        $driver = m::mock(NullDriver::class)->makePartial();

        $driver->shouldReceive('getMessages')
            ->andReturn([new IncomingMessage('Hi Julia', 'UX12345', 'general')]);

        $driver->shouldReceive('buildServicePayload')
            ->once()
            ->withArgs(function ($message, $match, $arguments) {
                return $message->getText() === 'This is a test question' && ($match instanceof IncomingMessage) && $arguments === [];
            });

        $driver->shouldReceive('sendPayload')
            ->once();


        /** @var ContainerInterface|m\Mock $containerMock */
        $containerMock = m::mock(ContainerInterface::class);
        $containerMock->shouldReceive('get')
            ->with(TestConversationService::class)
            ->once()
            ->andReturn(new TestConversationService());

        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'general',
            'message'   => 'Hi Julia',
        ]);

        $botman->setContainer($containerMock);

        $botman->setDriver($driver);

        $botman->hears('Hi Julia', function ($bot) {
            $bot->startConversationService(TestConversationService::class);
        });
        $botman->listen();

        /*
         * Now that the first message is saved, fake a reply.
         * This should get skipped!
         */
        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'general',
            'message'   => 'fluent_stop_keyword',
        ]);

        $botman->hears('fluent_stop_keyword', function ($bot) use (&$called) {
            $called = true;
        })->stopsConversation();
        $botman->listen();

        $this->assertTrue($called);

        // Conversation should be removed from cache
        $cacheKey = 'conversation-' . sha1('UX12345') . '-' . sha1('general');
        $this->assertFalse($this->cache->has($cacheKey));

        /*
         * This should now get picked up the usual way.
         */
        $called = false;
        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'general',
            'message'   => 'repeat',
        ]);

        $botman->hears('repeat', function ($bot) use (&$called) {
            $called = true;
        });

        $botman->listen();

        $this->assertTrue($called);
    }

    /** @test */
    public function it_can_stop_a_running_conversation_with_group_attribute_in_a_service()
    {
        $called = false;
        $driver = m::mock(NullDriver::class)->makePartial();

        $driver->shouldReceive('getMessages')
            ->andReturn([new IncomingMessage('Hi Julia', 'UX12345', 'general')]);

        $driver->shouldReceive('buildServicePayload')
            ->once()
            ->withArgs(function ($message, $match, $arguments) {
                return $message->getText() === 'This is a test question' && ($match instanceof IncomingMessage) && $arguments === [];
            });

        $driver->shouldReceive('sendPayload')
            ->once();

        /** @var ContainerInterface|m\Mock $containerMock */
        $containerMock = m::mock(ContainerInterface::class);
        $containerMock->shouldReceive('get')
            ->with(TestConversationService::class)
            ->once()
            ->andReturn(new TestConversationService());


        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'general',
            'message'   => 'Hi Julia',
        ]);

        $botman->setContainer($containerMock);

        $botman->setDriver($driver);

        $botman->hears('Hi Julia', function ($bot) {
            $bot->startConversationService(TestConversationService::class);
        });
        $botman->listen();

        /*
         * Now that the first message is saved, fake a reply.
         * This should get skipped!
         */
        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'general',
            'message'   => 'group_skip_keyword',
        ]);

        $botman->group(['stop_conversation' => true], function ($bot) use (&$called) {
            $bot->hears('group_skip_keyword', function ($bot) use (&$called) {
                $called = true;
            });
        });
        $botman->listen();

        $this->assertTrue($called);

        // Conversation should be removed from cache
        $cacheKey = 'conversation-' . sha1('UX12345') . '-' . sha1('general');
        $this->assertFalse($this->cache->has($cacheKey));

        /*
         * This should now get picked up the usual way.
         */
        $called = false;
        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'general',
            'message'   => 'repeat',
        ]);

        $botman->hears('repeat', function ($bot) use (&$called) {
            $called = true;
        });

        $botman->listen();

        $this->assertTrue($called);
    }

    /** @test */
    public function it_can_stop_a_running_conversation_in_a_service()
    {
        $called = false;
        $driver = m::mock(NullDriver::class)->makePartial();

        $driver->shouldReceive('getMessages')
            ->andReturn([new IncomingMessage('Hi Julia', 'UX12345', 'general')]);

        $driver->shouldReceive('buildServicePayload')
            ->once()
            ->withArgs(function ($message, $match, $arguments) {
                return $message->getText() === 'This is a test question' && ($match instanceof IncomingMessage) && $arguments === [];
            });

        $driver->shouldReceive('sendPayload')
            ->once();

        /** @var ContainerInterface|m\Mock $containerMock */
        $containerMock = m::mock(ContainerInterface::class);
        $containerMock->shouldReceive('get')
            ->with(TestConversationService::class)
            ->once()
            ->andReturn(new TestConversationService());

        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'general',
            'message'   => 'Hi Julia',
        ]);

        $botman->setContainer($containerMock);
        $botman->setDriver($driver);

        $botman->hears('Hi Julia', function ($bot) {
            $bot->startConversationService(TestConversationService::class);
        });
        $botman->listen();

        /*
         * Now that the first message is saved, fake a reply.
         * This should get skipped!
         */
        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'general',
            'message'   => 'stop_keyword',
        ]);

        $botman->hears('stop_keyword', function ($bot) use (&$called) {
            $called = true;
        });
        $botman->listen();

        $this->assertTrue($called);

        // Conversation should be removed from cache
        $cacheKey = 'conversation-' . sha1('UX12345') . '-' . sha1('general');
        $this->assertFalse($this->cache->has($cacheKey));

        /*
         * This should now get picked up the usual way.
         */
        $called = false;
        $botman = $this->getBot([
            'sender'    => 'UX12345',
            'recipient' => 'general',
            'message'   => 'repeat',
        ]);

        $botman->hears('repeat', function ($bot) use (&$called) {
            $called = true;
        });

        $botman->listen();

        $this->assertTrue($called);
    }

    /** @test */
    public function it_caches_conversation_for_30_minutes_in_a_service()
    {
        $this->botman->setContainer($this->getContainerWithDataConversationService());

        $message = new IncomingMessage('Hello', 'helloman', '#helloworld');

        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversationService(TestDataConversation::class);
        });

        $this->cache->shouldReceive('put')
            ->with($message->getConversationIdentifier(), m::any(), 30)
            ->once();

        $this->replyWithFakeMessage('Hello');
    }

    /** @test */
    public function it_caches_conversation_for_custom_amount_of_minutes_in_a_service()
    {
        $this->fakeDriver = new FakeDriver();
        $this->cache      = m::mock(ArrayCache::class)->makePartial();
        ProxyDriver::setInstance($this->fakeDriver);
        $this->botman = BotManFactory::create([
            'config' => [
                'conversation_cache_time' => '50',
            ],
        ], $this->cache);

        $this->botman->setContainer($this->getContainerWithDataConversationService());

        $message = new IncomingMessage('Hello', 'helloman', '#helloworld');

        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversationService(TestDataConversation::class);
        });

        $this->cache->shouldReceive('put')
            ->with($message->getConversationIdentifier(), m::any(), 50)
            ->once();

        $this->replyWithFakeMessage('Hello');
    }

    /** @test */
    public function it_uses_conversation_defined_cache_time_in_a_service()
    {
        $this->botman->setContainer($this->getContainerWithConversationService());

        $message = new IncomingMessage('Hello', 'helloman', '#helloworld');

        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversationService(TestConversation::class);
        });

        $this->cache->shouldReceive('put')
            ->with($message->getConversationIdentifier(), m::any(), 900)
            ->once();

        $this->replyWithFakeMessage('Hello');
    }

    /** @test */
    public function it_repeats_invalid_image_answers_in_a_service()
    {
        $this->botman->setContainer($this->getContainerWithDataConversationService());

        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversationService(TestDataConversation::class);
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
    public function it_calls_custom_repeat_method_on_invalid_image_answers_in_a_service()
    {
        $this->botman->setContainer($this->getContainerWithDataConversationService());

        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversationService(TestDataConversation::class);
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
    public function it_returns_the_images_in_a_service()
    {
        $this->botman->setContainer($this->getContainerWithDataConversationService());

        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversationService(TestDataConversation::class);
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
    public function it_repeats_invalid_videos_answers_in_a_service()
    {
        $this->botman->setContainer($this->getContainerWithDataConversationService());

        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversationService(TestDataConversation::class);
        });

        $this->replyWithFakeMessage('Hello');
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('videos');
        static::assertEquals('Please supply a video', $this->fakeDriver->getBotMessages()[1]->getText());

        $this->replyWithFakeMessage('not_a_video');
        static::assertEquals('Please supply a video', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_calls_custom_repeat_method_on_invalid_videos_answers_in_a_service()
    {
        $this->botman->setContainer($this->getContainerWithDataConversationService());

        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversationService(TestDataConversation::class);
        });

        $this->replyWithFakeMessage('Hello');
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('custom_video_repeat');
        static::assertEquals('Please supply a video', $this->fakeDriver->getBotMessages()[1]->getText());

        $this->replyWithFakeMessage('not_a_video');
        static::assertEquals('That is not a video...', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_returns_the_videos_in_a_service()
    {
        $this->botman->setContainer($this->getContainerWithDataConversationService());

        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversationService(TestDataConversation::class);
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
    public function it_repeats_invalid_audio_answers_in_a_service()
    {
        $this->botman->setContainer($this->getContainerWithDataConversationService());

        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversationService(TestDataConversation::class);
        });

        $this->replyWithFakeMessage('Hello');
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('audio');
        static::assertEquals('Please supply an audio', $this->fakeDriver->getBotMessages()[1]->getText());

        $this->replyWithFakeMessage('not_an_audio');
        static::assertEquals('Please supply an audio', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_calls_custom_repeat_method_on_invalid_audio_answers_in_a_service()
    {
        $this->botman->setContainer($this->getContainerWithDataConversationService());

        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversationService(TestDataConversation::class);
        });

        $this->replyWithFakeMessage('Hello');
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('custom_audio_repeat');
        static::assertEquals('Please supply an audio', $this->fakeDriver->getBotMessages()[1]->getText());

        $this->replyWithFakeMessage('not_an_audio');
        static::assertEquals('That is not an audio...', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_returns_the_audio_in_a_service()
    {
        $this->botman->setContainer($this->getContainerWithDataConversationService());

        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversationService(TestDataConversation::class);
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
    public function it_repeats_invalid_location_answers_in_a_service()
    {
        $this->botman->setContainer($this->getContainerWithDataConversationService());

        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversationService(TestDataConversation::class);
        });

        $this->replyWithFakeMessage('Hello');
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('location');
        static::assertEquals('Please supply a location', $this->fakeDriver->getBotMessages()[1]->getText());

        $this->replyWithFakeMessage('not_a_location');
        static::assertEquals('Please supply a location', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_calls_custom_repeat_method_on_invalid_location_answers_in_a_service()
    {
        $this->botman->setContainer($this->getContainerWithDataConversationService());

        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversationService(TestDataConversation::class);
        });

        $this->replyWithFakeMessage('Hello');
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('custom_location_repeat');
        static::assertEquals('Please supply a location', $this->fakeDriver->getBotMessages()[1]->getText());

        $this->replyWithFakeMessage('not_a_location');
        static::assertEquals('That is not a location...', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_returns_the_location_in_a_service()
    {
        $this->botman->setContainer($this->getContainerWithDataConversationService());

        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversationService(TestDataConversation::class);
        });
        $this->replyWithFakeMessage('Hello');
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());
        $this->replyWithFakeMessage('location');
        static::assertEquals('Please supply a location', $this->fakeDriver->getBotMessages()[1]->getText());
        $message  = new IncomingMessage(Location::PATTERN, 'helloman', '#helloworld');
        $location = new Location(41.123, -12.123);
        $message->setLocation($location);
        $this->replyWithFakeMessage($message);
        static::assertEquals('41.123:-12.123', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_repeats_invalid_contact_answers_in_a_service()
    {
        $this->botman->setContainer($this->getContainerWithDataConversationService());

        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversationService(TestDataConversation::class);
        });

        $this->replyWithFakeMessage('Hello');
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('contact');
        static::assertEquals('Please supply a contact', $this->fakeDriver->getBotMessages()[1]->getText());

        $this->replyWithFakeMessage('not_a_contact');
        static::assertEquals('Please supply a contact', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_calls_custom_repeat_method_on_invalid_contact_answers_in_a_service()
    {
        $this->botman->setContainer($this->getContainerWithDataConversationService());

        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversationService(TestDataConversation::class);
        });

        $this->replyWithFakeMessage('Hello');
        static::assertEquals('What do you want to test?', $this->fakeDriver->getBotMessages()[0]->getText());

        $this->replyWithFakeMessage('custom_contact_repeat');
        static::assertEquals('Please supply a contact', $this->fakeDriver->getBotMessages()[1]->getText());

        $this->replyWithFakeMessage('not_a_contact');
        static::assertEquals('That is not a contact...', $this->fakeDriver->getBotMessages()[2]->getText());
    }

    /** @test */
    public function it_returns_the_contact_in_a_service()
    {
        $this->botman->setContainer($this->getContainerWithDataConversationService());

        $this->botman->hears('Hello', function (BotMan $bot) {
            $bot->startConversationService(TestDataConversation::class);
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
