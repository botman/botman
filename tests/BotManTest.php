<?php

namespace Mpociot\BotMan\Tests;

use Mockery as m;
use Mockery\MockInterface;
use Mpociot\BotMan\Answer;
use Mpociot\BotMan\BotMan;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\BotManFactory;
use Mpociot\BotMan\Cache\ArrayCache;
use Mpociot\BotMan\Tests\Fixtures\TestClass;
use Mpociot\BotMan\Tests\Fixtures\TestMiddleware;
use Mpociot\BotMan\Tests\Fixtures\TestConversation;

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

        $botman->hears('foo', function ($bot) use (&$called) {
            $called = true;
        });
        $botman->listen();
        $this->assertTrue($called);
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
    public function it_hears_in_public_channel_only()
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
        }, BotMan::PUBLIC_CHANNEL);
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
        }, BotMan::PUBLIC_CHANNEL);
        $botman->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_hears_in_private_channel_only()
    {
        $called = false;

        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'foo',
                'channel' => 'C12345',
            ],
        ]);

        $botman->hears('foo', function ($bot) use (&$called) {
            $called = true;
        }, BotMan::DIRECT_MESSAGE);
        $botman->listen();
        $this->assertFalse($called);

        $called = false;

        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'foo',
                'channel' => 'D12345',
            ],
        ]);

        $botman->hears('foo', function ($bot) use (&$called) {
            $called = true;
        }, BotMan::DIRECT_MESSAGE);
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

        $this->assertTrue($this->cache->has('conversation:UX12345-general'));

        $cached = $this->cache->get('conversation:UX12345-general');

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

        $this->assertInstanceOf(Answer::class, $GLOBALS['answer']);
        $this->assertFalse($GLOBALS['answer']->isInteractiveMessageReply());
        $this->assertSame('Hello again', $GLOBALS['answer']->getText());
        $this->assertTrue($GLOBALS['called']);
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
    public function it_tries_to_match_with_middlewares()
    {
        $called = false;
        $botman = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'foo',
            ],
        ]);
        $botman->middleware(new TestMiddleware());

        $botman->hears('successful', function ($bot) use (&$called) {
            $called = true;
        });
        $botman->listen();
        $this->assertTrue($called);
    }
}
