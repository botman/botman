<?php

namespace Mpociot\BotMan\Tests\Middleware;

use Mockery as m;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Middleware\Wit;
use Mpociot\BotMan\Drivers\NullDriver;
use Symfony\Component\HttpFoundation\Response;

class WitTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_adds_entities_to_the_message()
    {
        $messageText = 'This will be my message text!';
        $message = new Message($messageText, '', '');

        $response = new Response(json_encode(['entities' => ['foo' => 'bar']]));

        $http = m::mock(Curl::class);
        $http->shouldReceive('post')
            ->once()
            ->with('https://api.wit.ai/message?q='.urlencode($messageText), [], [], [
                'Authorization: Bearer token',
            ])
            ->andReturn($response);

        $middleware = new Wit('token', 0.5, $http);
        $middleware->handle($message, m::mock(NullDriver::class));

        $this->assertSame([
            'entities' => ['foo' => 'bar'],
        ], $message->getExtras());
    }

    /** @test */
    public function it_matches_intents()
    {
        $messageText = 'This will be my message text!';
        $message = new Message($messageText, '', '');

        $response = new Response('{
          "msg_id": "eb458be1-43e0-47c0-88b2-efbc9fa3240a",
          "_text": "I am happy",
          "entities": {
            "emotion": [
              {
                "confidence": 0.9775576413827303,
                "type": "value",
                "value": "happy"
              }
            ],
            "intent": [
              {
                "confidence": 0.7343395827157483,
                "value": "emotion"
              }
            ]
          }
        }
        ');

        $http = m::mock(Curl::class);
        $http->shouldReceive('post')
            ->once()
            ->with('https://api.wit.ai/message?q='.urlencode($messageText), [], [], [
                'Authorization: Bearer token',
            ])
            ->andReturn($response);

        $middleware = new Wit('token', 0.5, $http);
        $middleware->handle($message, m::mock(NullDriver::class));
        $this->assertTrue($middleware->isMessageMatching($message, 'emotion', false));
    }

    /** @test */
    public function it_does_not_match_intents_with_lower_confidence()
    {
        $messageText = 'This will be my message text!';
        $message = new Message($messageText, '', '');

        $response = new Response('{
          "msg_id": "eb458be1-43e0-47c0-88b2-efbc9fa3240a",
          "_text": "I am happy",
          "entities": {
            "emotion": [
              {
                "confidence": 0.9775576413827303,
                "type": "value",
                "value": "happy"
              }
            ],
            "intent": [
              {
                "confidence": 0.343395827157483,
                "value": "emotion"
              }
            ]
          }
        }
        ');

        $http = m::mock(Curl::class);
        $http->shouldReceive('post')
            ->once()
            ->with('https://api.wit.ai/message?q='.urlencode($messageText), [], [], [
                'Authorization: Bearer token',
            ])
            ->andReturn($response);

        $middleware = new Wit('token', 0.5, $http);
        $middleware->handle($message, m::mock(NullDriver::class));
        $this->assertFalse($middleware->isMessageMatching($message, 'emotion', false));
    }

    /** @test */
    public function it_can_be_created()
    {
        $middleware = Wit::create('token');
        $this->assertInstanceOf(Wit::class, $middleware);
    }
}
