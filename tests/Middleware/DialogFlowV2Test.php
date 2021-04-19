<?php

namespace BotMan\BotMan\tests\Middleware;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Cache\ArrayCache;
use BotMan\BotMan\Http\Curl;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Middleware\ApiAi;
use BotMan\BotMan\Middleware\DialogFlowV2;
use BotMan\BotMan\Middleware\DialogFlowV2\Client;
use Google\Cloud\Dialogflow\V2\Context;
use Google\Cloud\Dialogflow\V2\DetectIntentResponse;
use Google\Cloud\Dialogflow\V2\Intent;
use Google\Cloud\Dialogflow\V2\Intent\Message;
use Google\Cloud\Dialogflow\V2\Intent\Parameter;
use Google\Cloud\Dialogflow\V2\Intent\TrainingPhrase;
use Google\Cloud\Dialogflow\V2\QueryResult;
use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\MapField;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Struct;
use Google\Protobuf\Value;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class DialogFlowV2Test extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @test */
    public function it_adds_entities_to_the_message()


    {
        $client = $this->getClient();

        $messageChannel = '1234567890';
        $messageText = 'Hello';
        $message = new IncomingMessage($messageText, '', $messageChannel);
        $callback = function ($m) use (&$message) {
            $message = $m;
        };

        $middleware = new DialogFlowV2($client);
        $middleware->received($message, $callback, m::mock(BotMan::class));

        self::assertSame([
            'apiReply' => 'Hi!',
            'apiAction' => 'input.welcome',
            'apiActionIncomplete' => true,
            'apiIntent' => 'Default Welcome Intent',
            'apiParameters' => ['key1' => 'value1'],
            'apiContexts' => [],
        ], $message->getExtras());
    }

    /** @test */
    public function it_matches_messages()
    {
        $messageText = 'Hi!';
        $message = new IncomingMessage($messageText, '', '');

        $callback = function ($m) use (&$message) {
            $message = $m;
        };

        $client = $this->getClient();

        $middleware = new DialogFlowV2($client);
        $middleware->listenForAction();
        $middleware->received($message, $callback, m::mock(BotMan::class));
        $expectedAction = 'input.welcome';
        self::assertTrue($middleware->matching($message, $expectedAction, false));
        self::assertFalse($middleware->matching($message, 'some_other_action', false));
    }

    /** @test */
    public function it_matches_messages_with_regular_expressions()
    {
        $messageText = 'Hi!';
        $message = new IncomingMessage($messageText, '', '');
        $callback = function ($m) use (&$message) {
            $message = $m;
        };
        $client = $this->getClient();

        $middleware = new DialogFlowV2($client);
        $middleware->listenForAction();
        $expectedAction = 'input.*';
        $middleware->received($message, $callback, m::mock(BotMan::class));
        $this->assertTrue($middleware->matching($message, $expectedAction, false));
        $this->assertFalse($middleware->matching($message, 'some_other_action', false));
    }

    /** @test */
    public function it_can_be_created()
    {
        putenv('GOOGLE_CLOUD_PROJECT=dummy-agent');
        putenv('GOOGLE_APPLICATION_CREDENTIALS='. __DIR__ . DIRECTORY_SEPARATOR . 'dialogFlowV2Credentials'. DIRECTORY_SEPARATOR .'credential.json');
        $middleware = DialogFlowV2::create();
        self::assertInstanceOf(DialogFlowV2::class, $middleware);
    }

    /** @test */
    public function it_only_calls_service_once_per_listen()
    {
        $request = m::mock(\Illuminate\Http\Request::class . '[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode([]));

        $botman = BotManFactory::create([], new ArrayCache, $request);


        $client = $this->getClient();

        $middleware = new DialogFlowV2($client);
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

    protected function tearDown(): void
    {
        m::close();
    }

    /**
     * @return Client
     */
    private function getClient(): Client
    {
        $intent = new Intent();
        $intentContextNames = new RepeatedField(GPBType::STRING);
        $intent->setInputContextNames($intentContextNames);
        $intent->setEvents($intentContextNames);
        $trainingPhrase = new RepeatedField(GPBType::MESSAGE, TrainingPhrase::class);
        $intent->setTrainingPhrases($trainingPhrase);
        $outputContext = new RepeatedField(GPBType::MESSAGE, Context::class);
        $intent->setOutputContexts($outputContext);
        $parameters = new RepeatedField(GPBType::MESSAGE, Parameter::class);
        $intent->setParameters($parameters);
        $messages = new RepeatedField(GPBType::MESSAGE, Message::class);
        $intent->setMessages($messages);
        $defaultResponse = new RepeatedField(GPBType::ENUM);
        $intent->setDefaultResponsePlatforms($defaultResponse);
        $intent->setDisplayName('Default Welcome Intent');

        $queryResult = new QueryResult();
        $outputContexts = new RepeatedField(GPBType::MESSAGE, Context::class);
        $queryResult->setOutputContexts($outputContexts);
        $queryResult->setIntentDetectionConfidence(1);
        $queryResult->setQueryText('Hello');
        $queryResult->setAction('input.welcome');
        $queryResult->setLanguageCode('en');
        $queryResult->setIntent($intent);
        $messages = new RepeatedField(GPBType::MESSAGE, Message::class);
        $queryResult->setFulfillmentMessages($messages);
        $queryResult->setFulfillmentText('Hi!');
        $parameters = new Struct();
        $fields = new MapField(GPBType::STRING, GPBType::MESSAGE, Value::class);
        $value = new Value();
        $value->setStringValue('value1');
        $fields['key1'] = $value;

        $parameters->setFields($fields);
        $queryResult->setParameters($parameters);

        $response = m::mock(DetectIntentResponse::class);
        $response->shouldReceive('getQueryResult')
            ->once()
            ->andReturn($queryResult);

        $sessionClient = m::mock(SessionsClient::class);
        $sessionClient->shouldReceive('sessionName')
            ->andReturn('sessionId');
        $sessionClient->shouldReceive('detectIntent')
            ->once()
            ->andReturn($response);

        $sessionClient->shouldReceive('close')
            ->once();

        return new Client('en', $sessionClient);
    }
}
