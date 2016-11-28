<?php

namespace Mpociot\SlackBot\Tests;

use Frlnc\Slack\Core\Commander;
use Frlnc\Slack\Http\CurlInteractor;
use Frlnc\Slack\Http\SlackResponseFactory;
use Mockery as m;
use Mockery\MockInterface;
use Mpociot\SlackBot\Answer;
use Mpociot\SlackBot\Button;
use Mpociot\SlackBot\Cache\ArrayCache;
use Mpociot\SlackBot\DriverManager;
use Mpociot\SlackBot\Http\Curl;
use Mpociot\SlackBot\Question;
use Mpociot\SlackBot\SlackBot;
use Mpociot\SlackBot\Tests\Fixtures\TestConversation;
use PHPUnit_Framework_TestCase;
use SuperClosure\Serializer;

class SlackBotTest extends PHPUnit_Framework_TestCase
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
        $interactor = new CurlInteractor;
        $interactor->setResponseFactory(new SlackResponseFactory);
        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        return new SlackBot(new Serializer(), $request, $this->cache, new DriverManager([], new Curl()));
    }

    protected function getBotWithInteractiveData($payload)
    {
        $interactor = new CurlInteractor;
        $interactor->setResponseFactory(new SlackResponseFactory);
        /** @var \Illuminate\Http\Request $request */
        $request = new \Illuminate\Http\Request();
        $request->replace([
            'payload' => $payload,
        ]);

        return new SlackBot(new Serializer(), $request, $this->cache, new DriverManager([], new Curl()));
    }

    /** @test */
    public function it_does_not_hear_commands()
    {
        $called = false;

        $slackbot = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'bar',
            ],
        ]);

        $slackbot->hears('foo', function ($bot) use (&$called) {
            $called = true;
        });
        $slackbot->listen();
        $this->assertFalse($called);
    }

    /** @test */
    public function it_does_not_hear_commands_and_uses_fallback()
    {
        $called = false;
        $fallbackCalled = false;

        $slackbot = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'bar',
            ],
        ]);

        $slackbot->fallback(function ($bot) use (&$fallbackCalled) {
            $fallbackCalled = true;
        });

        $slackbot->hears('foo', function ($bot) use (&$called) {
            $called = true;
        });

        $slackbot->listen();
        $this->assertFalse($called);
        $this->assertTrue($fallbackCalled);
    }

    /** @test */
    public function it_does_not_use_fallback_for_bot_replies()
    {
        $called = false;
        $fallbackCalled = false;

        $slackbot = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'bar',
                'bot_id' => 'i_am_a_bot',
            ],
        ]);

        $slackbot->fallback(function ($bot) use (&$fallbackCalled) {
            $fallbackCalled = true;
        });

        $slackbot->hears('foo', function ($bot) use (&$called) {
            $called = true;
        });

        $slackbot->listen();
        $this->assertFalse($called);
        $this->assertFalse($fallbackCalled);
    }

    /** @test */
    public function it_hears_matching_commands()
    {
        $called = false;

        $slackbot = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'foo',
            ],
        ]);

        $slackbot->hears('foo', function ($bot) use (&$called) {
            $called = true;
        });
        $slackbot->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_hears_in_public_channel_only()
    {
        $called = false;

        $slackbot = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'channel' => 'D12345',
                'text' => 'foo',
            ],
        ]);

        $slackbot->hears('foo', function ($bot) use (&$called) {
            $called = true;
        }, SlackBot::PUBLIC_CHANNEL);
        $slackbot->listen();
        $this->assertFalse($called);

        $called = false;

        $slackbot = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'channel' => 'C12345',
                'text' => 'foo',
            ],
        ]);

        $slackbot->hears('foo', function ($bot) use (&$called) {
            $called = true;
        }, SlackBot::PUBLIC_CHANNEL);
        $slackbot->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_hears_in_private_channel_only()
    {
        $called = false;

        $slackbot = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'foo',
                'channel' => 'C12345',
            ],
        ]);

        $slackbot->hears('foo', function ($bot) use (&$called) {
            $called = true;
        }, SlackBot::DIRECT_MESSAGE);
        $slackbot->listen();
        $this->assertFalse($called);


        $called = false;

        $slackbot = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'foo',
                'channel' => 'D12345',
            ],
        ]);

        $slackbot->hears('foo', function ($bot) use (&$called) {
            $called = true;
        }, SlackBot::DIRECT_MESSAGE);
        $slackbot->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_passes_itself_to_the_closure()
    {
        $called = false;

        $slackbot = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'foo',
            ],
        ]);

        $slackbot->hears('foo', function ($bot) use (&$called) {
            $called = true;
            $this->assertInstanceOf(SlackBot::class, $bot);
        });
        $slackbot->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_allows_regular_expressions()
    {
        $called = false;

        $slackbot = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'Hi Julia',
            ],
        ]);

        $slackbot->hears('hi {name}', function ($bot, $name) use (&$called) {
            $called = true;
            $this->assertSame('Julia', $name);
        });
        $slackbot->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_returns_regular_expression_matches()
    {
        $called = false;

        $slackbot = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'I am Gandalf the grey',
            ],
        ]);

        $slackbot->hears('I am {name} the {attribute}', function ($bot, $name, $attribute) use (&$called) {
            $called = true;
            $this->assertSame('Gandalf', $name);
            $this->assertSame('grey', $attribute);
        });
        $slackbot->listen();
        $this->assertTrue($called);
    }

    /** @test */
    public function it_returns_the_matches()
    {
        $called = false;

        $slackbot = $this->getBot([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'I am Gandalf',
            ],
        ]);

        $slackbot->hears('I am {name}', function ($bot, $name) use (&$called) {
            $called = true;
        });
        $slackbot->listen();
        $matches = $slackbot->getMatches();
        $this->assertSame('Gandalf', $matches['name']);
        $this->assertTrue($called);
    }

    /** @test */
    public function it_can_store_conversations()
    {
        $slackbot = $this->getBot([
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'foo',
            ],
        ]);

        $slackbot->hears('foo',function(){});
        $slackbot->listen();

        $conversation = new TestConversation();
        $slackbot->storeConversation($conversation, function ($answer) {});

        $this->assertTrue($this->cache->has('conversation:UX12345-general'));

        $cached = $this->cache->get('conversation:UX12345-general');

        $this->assertSame($conversation, $cached['conversation']);

        $this->assertTrue(is_string($cached['next']));
    }

    /** @test */
    public function it_can_start_conversations()
    {
        $slackbot = $this->getBot([
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
            ],
            'text' => 'foo'
        ]);

        $slackbot->hears('foo',function(){});
        $slackbot->listen();

        $conversation = m::mock(TestConversation::class);
        $conversation->shouldReceive('setBot')
            ->once()
            ->with($slackbot);

        $conversation->shouldReceive('run')
            ->once();

        $slackbot->startConversation($conversation);
    }

    /** @test */
    public function it_picks_up_conversations()
    {
        $GLOBALS['answer'] = '';
        $GLOBALS['called'] = false;
        $slackbot = $this->getBot([
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'Hi Julia',
            ],
        ]);

        $slackbot->hears('Hi Julia',function(){});
        $slackbot->listen();

        $conversation = new TestConversation();

        $slackbot->storeConversation($conversation, function ($answer) use (&$called) {
            $GLOBALS['answer'] = $answer;
            $GLOBALS['called'] = true;
        });

        /*
         * Now that the first message is saved, fake a reply
         */
        $slackbot = $this->getBot([
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
        $slackbot = $this->getBot([
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'Hi Julia',
            ],
        ]);

        $slackbot->hears('Hi Julia',function(){});
        $slackbot->listen();

        $conversation = new TestConversation();

        $slackbot->storeConversation($conversation, [
            [
                'pattern' => 'token_one',
                'callback' => function ($answer) use (&$called) {
                    $GLOBALS['answer'] = $answer;
                    $GLOBALS['called_foo'] = true;
                },
            ],
            [
                'pattern' => 'token_two',
                'callback' => function ($answer) use (&$called) {
                    $GLOBALS['answer'] = $answer;
                    $GLOBALS['called_bar'] = true;
                },
            ],
        ]);

        /*
         * Now that the first message is saved, fake a reply
         */
        $slackbot = $this->getBot([
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
        $slackbot = $this->getBot([
            'token' => 'foo',
            'event' => [
                'user' => 'UX12345',
                'channel' => 'general',
                'text' => 'Hi Julia',
            ],
        ]);

        $slackbot->hears('Hi Julia',function(){});
        $slackbot->listen();

        $conversation = new TestConversation();

        $slackbot->storeConversation($conversation, [
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
        $slackbot = $this->getBot([
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
}
