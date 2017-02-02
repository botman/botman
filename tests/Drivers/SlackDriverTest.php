<?php

namespace Mpociot\BotMan\Tests\Drivers;

use Mockery as m;
use Mpociot\BotMan\Button;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Mpociot\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Drivers\SlackDriver;
use Symfony\Component\HttpFoundation\Request;

class SlackDriverTest extends PHPUnit_Framework_TestCase
{
    private function getDriver($responseData, $htmlInterface = null)
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        return new SlackDriver($request, [], $htmlInterface);
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver([]);
        $this->assertSame('Slack', $driver->getName());
    }

    /** @test */
    public function it_matches_the_request()
    {
        $driver = $this->getDriver([
            'event' => [
                'text' => 'bar',
            ],
        ]);
        $this->assertFalse($driver->matchesRequest());

        $driver = $this->getDriver([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'bar',
            ],
        ]);
        $this->assertTrue($driver->matchesRequest());
    }

    /** @test */
    public function it_matches_the_request_for_outgoing_webhooks()
    {
        $request = new Request([], [
            'token' => '1234567890',
            'team_id' => 'T046C3T',
            'team_domain' => 'botman',
            'service_id' => '1234567890',
            'channel_id' => 'C1234567890',
            'channel_name' => 'botman',
            'timestamp' => '1481125473.000011',
            'user_id' => 'U1234567890',
            'user_name' => 'marcel',
            'text' => 'hello',
        ]);
        $driver = new SlackDriver($request, [], m::mock(Curl::class));
        $this->assertTrue($driver->matchesRequest());
    }

    /** @test */
    public function it_matches_the_request_for_a_slash_command()
    {
        $request = new Request([], [
            'token' => '1234567890',
            'team_id' => 'T046C3T',
            'team_domain' => 'botman',
            'service_id' => '1234567890',
            'channel_id' => 'C1234567890',
            'channel_name' => 'botman',
            'timestamp' => '1481125473.000011',
            'user_id' => 'U1234567890',
            'user_name' => 'marcel',
            'command' => '/botman',
            'text' => 'hello',
        ]);
        $driver = new SlackDriver($request, [], m::mock(Curl::class));
        $this->assertTrue($driver->matchesRequest());
    }

    /** @test */
    public function it_returns_the_message_object()
    {
        $driver = $this->getDriver([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'Hi Julia',
            ],
        ]);
        $this->assertTrue(is_array($driver->getMessages()));
    }

    /** @test */
    public function it_returns_the_message_object_for_outgoing_webhooks()
    {
        $request = new Request([], [
            'token' => '1234567890',
            'team_id' => 'T046C3T',
            'team_domain' => 'botman',
            'service_id' => '1234567890',
            'channel_id' => 'C1234567890',
            'channel_name' => 'botman',
            'timestamp' => '1481125473.000011',
            'user_id' => 'U1234567890',
            'user_name' => 'marcel',
            'text' => 'Hi Julia',
        ]);
        $driver = new SlackDriver($request, [], m::mock(Curl::class));
        $this->assertTrue(is_array($driver->getMessages()));
        $this->assertSame('Hi Julia', $driver->getMessages()[0]->getMessage());
    }

    /** @test */
    public function it_returns_the_message_object_for_slash_commands()
    {
        $request = new Request([], [
            'token' => '1234567890',
            'team_id' => 'T046C3T',
            'team_domain' => 'botman',
            'service_id' => '1234567890',
            'channel_id' => 'C1234567890',
            'channel_name' => 'botman',
            'timestamp' => '1481125473.000011',
            'user_id' => 'U1234567890',
            'user_name' => 'marcel',
            'command' => '/botman',
            'text' => 'Hi Julia',
        ]);
        $driver = new SlackDriver($request, [], m::mock(Curl::class));
        $this->assertTrue(is_array($driver->getMessages()));
        $this->assertSame('/botman Hi Julia', $driver->getMessages()[0]->getMessage());
    }

    /** @test */
    public function it_returns_the_message_text()
    {
        $driver = $this->getDriver([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'Hi Julia',
            ],
        ]);
        $this->assertSame('Hi Julia', $driver->getMessages()[0]->getMessage());
    }

    /** @test */
    public function it_returns_the_user_object()
    {
        $driver = $this->getDriver([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'Hi Julia',
            ],
        ]);

        $message = $driver->getMessages()[0];
        $user = $driver->getUser($message);

        $this->assertSame($user->getId(), 'U0X12345');
        $this->assertNull($user->getFirstName());
        $this->assertNull($user->getLastName());
        $this->assertNull($user->getUsername());
    }

    /** @test */
    public function it_detects_bots()
    {
        $driver = $this->getDriver([
            'event' => [
                'user' => 'U0X12345',
                'text' => 'Hi Julia',
            ],
        ]);
        $this->assertFalse($driver->isBot());

        $driver = $this->getDriver([
            'event' => [
                'user' => 'U0X12345',
                'bot_id' => 'foo',
                'text' => 'Hi Julia',
            ],
        ]);
        $this->assertTrue($driver->isBot());
    }

    /** @test */
    public function it_returns_the_user_id()
    {
        $driver = $this->getDriver([
            'event' => [
                'user' => 'U0X12345',
            ],
        ]);
        $this->assertSame('U0X12345', $driver->getMessages()[0]->getUser());
    }

    /** @test */
    public function it_returns_the_user_id_for_outgoing_webhooks()
    {
        $request = new Request([], [
            'token' => '1234567890',
            'team_id' => 'T046C3T',
            'team_domain' => 'botman',
            'service_id' => '1234567890',
            'channel_id' => 'C1234567890',
            'channel_name' => 'botman',
            'timestamp' => '1481125473.000011',
            'user_id' => 'U1234567890',
            'user_name' => 'marcel',
            'text' => 'Hi Julia',
        ]);
        $driver = new SlackDriver($request, [], m::mock(Curl::class));
        $this->assertTrue(is_array($driver->getMessages()));
        $this->assertSame('U1234567890', $driver->getMessages()[0]->getUser());
    }

    /** @test */
    public function it_returns_the_channel_id()
    {
        $driver = $this->getDriver([
            'event' => [
                'user' => 'U0X12345',
                'channel' => 'general',
            ],
        ]);
        $this->assertSame('general', $driver->getMessages()[0]->getChannel());
    }

    /** @test */
    public function it_returns_the_channel_id_for_outgoing_webhooks()
    {
        $request = new Request([], [
            'token' => '1234567890',
            'team_id' => 'T046C3T',
            'team_domain' => 'botman',
            'service_id' => '1234567890',
            'channel_id' => 'C1234567890',
            'channel_name' => 'botman',
            'timestamp' => '1481125473.000011',
            'user_id' => 'U1234567890',
            'user_name' => 'marcel',
            'text' => 'Hi Julia',
        ]);
        $driver = new SlackDriver($request, [], m::mock(Curl::class));
        $this->assertTrue(is_array($driver->getMessages()));
        $this->assertSame('C1234567890', $driver->getMessages()[0]->getChannel());
    }

    /** @test */
    public function it_returns_the_message_for_conversation_answers()
    {
        $driver = $this->getDriver([
            'event' => [
                'user' => 'U0X12345',
                'channel' => 'general',
                'text' => 'response',
            ],
        ]);

        $message = new Message('response', 'U0X12345', 'general');
        $this->assertSame('response', $driver->getConversationAnswer($message)->getText());
        $this->assertSame($message, $driver->getConversationAnswer($message)->getMessage());
    }

    /** @test */
    public function it_detects_users_from_interactive_messages()
    {
        $request = new \Illuminate\Http\Request();
        $request->replace([
            'payload' => file_get_contents(__DIR__.'/../Fixtures/payload.json'),
        ]);
        $driver = new SlackDriver($request, [], new Curl());

        $this->assertSame('U045VRZFT', $driver->getMessages()[0]->getUser());
    }

    /** @test */
    public function it_detects_bots_from_interactive_messages()
    {
        $request = new \Illuminate\Http\Request();
        $request->replace([
            'payload' => file_get_contents(__DIR__.'/../Fixtures/payload.json'),
        ]);
        $driver = new SlackDriver($request, [], new Curl());

        $this->assertFalse($driver->isBot());
    }

    /** @test */
    public function it_detects_channels_from_interactive_messages()
    {
        $request = new \Illuminate\Http\Request();
        $request->replace([
            'payload' => file_get_contents(__DIR__.'/../Fixtures/payload.json'),
        ]);
        $driver = new SlackDriver($request, [], new Curl());

        $this->assertSame('C065W1189', $driver->getMessages()[0]->getChannel());
    }

    /** @test */
    public function it_returns_answer_from_interactive_messages()
    {
        $request = new \Illuminate\Http\Request();
        $request->replace([
            'payload' => file_get_contents(__DIR__.'/../Fixtures/payload.json'),
        ]);
        $driver = new SlackDriver($request, [], new Curl());

        $message = new Message('', '', '');
        $this->assertSame('yes', $driver->getConversationAnswer($message)->getValue());
    }

    /** @test */
    public function it_can_reply_string_messages()
    {
        $responseData = [
            'event' => [
                'user' => 'U0X12345',
                'channel' => 'general',
                'text' => 'response',
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://slack.com/api/chat.postMessage', [], [
                'token' => 'Foo',
                'channel' => 'general',
                'text' => 'Test',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new SlackDriver($request, [
            'slack_token' => 'Foo',
        ], $html);

        $message = new Message('', '', 'general');
        $driver->reply('Test', $message);
    }

    /** @test */
    public function it_can_reply_message_objects()
    {
        $responseData = [
            'event' => [
                'user' => 'U0X12345',
                'channel' => 'general',
                'text' => 'response',
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://slack.com/api/chat.postMessage', [], [
                'token' => 'Foo',
                'channel' => 'general',
                'text' => 'Test',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new SlackDriver($request, [
            'slack_token' => 'Foo',
        ], $html);

        $message = new Message('', '', 'general');
        $driver->reply(\Mpociot\BotMan\Messages\Message::create('Test'), $message);
    }

    /** @test */
    public function it_can_reply_message_objects_with_image()
    {
        $responseData = [
            'event' => [
                'user' => 'U0X12345',
                'channel' => 'general',
                'text' => 'response',
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://slack.com/api/chat.postMessage', [], [
                'token' => 'Foo',
                'channel' => 'general',
                'text' => 'Test',
                'attachments' => json_encode(['image_url' => 'http://image.url/foo.png']),
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new SlackDriver($request, [
            'slack_token' => 'Foo',
        ], $html);

        $message = new Message('', '', 'general');
        $driver->reply(\Mpociot\BotMan\Messages\Message::create('Test', 'http://image.url/foo.png'), $message);
    }

    /** @test */
    public function it_can_reply_string_messages_for_outgoing_webhooks()
    {
        $request = new Request([], [
            'token' => '1234567890',
            'team_id' => 'T046C3T',
            'team_domain' => 'botman',
            'service_id' => '1234567890',
            'channel_id' => 'C1234567890',
            'channel_name' => 'botman',
            'timestamp' => '1481125473.000011',
            'user_id' => 'U1234567890',
            'user_name' => 'marcel',
            'text' => 'Hi Julia',
        ]);
        $driver = new SlackDriver($request, [], m::mock(Curl::class));
        $driver->reply('test', $driver->getMessages()[0]);
    }

    /** @test */
    public function it_can_reply_string_messages_for_slash_commands()
    {
        $request = new Request([], [
            'token' => '1234567890',
            'team_id' => 'T046C3T',
            'team_domain' => 'botman',
            'service_id' => '1234567890',
            'channel_id' => 'C1234567890',
            'channel_name' => 'botman',
            'timestamp' => '1481125473.000011',
            'user_id' => 'U1234567890',
            'user_name' => 'marcel',
            'command' => '/botman',
            'text' => 'Hi Julia',
        ]);
        $driver = new SlackDriver($request, [], m::mock(Curl::class));
        $driver->reply('test', $driver->getMessages()[0]);
    }

    /** @test */
    public function it_can_reply_questions()
    {
        $responseData = [
            'event' => [
                'user' => 'U0X12345',
                'channel' => 'general',
                'text' => 'response',
            ],
        ];

        $question = Question::create('How are you doing?')
            ->addButton(Button::create('Great'))
            ->addButton(Button::create('Good'));

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://slack.com/api/chat.postMessage', [], [
                'token' => 'Foo',
                'channel' => 'general',
                'text' => '',
                'attachments' => json_encode([$question]),
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new SlackDriver($request, [
            'slack_token' => 'Foo',
        ], $html);

        $message = new Message('', '', 'general');
        $driver->reply($question, $message);
    }

    /** @test */
    public function it_can_reply_with_additional_parameters()
    {
        $responseData = [
            'event' => [
                'user' => 'U0X12345',
                'channel' => 'general',
                'text' => 'response',
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://slack.com/api/chat.postMessage', [], [
                'token' => 'Foo',
                'channel' => 'general',
                'text' => 'Test',
                'username' => 'ReplyBot',
                'icon_emoji' => ':dash:',
                'attachments' => json_encode([[
                    'image_url' => 'imageurl',
                ]]),
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new SlackDriver($request, [
            'slack_token' => 'Foo',
        ], $html);

        $message = new Message('response', '', 'general');
        $driver->reply('Test', $message, [
            'username' => 'ReplyBot',
            'icon_emoji' => ':dash:',
            'attachments' => json_encode([[
                'image_url' => 'imageurl',
            ]]),
        ]);
    }

    /** @test */
    public function it_can_reply_in_threads()
    {
        $responseData = [
            'event' => [
                'user' => 'U0X12345',
                'channel' => 'general',
                'text' => 'response',
                'ts' => '1234.5678',
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://slack.com/api/chat.postMessage', [], [
                'token' => 'Foo',
                'channel' => 'general',
                'text' => 'Test',
                'username' => 'ReplyBot',
                'thread_ts' => '1234.5678',
                'icon_emoji' => ':dash:',
                'attachments' => json_encode([[
                    'image_url' => 'imageurl',
                ]]),
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new SlackDriver($request, [
            'slack_token' => 'Foo',
        ], $html);

        $message = new Message('response', '', 'general', Collection::make([
            'ts' => '1234.5678',
        ]));
        $driver->replyInThread('Test', [
            'username' => 'ReplyBot',
            'icon_emoji' => ':dash:',
            'attachments' => json_encode([[
                'image_url' => 'imageurl',
            ]]),
        ], $message);
    }

    /** @test */
    public function it_is_configured()
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn('');
        $htmlInterface = m::mock(Curl::class);

        $driver = new SlackDriver($request, [
            'slack_token' => 'token',
        ], $htmlInterface);

        $this->assertTrue($driver->isConfigured());

        $driver = new SlackDriver($request, [
            'slack_token' => null,
        ], $htmlInterface);

        $this->assertFalse($driver->isConfigured());

        $driver = new SlackDriver($request, [], $htmlInterface);

        $this->assertFalse($driver->isConfigured());
    }
}
