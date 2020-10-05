<?php

namespace BotMan\BotMan\tests;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\Tests\Fixtures\TestConversation;
use Mockery as m;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use SuperClosure\Serializer;

class ConversationTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var MockInterface */
    protected $commander;

    protected function tearDown(): void
    {
        m::close();
    }

    /** @test */
    public function it_can_reply()
    {
        $bot = m::mock(BotMan::class);
        $bot->shouldReceive('getToken');
        $bot->shouldReceive('reply')
            ->once()
            ->with('This is my reply', []);

        $conversation = new TestConversation();
        $conversation->setBot($bot);
        $conversation->say('This is my reply');
    }

    /** @test */
    public function it_can_ask_questions()
    {
        $conversation = new TestConversation();
        $question = 'Will this test work?';
        $closure = function ($answer) {
        };

        $bot = m::mock(BotMan::class);
        $bot->shouldReceive('getToken');
        $bot->shouldReceive('reply')
            ->once()
            ->with($question, []);
        $bot->shouldReceive('storeConversation')
            ->once()
            ->with($conversation, $closure, $question, []);

        $conversation->setBot($bot);
        $conversation->ask($question, $closure);
    }

    /** @test */
    public function it_can_ask_question_with_multiple_callbacks()
    {
        $conversation = new TestConversation();
        $question = 'Will this test work?';
        $closure = function ($answer) {
        };

        $serializer = m::mock(Serializer::class);
        $serializer->shouldReceive('serialize')->andReturn('serialized_closure');

        $bot = m::mock(BotMan::class);
        $bot->shouldReceive('getSerializer')->andReturn($serializer);
        $bot->shouldReceive('getToken');
        $bot->shouldReceive('reply')
            ->once()
            ->with($question, []);
        $bot->shouldReceive('storeConversation')
            ->once()
            ->with($conversation, m::type('array'), $question, []);

        $conversation->setBot($bot);
        $conversation->ask($question, [
            [
                'pattern' => 'Sure',
                'callback' => $closure,
            ],
            [
                'pattern' => 'No way',
                'callback' => $closure,
            ],
        ]);
    }

    /** @test */
    public function it_can_return_bot_instance()
    {
        $conversation = new TestConversation();
        $bot = m::mock(BotMan::class);
        $conversation->setBot($bot);
        $this->assertEquals($bot, $conversation->getBot());
    }
}
