<?php

use Frlnc\Slack\Core\Commander;
use Frlnc\Slack\Http\CurlInteractor;
use Frlnc\Slack\Http\SlackResponseFactory;
use Mockery as m;

use Mockery\MockInterface;
use Mpociot\SlackBot\SlackBot;
use SuperClosure\Serializer;
use Symfony\Component\HttpFoundation\ParameterBag;

class SlackBotTest extends Orchestra\Testbench\TestCase
{

    public function tearDown()
    {
        m::close();
    }
    
    protected function getBot($responseData)
    {
        $interactor = new CurlInteractor;
        $interactor->setResponseFactory(new SlackResponseFactory);
        $request = m::mock(\Illuminate\Http\Request::class.'[json]');
        $request->shouldReceive('json')->once()->andReturn(new ParameterBag($responseData));
        return new SlackBot(new Serializer(), new Commander('', $interactor), $request);
    }

    public function testBotDoesNotHearCommands()
    {
        $called = false;

        $slackbot = $this->getBot([
            'event' => [
                'text' => 'bar'
            ]
        ]);

        $slackbot->hears('foo', function ($bot) use (&$called) {
            $called = true;
        });
        $this->assertFalse($called);
    }

    public function testBotHearsCommands()
    {
        $called = false;

        $slackbot = $this->getBot([
            'event' => [
                'text' => 'foo'
            ]
        ]);

        $slackbot->hears('foo', function ($bot) use (&$called) {
            $called = true;
        });
        $this->assertTrue($called);
    }

    public function testBotPassesItselfToClosure()
    {
        $called = false;

        $slackbot = $this->getBot([
            'event' => [
                'text' => 'foo'
            ]
        ]);

        $slackbot->hears('foo', function ($bot) use (&$called) {
            $called = true;
            $this->assertInstanceOf(SlackBot::class, $bot);
        });
        $this->assertTrue($called);
    }

    public function testBotAllowsRegularExpressions()
    {
        $called = false;

        $slackbot = $this->getBot([
            'event' => [
                'text' => 'Hi Julia'
            ]
        ]);

        $slackbot->hears('hi (.*)', function ($bot, $matches) use (&$called) {
            $called = true;
            $this->assertSame('Julia', $matches[1]);
        });
        $this->assertTrue($called);
    }

    public function testBotReturnsRegularExpressionMatches()
    {
        $called = false;

        $slackbot = $this->getBot([
            'event' => [
                'text' => 'Hi Julia'
            ]
        ]);

        $slackbot->hears('hi (.*)', function ($bot, $matches) use (&$called) {
            $called = true;
            $this->assertSame('Julia', $matches[1]);
            $this->assertSame('Julia', $bot->getMatches()[1]);
        });
        $this->assertTrue($called);
    }

    public function testBotReturnsMessage()
    {
        $slackbot = $this->getBot([
            'event' => [
                'text' => 'Hi Julia'
            ]
        ]);
        $this->assertSame('Hi Julia', $slackbot->getMessage());
    }

    public function testBotDoesNotReturnMessageFromBots()
    {
        $slackbot = $this->getBot([
            'event' => [
                'bot_id' => 'foo',
                'text' => 'Hi Julia'
            ]
        ]);
        $this->assertSame('', $slackbot->getMessage());
    }

    public function testBotDetectsBotMessages()
    {
        $slackbot = $this->getBot([
            'event' => [
                'text' => 'Hi Julia'
            ]
        ]);
        $this->assertFalse($slackbot->isBot());

        $slackbot = $this->getBot([
            'event' => [
                'bot_id' => 'foo',
                'text' => 'Hi Julia'
            ]
        ]);
        $this->assertTrue($slackbot->isBot());
    }
}