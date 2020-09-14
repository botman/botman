<?php

namespace BotMan\BotMan\tests\Middleware;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Cache\ArrayCache;
use BotMan\BotMan\Http\Curl;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Middleware\Wit;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class WitTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function tearDown(): void
    {
        m::close();
    }

    /** @test */
    public function it_adds_entities_to_the_message()
    {
        $messageText = 'This will be my message text!';
        $message = new IncomingMessage($messageText, '', '');

        $response = new Response(json_encode(['entities' => ['foo' => 'bar'], 'intents' => []]));

        $http = m::mock(Curl::class);
        $http->shouldReceive('get')
            ->once()
            ->with('https://api.wit.ai/message?q='.urlencode($messageText), [], [
                'Authorization: Bearer token',
            ])
            ->andReturn($response);

        $callback = function ($m) use (&$message) {
            $message = $m;
        };

        $middleware = new Wit('token', 0.5, $http);
        $middleware->received($message, $callback, m::mock(BotMan::class));

        $this->assertSame([
            'entities' => ['foo' => 'bar'],
            'intents' => []
        ], $message->getExtras());
    }

    /** @test */
    public function it_matches_intents()
    {
        $messageText = 'This will be my message text!';
        $message = new IncomingMessage($messageText, '', '');

        $response = new Response('{
          "msg_id": "eb458be1-43e0-47c0-88b2-efbc9fa3240a",
          "_text": "I am happy",
          "intents": [
            {
              "id": "123456",
              "name": "emotion",
              "confidence": 0.7343395827157483
            }
          ],
          "entities": {
            "emotion": [
              {
                "confidence": 0.9775576413827303,
                "type": "value",
                "value": "happy"
              }
            ]
          }
        }
        ');

        $http = m::mock(Curl::class);
        $http->shouldReceive('get')
            ->once()
            ->with('https://api.wit.ai/message?q='.urlencode($messageText), [], [
                'Authorization: Bearer token',
            ])
            ->andReturn($response);

        $callback = function ($m) use (&$message) {
            $message = $m;
        };

        $middleware = new Wit('token', 0.5, $http);
        $middleware->received($message, $callback, m::mock(BotMan::class));
        $this->assertTrue($middleware->matching($message, 'emotion', false));
    }

    /** @test */
    public function it_does_not_match_intents_with_lower_confidence()
    {
        $messageText = 'This will be my message text!';
        $message = new IncomingMessage($messageText, '', '');

        $response = new Response('{
          "msg_id": "eb458be1-43e0-47c0-88b2-efbc9fa3240a",
          "_text": "I am happy",
          "intents": [
            {
              "id": "123456",
              "name": "emotion",
              "confidence": 0.343395827157483,
            }
          ],
          "entities": {
            "emotion": [
              {
                "confidence": 0.9775576413827303,
                "type": "value",
                "value": "happy"
              }
            ]
          }
        }
        ');

        $http = m::mock(Curl::class);
        $http->shouldReceive('get')
            ->once()
            ->with('https://api.wit.ai/message?q='.urlencode($messageText), [], [
                'Authorization: Bearer token',
            ])
            ->andReturn($response);

        $callback = function ($m) use ($message) {
            $message = $m;
        };

        $middleware = new Wit('token', 0.5, $http);
        $middleware->received($message, $callback, m::mock(BotMan::class));
        $this->assertFalse($middleware->matching($message, 'emotion', false));
    }

    /** @test */
    public function it_can_be_created()
    {
        $middleware = Wit::create('token');
        $this->assertInstanceOf(Wit::class, $middleware);
    }

    /** @test */
    public function it_only_calls_service_once_per_listen()
    {
        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode([]));

        $botman = BotManFactory::create([], new ArrayCache, $request);

        $http = m::mock(Curl::class);
        $http->shouldReceive('get')
            ->once()
            ->andReturn(new Response('[]'));

        $middleware = new Wit('token', 0.5, $http);

        $botman->middleware->received($middleware);

        $botman->hears('one', function ($bot) {
        })->middleware($middleware);
        $botman->hears('two', function ($bot) {
        })->middleware($middleware);
        $botman->group(['middleware' => $middleware], function ($botman) use (&$called) {
            $botman->hears('one', function ($bot) {
            });
            $botman->hears('two', function ($bot) {
            });
        });

        $botman->listen();
    }
}
