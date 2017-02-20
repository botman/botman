<?php

namespace Mpociot\BotMan\Tests;

use Mockery as m;
use Mockery\MockInterface;
use Mpociot\BotMan\Answer;
use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Message;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Conversation;
use Mpociot\BotMan\BotManFactory;
use Mpociot\BotMan\DriverManager;
use Mpociot\BotMan\Cache\ArrayCache;
use Mpociot\BotMan\Drivers\NullDriver;
use Mpociot\BotMan\Drivers\SlackDriver;
use Mpociot\BotMan\Drivers\FacebookDriver;
use Mpociot\BotMan\Drivers\TelegramDriver;
use Mpociot\BotMan\Interfaces\UserInterface;
use Mpociot\BotMan\Tests\Fixtures\TestClass;
use Mpociot\BotMan\Tests\Fixtures\TestDriver;
use Mpociot\BotMan\Tests\Fixtures\TestMiddleware;
use Mpociot\BotMan\Tests\Fixtures\TestConversation;
use Mpociot\BotMan\Tests\Fixtures\TestMatchMiddleware;
use Mpociot\BotMan\Tests\Fixtures\TestNoMatchMiddleware;

/**
 * Class BotManTest.
 */
class BotManTest extends PHPUnit_Framework_TestCase
{
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

