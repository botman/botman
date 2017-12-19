<?php

namespace BotMan\BotMan\tests;

use Mockery as m;
use BotMan\BotMan\BotMan;
use Mockery\MockInterface;
use SuperClosure\Serializer;
use PHPUnit\Framework\TestCase;
use BotMan\BotMan\Tests\Fixtures\TestConversation;

class ConversationTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var MockInterface */
    protected $commander;

    public function tearDown()
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
}
