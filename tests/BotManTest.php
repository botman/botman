<?php

namespace BotMan\BotMan\tests;

use Mockery as m;
use BotMan\BotMan\BotMan;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use BotMan\BotMan\BotManFactory;
use Illuminate\Support\Collection;
use BotMan\BotMan\Cache\ArrayCache;
use BotMan\BotMan\Drivers\NullDriver;
use Psr\Container\ContainerInterface;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Drivers\Tests\FakeDriver;
use BotMan\BotMan\Interfaces\UserInterface;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Tests\Fixtures\TestClass;
use BotMan\BotMan\Tests\Fixtures\TestDriver;
use BotMan\BotMan\Messages\Attachments\Audio;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Attachments\Video;
use Psr\Container\NotFoundExceptionInterface;
use BotMan\BotMan\Tests\Fixtures\TestFallback;
use BotMan\BotMan\Middleware\MiddlewareManager;
use BotMan\BotMan\Messages\Attachments\Location;
use BotMan\BotMan\Tests\Fixtures\TestMiddleware;
use BotMan\BotMan\Exceptions\Base\BotManException;
use BotMan\BotMan\Tests\Fixtures\TestConversation;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Tests\Fixtures\Middleware\Matching;
use BotMan\BotMan\Tests\Fixtures\TestMatchMiddleware;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Tests\Fixtures\TestAdditionalDriver;
use BotMan\BotMan\Tests\Fixtures\TestNoMatchMiddleware;
use BotMan\BotMan\Exceptions\Core\BadMethodCallException;
use BotMan\BotMan\Exceptions\Core\UnexpectedValueException;

/**
 * Class BotManTest.
 */
class BotManTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var MockInterface */
    protected $commander;

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
    public function it_does_not_hear_commands()
    {
        $called = false;

        $botman = $this->getBot([
            'user' => 'U0X12345',
            'text' => 'bar',
        ]);

        $botman->hears('foo', function ($bot) use (&$called) {
            $called = true;
        });
        $botman->listen();
        $this->assertFalse($called);
    }

    /** @test */
    public function it_does_not_hear_commands_and_uses_fallback()
    {
        $called = false;
        $fallbackCalled = false;

        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'bar',
            ],
        ]);

        $botman->fallback(function ($bot) use (&$fallbackCalled) {
            $fallbackCalled = true;
        });

        $botman->hears('foo', function ($bot) use (&$called) {
            $called = true;
        });

        $botman->listen();
        $this->assertFalse($called);
        $this->assertTrue($fallbackCalled);
    }

    /** @test */
    public function it_does_not_use_fallback_for_conversation_replies()
    {
        $GLOBALS['answer'] = '';
        $GLOBALS['called'] = false;
        $fallbackCalled = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hi Julia',
        ]);

        $botman->hears('Hi Julia', function ($botman) {
            $conversation = new TestConversation();

            $botman->storeConversation($conversation, function ($answer) use (&$called) {
                $GLOBALS['answer'] = $answer;
                $GLOBALS['called'] = true;
            });
        });
        $botman->listen();

        /*
         * Now that the first message is saved, fake a reply
         */
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hello again',
        ]);

        $botman->fallback(function ($bot) use (&$fallbackCalled) {
            $fallbackCalled = true;
        });
        $botman->listen();

        $this->assertInstanceOf(Answer::class, $GLOBALS['answer']);
        $this->assertFalse($GLOBALS['answer']->isInteractiveMessageReply());
        $this->assertSame('Hello again', $GLOBALS['answer']->getText());
        $this->assertTrue($GLOBALS['called']);

        $this->assertFalse($fallbackCalled);
    }

    /** @test */
    public function it_does_not_use_fallback_for_bot_replies()
    {
        $called = false;
        $fallbackCalled = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hello again',
            'is_from_bot' => true,
        ]);

        $botman->fallback(function ($bot) use (&$fallbackCalled) {
            $fallbackCalled = true;
        });

        $botman->hears('foo', function ($bot) use (&$called) {
            $called = true;
        });

        $botman->listen();
        $this->assertFalse($called);
        $this->assertFalse($fallbackCalled);
    }

    /** @test */
    public function it_hears_matching_commands()
    {
        $called = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Foo',
        ]);

        $botman->hears('Foo', function ($bot) use (&$called) {
            $called = true;
        });
        $botman->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_can_used_instance_commands()
    {
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Foo',
        ]);

        $command = new TestClass($botman);

        $botman->hears('Foo', [$command, 'foo']);
        $botman->listen();

        $this->assertTrue($command::$called);
    }

    /** @test */
    public function it_does_not_hear_bot_commands()
    {
        $called = false;

        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'foo',
                'bot_id' => '123',
            ],
        ]);

        $botman->hears('Foo', function ($bot) use (&$called) {
            $called = true;
        });
        $botman->listen();
        $this->assertFalse($called);
    }

    /** @test */
    public function it_calls_fallback_without_closures()
    {
        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'foo',
            ],
        ]);

        $called = false;
        TestFallback::$called = false;

        $botman->fallback(TestFallback::class.'@foo');

        $botman->hears('bar', function ($bot) use (&$called) {
            $called = true;
        });

        $botman->listen();
        $this->assertFalse($called);
        $this->assertTrue(TestFallback::$called);
    }

    /** @test */
    public function it_hears_matching_commands_without_closures()
    {
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Foo',
        ]);
        TestClass::$called = false;
        $botman->hears('foo', TestClass::class.'@foo');
        $botman->listen();
        $this->assertTrue(TestClass::$called);
    }

    /** @test */
    public function it_hears_matching_commands_with_container()
    {
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Foo',
        ]);
        TestClass::$called = false;

        /** @var ContainerInterface|m\Mock $containerMock */
        $containerMock = m::mock(ContainerInterface::class);
        $containerMock->shouldReceive('get')
            ->with(TestClass::class)
            ->once()
            ->andReturn(new TestClass($botman));

        $botman->setContainer($containerMock);

        $botman->hears('foo', TestClass::class.'@foo');
        $botman->listen();
        $this->assertTrue(TestClass::$called);
    }

    /** @test */
    public function it_throws_not_found_exception_when_command_is_not_registered_in_container()
    {
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Foo',
        ]);
        TestClass::$called = false;

        /** @var ContainerInterface|m\Mock $containerMock */
        $containerMock = m::mock(ContainerInterface::class);
        $exceptionMock = new class() extends \Exception implements NotFoundExceptionInterface {
        };
        $containerMock->shouldReceive('get')->once()->andThrow($exceptionMock);

        $botman->setContainer($containerMock);

        $botman->hears('foo', TestClass::class.'@foo');

        $this->expectException(NotFoundExceptionInterface::class);

        $botman->listen();
        $this->assertFalse(TestClass::$called);
    }

    /** @test */
    public function it_uses_invoke_method()
    {
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Foo',
        ]);
        TestClass::$called = false;
        $botman->hears('foo', TestClass::class);
        $botman->listen();
        $this->assertTrue(TestClass::$called);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid hears action: [stdClass]');

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Foo',
        ]);
        $botman->hears('foo', \stdClass::class);
        $botman->listen();
    }

    /** @test */
    public function it_does_not_hears_matching_commands_in_text()
    {
        $called = false;

        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'this',
            ],
        ]);

        $botman->hears('hi', function ($bot) use (&$called) {
            $called = true;
        });
        $botman->listen();
        $this->assertFalse($called);
    }

    /** @test */
    public function it_hears_for_specific_drivers_only()
    {
        $called = false;
        DriverManager::loadDriver(TestAdditionalDriver::class);

        $botman = $this->getBot([
            'additional' => true,
            'event' => [
                'user' => 'U0X12345',
                'channel' => 'D12345',
                'text' => 'foo',
            ],
        ]);

        $botman->hears('foo', function ($bot) use (&$called) {
            $called = true;
        })->driver(TestAdditionalDriver::class);
        $botman->listen();
        $this->assertFalse($called);

        $called = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Foo',
        ]);

        $botman->hears('foo', function ($bot) use (&$called) {
            $called = true;
        })->driver(FakeDriver::class);
        $botman->listen();
        $this->assertTrue($called);

        $called = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Foo',
        ]);

        $botman->hears('foo', function ($bot) use (&$called) {
            $called = true;
        })->driver([TestAdditionalDriver::class, FakeDriver::class]);
        $botman->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_passes_itself_to_the_closure()
    {
        $called = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Foo',
        ]);

        $botman->hears('foo', function ($bot) use (&$called) {
            $called = true;
            $this->assertInstanceOf(BotMan::class, $bot);
        });
        $botman->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_can_retrieve_the_user()
    {
        $called = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Foo',
        ]);

        $botman->hears('foo', function ($bot) use (&$called) {
            $called = true;
            $this->assertSame('UX12345', $bot->getMessage()->getSender());
        });
        $botman->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_allows_regular_expressions()
    {
        $called = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hi Julia',
        ]);

        $botman->hears('hi {name}', function ($bot, $name) use (&$called) {
            $called = true;
            $this->assertSame('Julia', $name);
        });
        $botman->listen();
        $this->assertTrue($called);
        $called = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => '/hi Julia',
        ]);

        $botman->hears('/hi {name}', function ($bot, $name) use (&$called) {
            $called = true;
            $this->assertSame('Julia', $name);
        });
        $botman->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_allows_multiline_expressions()
    {
        $called = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Start new order by link:'.PHP_EOL.'/new_order_{hash}',
        ]);

        $botman->hears('.*/new_order_(.*)', function ($bot, $name) use (&$called) {
            $called = true;
            $this->assertSame('{hash}', $name);
        });
        $botman->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_allows_complex_regular_expressions()
    {
        $called = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'deploy site to dev',
        ]);

        $botman->hears('deploy\s+([a-zA-Z]*)(?:\s*to\s*)?([a-zA-Z]*)?', function ($bot, $project, $env) use (&$called) {
            $called = true;
            $this->assertSame('site', $project);
            $this->assertSame('dev', $env);
        });
        $botman->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_allows_unicode_regular_expressions()
    {
        $called = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Какая погода в Минске',
        ]);

        $botman->hears('какая\s+погода\s+в\s+([а-яa-z0-9]+)\s*', function ($bot, $city) use (&$called) {
            $called = true;
            $this->assertSame('Минске', $city);
        });
        $botman->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_allows_regular_expressions_with_range_quantifier()
    {
        $called = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'look at order #123456789',
        ]);

        $botman->hears('.*?#(\d{8,9})\b.*', function ($bot, $orderId) use (&$called) {
            $called = true;
            $this->assertSame('123456789', $orderId);
        });
        $botman->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_returns_regular_expression_matches()
    {
        $called = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'I am Gandalf the grey',
        ]);

        $botman->hears('I am {name} the {attribute}', function ($bot, $name, $attribute) use (&$called) {
            $called = true;
            $this->assertSame('Gandalf', $name);
            $this->assertSame('grey', $attribute);
        });
        $botman->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_returns_the_matches()
    {
        $called = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'I am Gandalf',
        ]);

        $botman->hears('I am {name}', function ($bot, $name) use (&$called) {
            $called = true;
        });
        $botman->listen();
        $matches = $botman->getMatches();
        $this->assertSame('Gandalf', $matches['name']);
        $this->assertTrue($called);
    }

    /** @test */
    public function it_returns_regular_expression_combined_matches()
    {
        $called = false;

        $botmans = [
            $this->getBot([
                'sender' => 'UX12345',
                'recipient' => 'general',
                'message' => 'I am Gandalf the grey',
            ]),
            $this->getBot([
                'sender' => 'UX12345',
                'recipient' => 'general',
                'message' => 'You are Gandalf a grey',
            ]),
        ];

        foreach ($botmans as $botman) {
            $botman->hears('(I am|You are) {name} (the|a) {attribute}', function ($bot, $name, $attribute) use (&$called) {
                $called = true;

                $this->assertSame('Gandalf', $name);
                $this->assertSame('grey', $attribute);
            });
        }

        $botman->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_returns_the_combined_matches()
    {
        $called = false;

        $botmans = [
            $this->getBot([
                'sender' => 'UX12345',
                'recipient' => 'general',
                'message' => 'I am Gandalf',
            ]),
            $this->getBot([
                'sender' => 'UX12345',
                'recipient' => 'general',
                'message' => 'You are Gandalf',
            ]),
        ];

        foreach ($botmans as $botman) {
            $botman->hears('(I am|You are) {name}', function ($bot, $name) use (&$called) {
                $called = true;

                $this->assertSame('Gandalf', $name);
            });
        }

        $botman->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_can_store_conversations()
    {
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'foo',
        ]);

        $conversation = new TestConversation();

        $botman->hears('foo', function ($botman) use ($conversation) {
            $botman->storeConversation($conversation, function ($answer) {
            });
        });
        $botman->listen();

        $cacheKey = 'conversation-'.sha1('UX12345').'-'.sha1('general');
        $this->assertTrue($this->cache->has($cacheKey));

        $cached = $this->cache->get($cacheKey);

        $this->assertSame($conversation, $cached['conversation']);

        $this->assertTrue(is_string($cached['next']));
    }

    /** @test */
    public function it_can_start_conversations()
    {
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'foo',
        ]);

        $botman->hears('foo', function () {
        });
        $botman->listen();

        $conversation = m::mock(TestConversation::class);
        $conversation->shouldReceive('setBot')
            ->once()
            ->with($botman);

        $conversation->shouldReceive('run')
            ->once();

        $botman->startConversation($conversation);
    }

    /** @test */
    public function it_picks_up_conversations()
    {
        $GLOBALS['answer'] = '';
        $GLOBALS['called'] = false;
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hi Julia',
        ]);

        $botman->hears('Hi Julia', function ($botman) {
            $conversation = new TestConversation();

            $botman->storeConversation($conversation, function (Answer $answer) use (&$called) {
                $GLOBALS['answer'] = $answer;
                $GLOBALS['called'] = true;
            });
        });
        $botman->listen();

        /*
         * Now that the first message is saved, fake a reply
         */
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hello again',
        ]);
        $botman->listen();

        $this->assertInstanceOf(Answer::class, $GLOBALS['answer']);
        $this->assertFalse($GLOBALS['answer']->isInteractiveMessageReply());
        $this->assertSame('Hello again', $GLOBALS['answer']->getText());
        $this->assertTrue($GLOBALS['called']);
    }

    /** @test */
    public function it_does_not_pick_up_conversations_with_bots()
    {
        $GLOBALS['answer'] = '';
        $GLOBALS['called'] = false;
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hi Julia',
        ]);

        $botman->hears('Hi Julia', function () {
        });
        $botman->listen();

        $conversation = new TestConversation();

        $botman->storeConversation($conversation, function (Answer $answer) use (&$called) {
            $GLOBALS['answer'] = $answer;
            $GLOBALS['called'] = true;
        });

        /*
         * Now that the first message is saved, fake a reply
         */
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hello again',
            'is_from_bot' => true,
        ]);
        $botman->listen();

        $this->assertFalse($GLOBALS['called']);
    }

    /** @test */
    public function it_picks_up_conversations_using_this()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('called conversation');

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hi Julia',
        ]);

        $botman->hears('Hi Julia', function ($botman) {
            $conversation = new TestConversation();

            $botman->storeConversation($conversation, function (Answer $answer) use (&$called) {
                $this->_throwException('called conversation');
            });
        });
        $botman->listen();

        /*
         * Now that the first message is saved, fake a reply
         */
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hello again',
        ]);
        $botman->listen();
    }

    /** @test */
    public function it_picks_up_conversations_with_multiple_callbacks()
    {
        $GLOBALS['answer'] = '';
        $GLOBALS['called_foo'] = false;
        $GLOBALS['called_bar'] = false;
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hi Julia',
        ]);

        $botman->hears('Hi Julia', function ($botman) {
            $conversation = new TestConversation();

            $botman->storeConversation($conversation, [
                [
                    'pattern' => 'token_one',
                    'callback' => function (Answer $answer) use (&$called) {
                        $GLOBALS['answer'] = $answer;
                        $GLOBALS['called_foo'] = true;
                    },
                ],
                [
                    'pattern' => 'token_two',
                    'callback' => function (Answer $answer) use (&$called) {
                        $GLOBALS['answer'] = $answer;
                        $GLOBALS['called_bar'] = true;
                    },
                ],
            ]);
        });
        $botman->listen();

        /*
         * Now that the first message is saved, fake a reply
         */
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'token_one',
        ]);
        $botman->listen();

        $this->assertInstanceOf(Answer::class, $GLOBALS['answer']);
        $this->assertFalse($GLOBALS['answer']->isInteractiveMessageReply());
        $this->assertSame('token_one', $GLOBALS['answer']->getText());
        $this->assertTrue($GLOBALS['called_foo']);
        $this->assertFalse($GLOBALS['called_bar']);
    }

    /** @test */
    public function it_can_use_parameters_in_callback_patterns()
    {
        $GLOBALS['answer'] = '';
        $GLOBALS['called'] = false;
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hi Julia',
        ]);

        $botman->hears('Hi Julia', function ($botman) {
            $conversation = new TestConversation();
            $botman->storeConversation($conversation, [
                [
                    'pattern' => '([0]?[0-2][0-3]|[0-9])',
                    'callback' => function (Answer $answer, $number) use (&$called) {
                        $GLOBALS['answer'] = $answer;
                        $GLOBALS['called'] = true;
                    },
                ],
            ]);
        });
        $botman->listen();

        /*
         * Now that the first message is saved, fake a reply
         */
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => '023',
        ]);
        $botman->listen();

        $this->assertInstanceOf(Answer::class, $GLOBALS['answer']);
        $this->assertFalse($GLOBALS['answer']->isInteractiveMessageReply());
        $this->assertSame('023', $GLOBALS['answer']->getText());
        $this->assertTrue($GLOBALS['called']);
    }

    /** @test */
    public function it_picks_up_conversations_with_patterns()
    {
        $GLOBALS['answer'] = '';
        $GLOBALS['called'] = false;
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hi Julia',
        ]);

        $botman->hears('Hi Julia', function ($botman) {
            $conversation = new TestConversation();
            $botman->storeConversation($conversation, [
                [
                    'pattern' => 'Call me {name}',
                    'callback' => function ($answer, $name) use (&$called) {
                        $GLOBALS['answer'] = $name;
                        $GLOBALS['called'] = true;
                    },
                ],
            ]);
        });
        $botman->listen();

        /*
         * Now that the first message is saved, fake a reply
         */
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'call me Heisenberg',
        ]);
        $botman->listen();

        $this->assertSame('Heisenberg', $GLOBALS['answer']);
        $this->assertTrue($GLOBALS['called']);
    }

    /** @test */
    public function it_applies_received_middlewares()
    {
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'foo',
        ]);
        $botman->middleware->received(new TestMiddleware());

        $botman->hears('foo', function ($bot) {
            $this->assertSame([
                'driver_name' => 'Fake',
                'test' => 'successful',
            ], $bot->getMessage()->getExtras());
        });
        $botman->listen();
    }

    /** @test */
    public function it_can_group_commands_by_middleware()
    {
        $called = false;

        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'successful',
            ],
        ]);
        $botman->middleware->received(new TestMiddleware());
        $botman->group(['middleware' => new TestMiddleware()], function ($botman) use (&$called) {
            $botman->hears('successful', function ($bot) use (&$called) {
                $called = true;
                $this->assertSame([
                    'driver_name' => 'Fake',
                    'test' => 'successful',
                ], $bot->getMessage()->getExtras());
            });
        });

        $botman->hears('foo', function ($bot) {
            $this->assertSame([], $bot->getMessage()->getExtras());
        });
        $botman->listen();

        $this->assertTrue($called);
    }

    /** @test */
    public function it_can_chain_multiple_group_commands()
    {
        $calledAdditionalDriverAndMiddleware = false;
        $calledAfterNestedGroup = false;
        $calledFakeDriverAndMiddleware = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'bar',
        ]);

        $botman->middleware->received(new TestMiddleware());

        $botman->group(['driver' => TestAdditionalDriver::class], function ($botman) use (&$calledAdditionalDriverAndMiddleware) {
            $botman->group(['middleware' => new TestMiddleware()], function ($botman) use (&$calledAdditionalDriverAndMiddleware) {
                $botman->hears('successful', function ($bot) use (&$calledAdditionalDriverAndMiddleware) {
                    $calledAdditionalDriverAndMiddleware = true;
                });
            });
        });

        $botman->group(['driver' => FakeDriver::class], function ($botman) use (&$calledFakeDriverAndMiddleware, &$calledAfterNestedGroup) {
            $botman->group(['middleware' => new TestMiddleware()], function ($botman) use (&$calledFakeDriverAndMiddleware) {
                $botman->hears('successful', function ($bot) use (&$calledFakeDriverAndMiddleware) {
                    $calledFakeDriverAndMiddleware = true;
                });
            });
            $botman->hears('after_nested_group', function ($bot) use (&$calledAfterNestedGroup) {
                $calledAfterNestedGroup = true;
            });
        });

        $botman->listen();

        $this->assertFalse($calledAdditionalDriverAndMiddleware);
        $this->assertTrue($calledFakeDriverAndMiddleware);

        $calledFakeDriverAndMiddleware = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'after_nested_group',
        ]);

        $botman->group(['driver' => FakeDriver::class], function ($botman) use (&$calledFakeDriverAndMiddleware, &$calledAfterNestedGroup) {
            $botman->group(['middleware' => new TestNoMatchMiddleware()], function ($botman) use (&$calledFakeDriverAndMiddleware) {
                $botman->hears('successful', function ($bot) use (&$calledFakeDriverAndMiddleware) {
                    $calledFakeDriverAndMiddleware = true;
                });
            });
            $botman->hears('after_nested_group', function ($bot) use (&$calledAfterNestedGroup) {
                $calledAfterNestedGroup = true;
            });
        });

        $botman->listen();

        $this->assertFalse($calledFakeDriverAndMiddleware);
        $this->assertTrue($calledAfterNestedGroup);
    }

    /** @test */
    public function it_can_match_grouped_middleware_commands()
    {
        $called = false;

        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'bar',
            ],
        ]);

        $botman->group(['middleware' => new TestNoMatchMiddleware()], function ($botman) use (&$called) {
            $botman->hears('bar', function ($bot) use (&$called) {
                $called = true;
            });
        });

        $botman->listen();

        $this->assertFalse($called);
    }

    /** @test */
    public function it_can_group_commands_by_driver()
    {
        $calledSlack = false;
        $calledAdditional = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'bar',
        ]);

        $botman->group(['driver' => TestAdditionalDriver::class], function ($botman) use (&$calledTelegram) {
            $botman->hears('bar', function ($bot) use (&$calledTelegram) {
                $calledAdditional = true;
            });
        });

        $botman->group(['driver' => FakeDriver::class], function ($botman) use (&$calledSlack) {
            $botman->hears('bar', function ($bot) use (&$calledSlack) {
                $calledSlack = true;
            });
        });

        $botman->listen();

        $this->assertFalse($calledAdditional);
        $this->assertTrue($calledSlack);
    }

    /** @test */
    public function it_only_listens_for_specific_recipients_from_a_list_of_specific_recipients()
    {
        $called_one = false;
        $called_two = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'C12345',
            'message' => 'foo',
        ]);

        $botman->hears('foo', function ($bot) use (&$called_one) {
            $called_one = true;
        })->recipient(['C12345', 'C12346']);

        $botman->hears('foo', function ($bot) use (&$called_two) {
            $called_two = true;
        })->recipient(['C12346', 'C12347']);

        $botman->listen();

        $this->assertTrue($called_one);
        $this->assertFalse($called_two);
    }

    /** @test */
    public function it_only_listens_for_specific_recipients_from_a_list_of_specific_recipients_in_group()
    {
        $called_one = false;
        $called_two = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'C12345',
            'message' => 'foo',
        ]);

        $botman->group(['recipient' => ['C12345', 'C12346']], function ($botman) use (&$called_one) {
            $botman->hears('foo', function ($bot) use (&$called_one) {
                $called_one = true;
            });
        });

        $botman->group(['recipient' => ['C12346', 'C12347']], function ($botman) use (&$called_two) {
            $botman->hears('foo', function ($bot) use (&$called_two) {
                $called_two = true;
            });
        });

        $botman->listen();

        $this->assertTrue($called_one);
        $this->assertFalse($called_two);
    }

    /** @test */
    public function it_only_listens_for_specific_recipients()
    {
        $called_one = false;
        $called_two = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'C12345',
            'message' => 'foo',
        ]);

        $botman->hears('foo', function ($bot) use (&$called_one) {
            $called_one = true;
        })->recipient('C12345');

        $botman->hears('foo', function ($bot) use (&$called_two) {
            $called_two = true;
        })->recipient('C123456');

        $botman->listen();

        $this->assertTrue($called_one);
        $this->assertFalse($called_two);
    }

    /** @test */
    public function it_only_listens_on_specific_recipients_in_group()
    {
        $called_one = false;
        $called_two = false;
        $called_group = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'C12345',
            'message' => 'foo',
        ]);

        $botman->group(['recipient' => 'C12345'], function ($botman) use (&$called_one) {
            $botman->hears('foo', function ($bot) use (&$called_one) {
                $called_one = true;
            });
        });

        $botman->group(['recipient' => 'C123456'], function ($botman) use (&$called_two) {
            $botman->hears('foo', function ($bot) use (&$called_two) {
                $called_two = true;
            });
        });

        $botman->listen();

        $this->assertTrue($called_one);
        $this->assertFalse($called_two);
    }

    /** @test */
    public function it_applies_multiple_middlewares()
    {
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'foo',
        ]);
        $botman->middleware->received(new TestMiddleware(), new TestMiddleware());

        $botman->hears('foo', function ($bot) {
            $this->assertSame([
                'driver_name' => 'Fake',
                'test' => 'successful',
            ], $bot->getMessage()->getExtras());
        });
        $botman->listen();
    }

    /** @test */
    public function it_tries_to_match_with_middlewares()
    {
        $called_one = false;
        $called_two = false;
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'C12345',
            'message' => 'open the pod bay doors',
        ]);
        $botman->middleware->heard(new TestMatchMiddleware());

        $botman->hears('open the {doorType} doors', function ($bot, $doorType) use (&$called_one) {
            $called_one = true;
            $this->assertSame('pod bay', $doorType);
        });

        $botman->hears('keyword', function ($bot) use (&$called_two) {
            $called_two = true;
        });

        $botman->listen();
        $this->assertTrue($called_one);
        $this->assertFalse($called_two);
    }

    /** @test */
    public function it_tries_to_match_with_command_specific_middlewares()
    {
        $called_one = false;
        $called_two = false;
        $called_three = false;
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'C12345',
            'message' => 'open the pod bay doors',
        ]);

        $botman->hears('open the {doorType} doors', function ($bot, $doorType) use (&$called_one) {
            $called_one = true;
            $this->assertSame('pod bay', $doorType);
        })->middleware(new TestMatchMiddleware());

        $botman->hears('keyword', function ($bot) use (&$called_two) {
            $called_two = true;
        })->middleware(new TestMatchMiddleware());

        $botman->hears('keyword', function ($bot) use (&$called_three) {
            $called_three = true;
        })->middleware(new Matching());

        $botman->listen();
        $this->assertTrue($called_one);
        $this->assertFalse($called_two);
        $this->assertTrue($called_three);
    }

    /** @test */
    public function it_does_not_hear_when_middleware_does_not_match()
    {
        $called = false;
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'C12345',
            'message' => 'open the pod bay doors',
        ]);
        $botman->middleware->matching(new TestNoMatchMiddleware());

        $botman->hears('open the {doorType} doors', function ($bot, $doorType) use (&$called) {
            $called = true;
        });
        $botman->listen();
        $this->assertFalse($called);
    }

    /** @test */
    public function it_can_reply_a_message()
    {
        $botman = $this->getBot([]);
        $driver = m::mock(FakeDriver::class)->makePartial();
        $botman->setDriver($driver);

        $botman->reply('foo', []);
        $this->assertCount(1, $botman->getDriver()->getBotMessages());
        $this->assertSame('foo', $botman->getDriver()->getBotMessages()[0]->getText());
    }

    /** @test */
    public function it_can_reply_a_random_message()
    {
        $randomMessages = ['foo', 'bar', 'baz'];
        $botman = $this->getBot([]);
        $driver = m::mock(FakeDriver::class)->makePartial();
        $botman->setDriver($driver);
        $botman->randomReply($randomMessages, []);

        $message = $botman->getDriver()->getBotMessages()[0]->getText();
        $this->assertContains($message, $randomMessages);
    }

    /**
     * @test
     */
    public function it_can_originate_inline_questions()
    {
        $driver = m::mock(FakeDriver::class)->makePartial();
        $_SERVER['called'] = false;
        $_SERVER['expectedAnswer'] = null;

        $botman = $this->getBot([]);
        $botman->setDriver($driver);

        $botman->ask('Some question', function ($answer) {
            $_SERVER['expectedAnswer'] = $answer->getText();
            $_SERVER['called'] = true;
        }, [], 'channel', $driver);

        $message = $botman->getDriver()->getBotMessages()[0]->getText();
        $this->assertSame('Some question', $message);

        $botman->getDriver()->messages[] = new IncomingMessage('My answer', 'channel', '');
        $botman->listen();

        $this->assertTrue($_SERVER['called']);
        $this->assertSame('My answer', $_SERVER['expectedAnswer']);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_can_originate_messages_with_given_driver()
    {
        $driver = m::mock(NullDriver::class);
        $driver->shouldReceive('buildServicePayload')
            ->once()
            ->withArgs(function ($message, $match, $arguments) {
                return $message->getText() === 'foo' && $match->getSender() === 'channel' && $arguments === [];
            });
        $driver->shouldReceive('sendPayload')
            ->once();

        $mock = \Mockery::mock('alias:BotMan\BotMan\Drivers\DriverManager');
        $mock->shouldReceive('loadFromName')
            ->once()
            ->with(FakeDriver::class, [])
            ->andReturn($driver);

        $botman = m::mock(BotMan::class)->makePartial();
        $botman->middleware = m::mock(MiddlewareManager::class)->makePartial();
        $botman->say('foo', 'channel', FakeDriver::class);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_can_originate_messages_to_multiple_users()
    {
        $driver = m::mock(NullDriver::class);
        $driver->shouldReceive('buildServicePayload')
            ->withArgs(function ($message, $match, $arguments) {
                return $message->getText() === 'foo' && $match->getSender() === 'channel1' && $arguments === [];
            });
        $driver->shouldReceive('buildServicePayload')
            ->withArgs(function ($message, $match, $arguments) {
                return $message->getText() === 'foo' && $match->getSender() === 'channel2' && $arguments === [];
            });

        $driver->shouldReceive('sendPayload')
            ->twice();

        $mock = \Mockery::mock('alias:BotMan\BotMan\Drivers\DriverManager');
        $mock->shouldReceive('loadFromName')
            ->once()
            ->with(FakeDriver::class, [])
            ->andReturn($driver);

        $botman = m::mock(BotMan::class)->makePartial();
        $botman->middleware = m::mock(MiddlewareManager::class)->makePartial();
        $botman->say('foo', ['channel1', 'channel2'], FakeDriver::class);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_can_originate_messages_with_additional_parameters()
    {
        $additionalParameters = [
            'foo' => 'bar',
        ];

        $driver = m::mock(NullDriver::class);
        $driver->shouldReceive('buildServicePayload')
            ->once()
            ->withArgs(function ($message, $match, $arguments) use ($additionalParameters) {
                return $message->getText() === 'foo' && $match->getSender() === '1234567890' && $arguments === $additionalParameters;
            });
        $driver->shouldReceive('sendPayload')
            ->once();

        $mock = \Mockery::mock('alias:BotMan\BotMan\Drivers\DriverManager');
        $mock->shouldReceive('loadFromName')
            ->once()
            ->with('NullDriver', [])
            ->andReturn($driver);

        $botman = m::mock(BotMan::class)->makePartial();
        $botman->middleware = m::mock(MiddlewareManager::class)->makePartial();
        $botman->say('foo', '1234567890', 'NullDriver', $additionalParameters);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_can_originate_messages_with_configured_driver()
    {
        $driver = m::mock(NullDriver::class);
        $driver->shouldReceive('buildServicePayload')
            ->once()
            ->withArgs(function ($message, $match, $arguments) {
                return $message->getText() === 'foo' && $match->getSender() === 'channel' && $arguments === [];
            });
        $driver->shouldReceive('sendPayload')
            ->once();

        $botman = m::mock(BotMan::class)->makePartial();
        $botman->setDriver($driver);
        $botman->middleware = m::mock(MiddlewareManager::class)->makePartial();
        $botman->say('foo', 'channel');
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_throws_an_exception_with_no_drivers()
    {
        $botman = m::mock(BotMan::class)->makePartial();
        $botman->middleware = m::mock(MiddlewareManager::class)->makePartial();
        $this->expectException(BotManException::class);
        $botman->say('foo', 'channel');
    }

    /** @test */
    public function it_can_use_custom_drivers()
    {
        $driver = m::mock(TestDriver::class);
        $driver->shouldReceive('buildServicePayload')
            ->once();
        $driver->shouldReceive('sendPayload')
            ->once();

        DriverManager::loadDriver(TestDriver::class);

        $botman = m::mock(BotMan::class)->makePartial();
        $botman->middleware = m::mock(MiddlewareManager::class)->makePartial();
        $botman->setDriver($driver);

        $botman->reply('foo', []);

        DriverManager::unloadDriver(TestDriver::class);
    }

    /** @test */
    public function it_passes_unknown_methods_to_the_driver()
    {
        $driver = m::mock(TestDriver::class);
        $driver->shouldReceive('dummyMethod')
            ->once()
            ->with('bar', 'baz', m::type(IncomingMessage::class), m::type(BotMan::class));

        DriverManager::loadDriver(TestDriver::class);

        $botman = $this->getBot('');
        $botman->setDriver($driver);

        $botman->dummyMethod('bar', 'baz');

        DriverManager::unloadDriver(TestDriver::class);
    }

    /** @test */
    public function it_can_load_drivers_from_name()
    {
        $botman = $this->getBot('');

        $this->assertInstanceOf(FakeDriver::class, $botman->getDriver());
    }

    /** @test */
    public function it_retrieves_the_user()
    {
        $botman = $this->getBot('');
        $this->assertInstanceOf(UserInterface::class, $botman->getUser());
    }

    /** @test */
    public function it_can_repeat_a_question()
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

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'C12345',
            'message' => 'Hi Julia',
        ]);

        $botman->setDriver($driver);

        $botman->hears('Hi Julia', function ($bot) {
            $bot->startConversation(new TestConversation());
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
    public function it_can_repeat_a_modified_question()
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

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'C12345',
            'message' => 'Hi Julia',
        ]);

        $botman->setDriver($driver);

        $botman->hears('Hi Julia', function ($bot) {
            $bot->startConversation(new TestConversation());
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
    public function it_checks_that_all_middleware_match()
    {
        $called_one = false;
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'C12345',
            'message' => 'open the pod bay doors',
        ]);
        $botman->middleware->matching(new TestMatchMiddleware());
        $botman->middleware->matching(new TestNoMatchMiddleware());

        $botman->hears('open the {doorType} doors', function ($bot, $doorType) use (&$called_one) {
            $called_one = true;
        });

        $botman->listen();
        $this->assertFalse($called_one);
    }

    /** @test */
    public function it_can_skip_a_running_conversation()
    {
        $called = false;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hi Julia',
        ]);

        $botman->hears('Hi Julia', function ($bot) {
            $bot->startConversation(new TestConversation());
        });
        $botman->listen();

        /*
         * Now that the first message is saved, fake a reply.
         * This should get skipped!
         */
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'skip_keyword',
        ]);

        $botman->hears('skip_keyword', function ($bot) use (&$called) {
            $called = true;
        });

        $botman->listen();

        $this->assertTrue($called);

        $cacheKey = 'conversation-'.sha1('UX12345').'-'.sha1('general');
        $this->assertTrue($this->cache->has($cacheKey));

        /*
         * This should now get picked up the usual way.
         */
        $called = false;
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'repeat',
        ]);

        $botman->hears('repeat', function ($bot) use (&$called) {
            $called = true;
        });

        $botman->listen();

        $this->assertFalse($called);
    }

    /** @test */
    public function it_can_skip_a_running_conversation_fluently()
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

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hi Julia',
        ]);

        $botman->setDriver($driver);

        $botman->hears('Hi Julia', function ($bot) {
            $bot->startConversation(new TestConversation());
        });
        $botman->listen();

        /*
         * Now that the first message is saved, fake a reply.
         * This should get skipped!
         */
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'fluent_skip_keyword',
        ]);

        $botman->hears('fluent_skip_keyword', function ($bot) use (&$called) {
            $called = true;
        })->skipsConversation();

        $botman->listen();

        $this->assertTrue($called);

        $cacheKey = 'conversation-'.sha1('UX12345').'-'.sha1('general');
        $this->assertTrue($this->cache->has($cacheKey));

        /*
         * This should now get picked up the usual way.
         */
        $called = false;
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'repeat',
        ]);

        $botman->hears('repeat', function ($bot) use (&$called) {
            $called = true;
        });

        $botman->listen();

        $this->assertFalse($called);
    }

    /** @test */
    public function it_can_skip_a_running_conversation_with_group_attribute()
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

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hi Julia',
        ]);

        $botman->setDriver($driver);

        $botman->hears('Hi Julia', function ($bot) {
            $bot->startConversation(new TestConversation());
        });
        $botman->listen();

        /*
         * Now that the first message is saved, fake a reply.
         * This should get skipped!
         */
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'group_skip_keyword',
        ]);

        $botman->group(['skip_conversation' => true], function ($bot) use (&$called) {
            $bot->hears('group_skip_keyword', function ($bot) use (&$called) {
                $called = true;
            });
        });

        $botman->listen();

        $this->assertTrue($called);

        $cacheKey = 'conversation-'.sha1('UX12345').'-'.sha1('general');
        $this->assertTrue($this->cache->has($cacheKey));

        /*
         * This should now get picked up the usual way.
         */
        $called = false;
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'repeat',
        ]);

        $botman->hears('repeat', function ($bot) use (&$called) {
            $called = true;
        });

        $botman->listen();

        $this->assertFalse($called);
    }

    /** @test */
    public function it_can_stop_a_running_conversation_fluently()
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

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hi Julia',
        ]);

        $botman->setDriver($driver);

        $botman->hears('Hi Julia', function ($bot) {
            $bot->startConversation(new TestConversation());
        });
        $botman->listen();

        /*
         * Now that the first message is saved, fake a reply.
         * This should get skipped!
         */
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'fluent_stop_keyword',
        ]);

        $botman->hears('fluent_stop_keyword', function ($bot) use (&$called) {
            $called = true;
        })->stopsConversation();
        $botman->listen();

        $this->assertTrue($called);

        // Conversation should be removed from cache
        $cacheKey = 'conversation-'.sha1('UX12345').'-'.sha1('general');
        $this->assertFalse($this->cache->has($cacheKey));

        /*
         * This should now get picked up the usual way.
         */
        $called = false;
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'repeat',
        ]);

        $botman->hears('repeat', function ($bot) use (&$called) {
            $called = true;
        });

        $botman->listen();

        $this->assertTrue($called);
    }

    /** @test */
    public function it_can_stop_a_running_conversation_with_group_attribute()
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

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hi Julia',
        ]);

        $botman->setDriver($driver);

        $botman->hears('Hi Julia', function ($bot) {
            $bot->startConversation(new TestConversation());
        });
        $botman->listen();

        /*
         * Now that the first message is saved, fake a reply.
         * This should get skipped!
         */
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'group_skip_keyword',
        ]);

        $botman->group(['stop_conversation' => true], function ($bot) use (&$called) {
            $bot->hears('group_skip_keyword', function ($bot) use (&$called) {
                $called = true;
            });
        });
        $botman->listen();

        $this->assertTrue($called);

        // Conversation should be removed from cache
        $cacheKey = 'conversation-'.sha1('UX12345').'-'.sha1('general');
        $this->assertFalse($this->cache->has($cacheKey));

        /*
         * This should now get picked up the usual way.
         */
        $called = false;
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'repeat',
        ]);

        $botman->hears('repeat', function ($bot) use (&$called) {
            $called = true;
        });

        $botman->listen();

        $this->assertTrue($called);
    }

    /** @test */
    public function it_can_stop_a_running_conversation()
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

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hi Julia',
        ]);

        $botman->setDriver($driver);

        $botman->hears('Hi Julia', function ($bot) {
            $bot->startConversation(new TestConversation());
        });
        $botman->listen();

        /*
         * Now that the first message is saved, fake a reply.
         * This should get skipped!
         */
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'stop_keyword',
        ]);

        $botman->hears('stop_keyword', function ($bot) use (&$called) {
            $called = true;
        });
        $botman->listen();

        $this->assertTrue($called);

        // Conversation should be removed from cache
        $cacheKey = 'conversation-'.sha1('UX12345').'-'.sha1('general');
        $this->assertFalse($this->cache->has($cacheKey));

        /*
         * This should now get picked up the usual way.
         */
        $called = false;
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'repeat',
        ]);

        $botman->hears('repeat', function ($bot) use (&$called) {
            $called = true;
        });

        $botman->listen();

        $this->assertTrue($called);
    }

    /** @test */
    public function it_can_create_conversations_on_the_fly()
    {
        $GLOBALS['answer'] = null;
        $GLOBALS['conversation'] = null;
        $GLOBALS['called'] = false;
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Hi Julia',
        ]);

        $botman->hears('Hi Julia', function ($bot) {
            $bot->ask('How are you doing?', function ($answer, $conversation) {
                $GLOBALS['called'] = true;
                $GLOBALS['answer'] = $answer;
                $GLOBALS['conversation'] = $conversation;
            });
        });
        $botman->listen();
        /*
         * Now that the first message is saved, fake a reply
         */
        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Great!',
        ]);
        $botman->listen();

        $this->assertTrue($GLOBALS['called']);
        $this->assertInstanceOf(Answer::class, $GLOBALS['answer']);
        $this->assertInstanceOf(Conversation::class, $GLOBALS['conversation']);
        $this->assertFalse($GLOBALS['answer']->isInteractiveMessageReply());
        $this->assertSame('Great!', $GLOBALS['answer']->getText());
    }

    /** @test */
    public function it_does_not_allow_sendRequest_method()
    {
        $botman = $this->getBot([]);
        $botman->setDriver(new FakeDriver());
        $this->expectException(BadMethodCallException::class);
        $botman->sendRequest('foo', []);
    }

    /** @test */
    public function it_returns_images_as_second_argument()
    {
        $called = false;

        $message = new IncomingMessage(Image::PATTERN, '', '');
        $message->setImages([
            'http://foo.com/bar.png',
        ]);

        $botman = $this->getBot([]);

        $driver = m::mock(FakeDriver::class)->makePartial();
        $driver->shouldReceive('getMessages')
            ->andReturn([
                $message,
            ]);

        $botman->setDriver($driver);

        $botman->receivesImages(function ($bot, $data) use (&$called) {
            $called = true;
            $this->assertCount(1, $data);
            $this->assertSame(['http://foo.com/bar.png'], $data);
        });
        $botman->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_returns_videos_as_second_argument()
    {
        $called = false;

        $message = new IncomingMessage(Video::PATTERN, '', '');
        $message->setVideos([
            'http://foo.com/bar.png',
        ]);

        $botman = $this->getBot([]);

        $driver = m::mock(FakeDriver::class)->makePartial();
        $driver->shouldReceive('getMessages')
            ->andReturn([
                $message,
            ]);

        $botman->setDriver($driver);

        $botman->receivesVideos(function ($bot, $data) use (&$called) {
            $called = true;
            $this->assertCount(1, $data);
            $this->assertSame(['http://foo.com/bar.png'], $data);
        });
        $botman->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_returns_audio_as_second_argument()
    {
        $called = false;

        $message = new IncomingMessage(Audio::PATTERN, '', '');
        $message->setAudio([
            'http://foo.com/bar.png',
        ]);

        $botman = $this->getBot([]);

        $driver = m::mock(FakeDriver::class)->makePartial();
        $driver->shouldReceive('getMessages')
            ->andReturn([
                $message,
            ]);

        $botman->setDriver($driver);

        $botman->receivesAudio(function ($bot, $data) use (&$called) {
            $called = true;
            $this->assertCount(1, $data);
            $this->assertSame(['http://foo.com/bar.png'], $data);
        });
        $botman->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_returns_location_as_second_argument()
    {
        $called = false;
        $lat = 41.123;
        $lng = -12.123;

        $location = new Location($lat, $lng);

        $message = new IncomingMessage(Location::PATTERN, '', '');
        $message->setLocation($location);

        $botman = $this->getBot([]);

        $driver = m::mock(FakeDriver::class)->makePartial();
        $driver->shouldReceive('getMessages')
            ->andReturn([
                $message,
            ]);

        $botman->setDriver($driver);

        $botman->receivesLocation(function ($bot, $data) use (&$called, $location) {
            $called = true;
            $this->assertInstanceOf(Location::class, $data);
            $this->assertSame($location, $data);
        });
        $botman->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_can_cache_the_user()
    {
        $user = null;
        $driverName = null;

        $botman = $this->getBot([
            'sender' => 'UX12345',
            'recipient' => 'general',
            'message' => 'Foo',
        ]);

        $botman->hears('Foo', function ($bot) use (&$user, &$driverName) {
            $user = $bot->getUser();
            $driverName = $bot->getDriver()->getName();
        });
        $botman->listen();
        $this->assertEquals($user, $this->cache->get('user_'.$driverName.'_UX12345'));
    }
}
