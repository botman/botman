<?php

namespace BotMan\BotMan\tests\Middleware;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Cache\ArrayCache;
use BotMan\BotMan\Http\Curl;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Middleware\ApiAi;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiAiTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function tearDown(): void
    {
        m::close();
    }

    /** @test */
    public function it_adds_entities_to_the_message()
    {
        $messageChannel = '1234567890';
        $messageText = 'This will be my message text!';
        $message = new IncomingMessage($messageText, '', $messageChannel);

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
                'sessionId' => md5($message->getConversationIdentifier()),
                'lang' => 'en',
            ], [
                'Authorization: Bearer token',
                'Content-Type: application/json; charset=utf-8',
            ], true)
            ->andReturn($response);

        $callback = function ($m) use (&$message) {
            $message = $m;
        };

        $middleware = new ApiAi('token', $http);
        $middleware->received($message, $callback, m::mock(BotMan::class));

        $this->assertSame([
            'apiReply' => 'api reply text',
            'apiAction' => 'api action name',
            'apiActionIncomplete' => false,
            'apiIntent' => 'name of the matched intent',
            'apiParameters' => ['param1' => 'value'],
            'apiResponseMessages' => [],
            'apiTextResponses' => [],
            'apiCustomPayloadResponses' => [],
            'apiContexts' => [],
        ], $message->getExtras());
    }

    /** @test */
    public function it_matches_messages()
    {
        $messageText = 'my_api_ai_action_name';
        $message = new IncomingMessage($messageText, '', '');

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

        $callback = function ($m) use (&$message) {
            $message = $m;
        };

        $middleware = new ApiAi('token', $http);
        $middleware->listenForAction();
        $middleware->received($message, $callback, m::mock(BotMan::class));
        $this->assertTrue($middleware->matching($message, $messageText, false));
        $this->assertFalse($middleware->matching($message, 'some_other_action', false));
    }

    /** @test */
    public function it_matches_messages_with_regular_expressions()
    {
        $messageText = 'my_api_ai_.*';
        $message = new IncomingMessage($messageText, '', '');

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

        $callback = function ($m) use (&$message) {
            $message = $m;
        };

        $middleware = new ApiAi('token', $http);
        $middleware->listenForAction();
        $middleware->received($message, $callback, m::mock(BotMan::class));
        $this->assertTrue($middleware->matching($message, $messageText, false));
        $this->assertFalse($middleware->matching($message, 'some_other_action', false));
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
