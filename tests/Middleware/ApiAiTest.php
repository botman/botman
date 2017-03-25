<?php

namespace Mpociot\BotMan\Tests\Middleware;

use Mockery as m;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\BotManFactory;
use Mpociot\BotMan\Cache\ArrayCache;
use Mpociot\BotMan\Middleware\ApiAi;
use Mpociot\BotMan\Drivers\NullDriver;
use Symfony\Component\HttpFoundation\Response;

class ApiAiTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    /** @test */
    public function it_adds_entities_to_the_message()
    {
        $messageChannel = '1234567890';
        $messageText = 'This will be my message text!';
        $message = new Message($messageText, '', $messageChannel);

        $apiResponse = [
            'result' => [
                'fulfillment' => [
                    'speech' => 'api reply text',
                ],
                'action' => 'api action name',
                'metadata' => [
                    'intentName' => 'name of the matched intent',
                ],
                'parameters' => [
                    'param1' => 'value',
                ],
            ],
        ];
        $response = new Response(json_encode($apiResponse));

        $http = m::mock(Curl::class);
        $http->shouldReceive('post')
            ->once()
            ->with('https://api.api.ai/v1/query?v=20150910', [], [
                'query' => [$messageText],
                'sessionId' => md5($messageChannel),
                'lang' => 'en',
            ], [
                'Authorization: Bearer token',
                'Content-Type: application/json; charset=utf-8',
            ], true)
            ->andReturn($response);

        $middleware = new ApiAi('token', $http);
        $middleware->handle($message, m::mock(NullDriver::class));

        $this->assertSame([
            'apiReply' => 'api reply text',
            'apiAction' => 'api action name',
            'apiActionIncomplete' => false,
            'apiIntent' => 'name of the matched intent',
            'apiParameters' => ['param1' => 'value'],
        ], $message->getExtras());
    }

    /** @test */
    public function it_matches_messages()
    {
        $messageText = 'my_api_ai_action_name';
        $message = new Message($messageText, '', '');

        $apiResponse = [
            'result' => [
                'fulfillment' => [
                    'speech' => 'api reply text',
                ],
                'action' => 'my_api_ai_action_name',
                'metadata' => [
                    'intentName' => 'name of the matched intent',
                ],
            ],
        ];
        $response = new Response(json_encode($apiResponse));

        $http = m::mock(Curl::class);
        $http->shouldReceive('post')
            ->once()
            ->andReturn($response);

        $middleware = new ApiAi('token', $http);
        $middleware->listenForAction();
        $middleware->handle($message, m::mock(NullDriver::class));
        $this->assertTrue($middleware->isMessageMatching($message, $messageText, false));
        $this->assertFalse($middleware->isMessageMatching($message, 'some_other_action', false));
    }

    /** @test */
    public function it_matches_messages_with_regular_expressions()
    {
        $messageText = 'my_api_ai_.*';
        $message = new Message($messageText, '', '');

        $apiResponse = [
            'result' => [
                'fulfillment' => [
                    'speech' => 'api reply text',
                ],
                'action' => 'my_api_ai_action_name',
                'metadata' => [
                    'intentName' => 'name of the matched intent',
                ],
            ],
        ];
        $response = new Response(json_encode($apiResponse));

        $http = m::mock(Curl::class);
        $http->shouldReceive('post')
            ->once()
            ->andReturn($response);

        $middleware = new ApiAi('token', $http);
        $middleware->listenForAction();
        $middleware->handle($message, m::mock(NullDriver::class));
        $this->assertTrue($middleware->isMessageMatching($message, $messageText, false));
        $this->assertFalse($middleware->isMessageMatching($message, 'some_other_action', false));
    }

    /** @test */
    public function it_can_be_created()
    {
        $middleware = ApiAi::create('token');
        $this->assertInstanceOf(ApiAi::class, $middleware);
    }

    /** @test */
    public function it_only_calls_service_once_per_listen()
    {
        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode([]));

        $botman = BotManFactory::create([], new ArrayCache, $request);

        $http = m::mock(Curl::class);
        $http->shouldReceive('post')
            ->once()
            ->andReturn(new Response('[]'));

        $middleware = new ApiAi('token', $http);
        $middleware->listenForAction();

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
