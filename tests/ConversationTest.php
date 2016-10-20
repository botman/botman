<?php

use Frlnc\Slack\Core\Commander;
use Frlnc\Slack\Http\CurlInteractor;
use Frlnc\Slack\Http\SlackResponseFactory;
use Mockery as m;

use Mockery\MockInterface;
use Mpociot\SlackBot\Button;
use Mpociot\SlackBot\Question;
use Mpociot\SlackBot\SlackBot;
use SuperClosure\Serializer;
use Symfony\Component\HttpFoundation\ParameterBag;

class ConversationTest extends Orchestra\Testbench\TestCase
{

    /** @var  MockInterface */
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
}

class TestConversation extends \Mpociot\SlackBot\Conversation {

    /**
     * @return mixed
     */
    public function run()
    {
        
    }
}