    protected function getBot($responseData)
    {
        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        return BotManFactory::create([], $this->cache, $request);
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
            'event' => [
                'user' => 'U0X12345',
                'text' => 'bar',
            ],
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
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'Hi Julia',
            ],
        ]);

        $botman->hears('Hi Julia', function () {
        });
        $botman->listen();

        $conversation = new TestConversation();

        $botman->storeConversation($conversation, function ($answer) use (&$called) {
            $GLOBALS['answer'] = $answer;
            $GLOBALS['called'] = true;
        });

        /*
         * Now that the first message is saved, fake a reply
         */
        $botman = $this->getBot([
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'Hello again',
            ],
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
            'event' => [
                'user' => 'U0X12345',
                'text' => 'bar',
                'bot_id' => 'i_am_a_bot',
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
        $this->assertFalse($fallbackCalled);
    }

    /** @test */
    public function it_hears_matching_commands()
    {
        $called = false;

        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'foo',
            ],
        ]);

        $botman->hears('Foo', function ($bot) use (&$called) {
            $called = true;
        });
        $botman->listen();
        $this->assertTrue($called);
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
    public function it_hears_matching_commands_without_closures()
    {
        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'foo',
            ],
        ]);
        TestClass::$called = false;
        $botman->hears('foo', TestClass::class.'@foo');
        $botman->listen();
        $this->assertTrue(TestClass::$called);
    }

    /** @test */
    public function it_uses_invoke_method()
    {
        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'foo',
            ],
        ]);
        TestClass::$called = false;
        $botman->hears('foo', TestClass::class);
        $botman->listen();
        $this->assertTrue(TestClass::$called);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid hears action: [stdClass]');

        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'foo',
            ],
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

        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'channel' => 'D12345',
                'text' => 'foo',
            ],
        ]);

        $botman->hears('foo', function ($bot) use (&$called) {
            $called = true;
        })->driver(TelegramDriver::DRIVER_NAME);
        $botman->listen();
        $this->assertFalse($called);

        $called = false;

        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'channel' => 'C12345',
                'text' => 'foo',
            ],
        ]);

        $botman->hears('foo', function ($bot) use (&$called) {
            $called = true;
        })->driver(SlackDriver::class);
        $botman->listen();
        $this->assertTrue($called);

        $called = false;

        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'channel' => 'C12345',
                'text' => 'foo',
            ],
        ]);

        $botman->hears('foo', function ($bot) use (&$called) {
            $called = true;
        })->driver([TelegramDriver::DRIVER_NAME, SlackDriver::DRIVER_NAME]);
        $botman->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_passes_itself_to_the_closure()
    {
        $called = false;

        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'foo',
            ],
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
            'event' => [
                'user' => 'U0X12345',
                'text' => 'foo',
            ],
        ]);

        $botman->hears('foo', function ($bot) use (&$called) {
            $called = true;
            $this->assertSame('U0X12345', $bot->getMessage()->getUser());
        });
        $botman->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_allows_regular_expressions()
    {
        $called = false;

        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'Hi Julia',
            ],
        ]);

        $botman->hears('hi {name}', function ($bot, $name) use (&$called) {
            $called = true;
            $this->assertSame('Julia', $name);
        });
        $botman->listen();
        $this->assertTrue($called);
        $called = false;

        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => '/Hi Julia',
            ],
        ]);

        $botman->hears('/hi {name}', function ($bot, $name) use (&$called) {
            $called = true;
            $this->assertSame('Julia', $name);
        });
        $botman->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_allows_complex_regular_expressions()
    {
        $called = false;

        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'deploy site to dev',
            ],
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
    public function it_allows_regular_expressions_with_range_quantifier()
    {
        $called = false;

        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'look at order #123456789',
            ],
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
            'event' => [
                'user' => 'U0X12345',
                'text' => 'I am Gandalf the grey',
            ],
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
            'event' => [
                'user' => 'U0X12345',
                'text' => 'I am Gandalf',
            ],
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
    public function it_can_store_conversations()
    {
        $botman = $this->getBot([
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'foo',
            ],
        ]);

        $botman->hears('foo', function () {
        });
        $botman->listen();

        $conversation = new TestConversation();
        $botman->storeConversation($conversation, function ($answer) {
        });

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
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
            ],
            'text' => 'foo',
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
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'Hi Julia',
            ],
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
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'Hello again',
            ],
        ]);
        $botman->listen();

        $this->assertInstanceOf(Answer::class, $GLOBALS['answer']);
        $this->assertFalse($GLOBALS['answer']->isInteractiveMessageReply());
        $this->assertSame('Hello again', $GLOBALS['answer']->getText());
        $this->assertTrue($GLOBALS['called']);
    }

    /** @test */
    public function it_picks_up_conversations_using_this()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('called conversation');

        $botman = $this->getBot([
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'Hi Julia',
            ],
        ]);

        $botman->hears('Hi Julia', function () {
        });
        $botman->listen();

        $conversation = new TestConversation();

        $botman->storeConversation($conversation, function (Answer $answer) use (&$called) {
            $this->_throwException('called conversation');
        });

        /*
         * Now that the first message is saved, fake a reply
         */
        $botman = $this->getBot([
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'Hello again',
            ],
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
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'Hi Julia',
            ],
        ]);

        $botman->hears('Hi Julia', function () {
        });
        $botman->listen();

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

        /*
         * Now that the first message is saved, fake a reply
         */
        $botman = $this->getBot([
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'token_one',
            ],
        ]);
        $botman->listen();

        $this->assertInstanceOf(Answer::class, $GLOBALS['answer']);
        $this->assertFalse($GLOBALS['answer']->isInteractiveMessageReply());
        $this->assertSame('token_one', $GLOBALS['answer']->getText());
        $this->assertTrue($GLOBALS['called_foo']);
        $this->assertFalse($GLOBALS['called_bar']);
    }

    /** @test */
    public function it_picks_up_conversations_with_patterns()
    {
        $GLOBALS['answer'] = '';
        $GLOBALS['called'] = false;
        $botman = $this->getBot([
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'Hi Julia',
            ],
        ]);

        $botman->hears('Hi Julia', function () {
        });
        $botman->listen();

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

        /*
         * Now that the first message is saved, fake a reply
         */
        $botman = $this->getBot([
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'call me Heisenberg',
            ],
        ]);
        $botman->listen();

        $this->assertSame('Heisenberg', $GLOBALS['answer']);
        $this->assertTrue($GLOBALS['called']);
    }

    /** @test */
    public function it_applies_middlewares()
    {
        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'foo',
            ],
        ]);
        $botman->middleware(new TestMiddleware());

        $botman->hears('foo', function ($bot) {
            $this->assertSame([
                'driver_name' => 'Slack',
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

        $botman->group(['middleware' => new TestMiddleware()], function ($botman) use (&$called) {
            $botman->hears('successful', function ($bot) use (&$called) {
                $called = true;
                $this->assertSame([
                    'driver_name' => 'Slack',
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
        $calledTelegram = false;

        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'bar',
            ],
        ]);

        $botman->group(['driver' => TelegramDriver::DRIVER_NAME], function ($botman) use (&$calledTelegram) {
            $botman->hears('bar', function ($bot) use (&$calledTelegram) {
                $calledTelegram = true;
            });
        });

        $botman->group(['driver' => SlackDriver::DRIVER_NAME], function ($botman) use (&$calledSlack) {
            $botman->hears('bar', function ($bot) use (&$calledSlack) {
                $calledSlack = true;
            });
        });

        $botman->listen();

        $this->assertFalse($calledTelegram);
        $this->assertTrue($calledSlack);
    }

    /** @test */
    public function it_applies_middleware_only_on_specific_commands()
    {
        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'foo',
            ],
        ]);

        $botman->hears('foo', function ($bot) {
            $this->assertSame([], $bot->getMessage()->getExtras());
        });

        $botman->hears('foo', function ($bot) {
            $this->assertSame([
                'driver_name' => 'Slack',
                'test' => 'successful',
            ], $bot->getMessage()->getExtras());
        })->middleware(new TestMiddleware());

        $botman->listen();
    }

    /** @test */
    public function it_only_listens_on_specific_channels()
    {
        $called_one = false;
        $called_two = false;
        $called_group = false;

        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'channel' => 'C12345',
                'text' => 'foo',
            ],
        ]);

        $botman->hears('foo', function ($bot) use (&$called_one) {
            $called_one = true;
        })->channel('C12345');

        $botman->hears('foo', function ($bot) use (&$called_two) {
            $called_two = true;
        })->channel('C123456');

        $botman->listen();

        $this->assertTrue($called_one);
        $this->assertFalse($called_two);
    }

    /** @test */
    public function it_only_listens_on_specific_channels_in_group()
    {
        $called_one = false;
        $called_two = false;
        $called_group = false;

        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'channel' => 'C12345',
                'text' => 'foo',
            ],
        ]);

        $botman->group(['channel' => 'C12345'], function ($botman) use (&$called_one) {
            $botman->hears('foo', function ($bot) use (&$called_one) {
                $called_one = true;
            });
        });

        $botman->group(['channel' => 'C123456'], function ($botman) use (&$called_two) {
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
            'event' => [
                'user' => 'U0X12345',
                'text' => 'foo',
            ],
        ]);
        $botman->middleware([new TestMiddleware()]);

        $botman->hears('foo', function ($bot) {
            $this->assertSame([
                'driver_name' => 'Slack',
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
            'event' => [
                'user' => 'U0X12345',
                'text' => 'open the pod bay doors',
            ],
        ]);
        $botman->middleware(new TestMatchMiddleware());

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
        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'open the pod bay doors',
            ],
        ]);

        $botman->hears('open the {doorType} doors', function ($bot, $doorType) use (&$called_one) {
            $called_one = true;
            $this->assertSame('pod bay', $doorType);
        })->middleware(new TestMatchMiddleware());

        $botman->hears('keyword', function ($bot) use (&$called_two) {
            $called_two = true;
        })->middleware(new TestMatchMiddleware());

        $botman->listen();
        $this->assertTrue($called_one);
        $this->assertFalse($called_two);
    }

    /** @test */
    public function it_does_not_hear_when_middleware_does_not_match()
    {
        $called = false;
        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'open the pod bay doors',
            ],
        ]);
        $botman->middleware(new TestNoMatchMiddleware());

        $botman->hears('open the {doorType} doors', function ($bot, $doorType) use (&$called) {
            $called = true;
        });
        $botman->listen();
        $this->assertFalse($called);
    }

    /** @test */
    public function it_can_reply_a_message()
    {
        $driver = m::mock(NullDriver::class);
        $driver->shouldReceive('reply')
            ->once()
            ->withArgs([
                'foo',
                null,
                [],
            ]);

        $botman = m::mock(BotMan::class)->makePartial();
        $botman->shouldReceive('getDriver')
            ->once()
            ->andReturn($driver);

        $botman->reply('foo', []);
    }

    /** @test */
    public function it_can_reply_a_random_message()
    {
        $driver = m::mock(NullDriver::class);
        $driver->shouldReceive('reply')
            ->once()
            ->with(m::anyOf('foo', 'bar', 'baz'),
                null,
                []
            );

        $botman = m::mock(BotMan::class)->makePartial();
        $botman->shouldReceive('getDriver')
            ->once()
            ->andReturn($driver);

        $botman->randomReply(['foo', 'bar', 'baz'], []);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_can_originate_messages_with_given_driver()
    {
        $driver = m::mock(NullDriver::class);
        $driver->shouldReceive('reply')
            ->once()
            ->withArgs(function ($message, $match, $arguments) {
                return $message === 'foo' && $match->getChannel() === 'channel' && $arguments === [];
            });

        $mock = \Mockery::mock('alias:Mpociot\BotMan\DriverManager');
        $mock->shouldReceive('loadFromName')
            ->once()
            ->with('Slack', [])
            ->andReturn($driver);

        $botman = m::mock(BotMan::class)->makePartial();
        $botman->say('foo', 'channel', SlackDriver::DRIVER_NAME);
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
        $driver->shouldReceive('reply')
            ->once()
            ->withArgs(function ($message, $match, $arguments) use ($additionalParameters) {
                return $message === 'foo' && $match->getChannel() === '1234567890' && $arguments === $additionalParameters;
            });

        $mock = \Mockery::mock('alias:Mpociot\BotMan\DriverManager');
        $mock->shouldReceive('loadFromName')
            ->once()
            ->with('Facebook', [])
            ->andReturn($driver);

        $botman = m::mock(BotMan::class)->makePartial();
        $botman->say('foo', '1234567890', FacebookDriver::DRIVER_NAME, $additionalParameters);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_can_originate_messages_with_configured_drivers()
    {
        $driver = m::mock(NullDriver::class);
        $driver->shouldReceive('reply')
            ->once()
            ->withArgs(function ($message, $match, $arguments) {
                return $message === 'foo' && $match->getChannel() === 'channel' && $arguments === [];
            });

        $mock = \Mockery::mock('alias:Mpociot\BotMan\DriverManager');
        $mock->shouldReceive('getConfiguredDrivers')
            ->andReturn([$driver]);

        $botman = m::mock(BotMan::class)->makePartial();
        $botman->say('foo', 'channel');
    }

    /** @test */
    public function it_can_use_custom_drivers()
    {
        $driver = m::mock(TestDriver::class);
        $driver->shouldReceive('reply')
            ->once();

        DriverManager::loadDriver(TestDriver::class);

        $botman = m::mock(BotMan::class)->makePartial();
        $botman->setDriver($driver);

        $botman->reply('foo', []);
    }

    /** @test */
    public function it_passes_unknown_methods_to_the_driver()
    {
        $driver = m::mock(TestDriver::class);
        $driver->shouldReceive('dummyMethod')
            ->once()
            ->with('bar', 'baz', m::type(Message::class));

        DriverManager::loadDriver(TestDriver::class);

        $botman = $this->getBot('');
        $botman->setDriver($driver);

        $botman->dummyMethod('bar', 'baz');
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
            ->andReturn([new Message('Hi Julia', 'UX12345', 'general')]);

        $driver->shouldReceive('reply')
            ->once()
            ->with('This is a test question', m::type(Message::class), []);

        $botman = $this->getBot([
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'Hi Julia',
            ],
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
            ->andReturn([new Message('repeat', 'UX12345', 'general')]);

        $driver->shouldReceive('reply')
            ->once()
            ->with('This is a test question', m::type(Message::class), []);

        $botman->setDriver($driver);

        $botman->listen();
    }

    /** @test */
    public function it_can_repeat_a_modified_question()
    {
        $driver = m::mock(NullDriver::class)->makePartial();

        $driver->shouldReceive('getMessages')
            ->andReturn([new Message('Hi Julia', 'UX12345', 'general')]);

        $driver->shouldReceive('reply')
            ->once()
            ->with('This is a test question', m::type(Message::class), []);

        $botman = $this->getBot([
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'Hi Julia',
            ],
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
            ->andReturn([new Message('repeat_modified', 'UX12345', 'general')]);

        $driver->shouldReceive('reply')
            ->once()
            ->with('This is a modified test question', m::type(Message::class), []);

        $botman->setDriver($driver);

        $botman->listen();
    }

    /** @test */
    public function it_checks_that_all_middleware_match()
    {
        $called_one = false;
        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'open the pod bay doors',
            ],
        ]);
        $botman->middleware([new TestMatchMiddleware(), new TestNoMatchMiddleware()]);

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
        $driver = m::mock(NullDriver::class)->makePartial();

        $driver->shouldReceive('getMessages')
            ->andReturn([new Message('Hi Julia', 'UX12345', 'general')]);

        $driver->shouldReceive('reply')
            ->once()
            ->with('This is a test question', m::type(Message::class), []);

        $botman = $this->getBot([
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'Hi Julia',
            ],
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
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'skip_keyword',
            ],
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
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'repeat',
            ],
        ]);

        $botman->hears('repeat', function ($bot) use (&$called) {
            $called = true;
        });

        $botman->listen();

        $this->assertFalse($called);
    }

    /** @test */
    public function it_can_stop_a_running_conversation()
    {
        $called = false;
        $driver = m::mock(NullDriver::class)->makePartial();

        $driver->shouldReceive('getMessages')
            ->andReturn([new Message('Hi Julia', 'UX12345', 'general')]);

        $driver->shouldReceive('reply')
            ->once()
            ->with('This is a test question', m::type(Message::class), []);

        $botman = $this->getBot([
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'Hi Julia',
            ],
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
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'stop_keyword',
            ],
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
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'repeat',
            ],
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
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'Hi Julia',
            ],
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
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'Great!',
            ],
        ]);
        $botman->listen();

        $this->assertTrue($GLOBALS['called']);
        $this->assertInstanceOf(Answer::class, $GLOBALS['answer']);
        $this->assertInstanceOf(Conversation::class, $GLOBALS['conversation']);
        $this->assertFalse($GLOBALS['answer']->isInteractiveMessageReply());
        $this->assertSame('Great!', $GLOBALS['answer']->getText());
    }
}
