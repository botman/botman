<?php

namespace Mpociot\SlackBot\Tests;

use Mockery as m;
use Mockery\MockInterface;
use Mpociot\SlackBot\SlackBot;
use Mpociot\SlackBot\Tests\Fixtures\TestConversation;
use PHPUnit_Framework_TestCase;

class ConversationTest extends PHPUnit_Framework_TestCase
{
    /** @var MockInterface */
    protected $commander;

    public function tearDown()
    {
        m::close();
    }

    /** @test */
    public function it_can_set_a_bot_and_store_its_token()
    {
        $bot = m::mock(SlackBot::class);
        $bot->shouldReceive('getToken')
            ->once()
            ->andReturn('Foo');
        $conversation = new TestConversation();
        $conversation->setBot($bot);

        $this->assertSame('Foo', $conversation->getToken());
    }

    /** @test */
    public function it_can_reply()
    {
        $bot = m::mock(SlackBot::class);
        $bot->shouldReceive('getToken');
        $bot->shouldReceive('respond')
            ->once()
            ->with('This is my reply');

        $conversation = new TestConversation();
        $conversation->setBot($bot);
        $conversation->reply('This is my reply');
    }

    /** @test */
    public function it_can_ask_questions()
    {
        $conversation = new TestConversation();
        $question = 'Will this test work?';
        $closure = function ($answer) {
        };

        $bot = m::mock(SlackBot::class);
        $bot->shouldReceive('getToken');
        $bot->shouldReceive('respond')
            ->once()
            ->with($question);
        $bot->shouldReceive('storeConversation')
            ->once()
            ->with($conversation, $closure);

        $conversation->setBot($bot);
        $conversation->ask($question, $closure);
    }
}
