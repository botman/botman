<?php

namespace Mpociot\BotMan\Tests\Drivers;

use Mockery as m;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpFoundation\Request;
use Mpociot\BotMan\Drivers\BotFrameworkDriver;
use Symfony\Component\HttpFoundation\Response;

class BotFrameworkDriverTest extends PHPUnit_Framework_TestCase
{
    private function getDriver($responseData, $htmlInterface = null)
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        return new BotFrameworkDriver($request, [], $htmlInterface);
    }

    private function getResponseData()
    {
        return [
            'type' => 'message',
            'id' => '4IIOjFkzcYy1HbYO',
            'timestamp' => '2016-11-29T21:58:31.879Z',
            'serviceUrl' => 'https://skype.botframework.com',
            'channelId' => 'skype',
            'from' => [
                'id' => '29:1zPNq1EP2_H-mik_1MQgKYp0nZu9tUljr2VEdTlGhEo7VlZ1YVDVSUZ0g70sk1',
                'name' => 'Julia',
            ],
            'conversation' => [
                'id' => '29:1zPNq1EP2_H-mik_1MQgKYp0nZu9tUljr2VEdTlGhEo7VlZ1YVDVSUZ0g70sk1',
            ],
            'recipient' => [
                'id' => '28:a91af6d0-0e59-4adb-abcf-b6a1f728e3f3',
                'name' => 'BotMan',
            ],
            'text' => 'hey there',
            'entities' => [],
        ];
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver([]);
        $this->assertSame('BotFramework', $driver->getName());
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

        $driver = $this->getDriver($this->getResponseData());
        $this->assertTrue($driver->matchesRequest());
    }

    /** @test */
    public function it_returns_the_message_object()
    {
        $driver = $this->getDriver($this->getResponseData());
        $this->assertTrue(is_array($driver->getMessages()));
    }

    /** @test */
    public function it_returns_the_message_text()
    {
        $driver = $this->getDriver([
            'type' => 'message',
            'id' => '4IIOjFkzcYy1HbYO',
            'timestamp' => '2016-11-29T21:58:31.879Z',
            'serviceUrl' => 'https://skype.botframework.com',
            'channelId' => 'skype',
            'from' => [
                'id' => '29:1zPNq1EP2_H-mik_1MQgKYp0nZu9tUljr2VEdTlGhEo7VlZ1YVDVSUZ0g70sk1',
                'name' => 'Julia',
            ],
            'conversation' => [
                'id' => '29:1zPNq1EP2_H-mik_1MQgKYp0nZu9tUljr2VEdTlGhEo7VlZ1YVDVSUZ0g70sk1',
            ],
            'recipient' => [
                'id' => '28:a91af6d0-0e59-4adb-abcf-b6a1f728e3f3',
                'name' => 'BotMan',
            ],
            'text' => 'Hi Julia',
            'entities' => [],
        ]);
        $this->assertSame('Hi Julia', $driver->getMessages()[0]->getMessage());
    }

    /** @test */
    public function it_replaces_bot_name_in_group_chat()
    {
        $responseData = $this->getResponseData();
        $responseData['text'] = '<at id="28:3176e6ca-8dad-4c6d-97f1-e2a86548acc8">@Bot</at>    Hi Maks';

        $driver = $this->getDriver($responseData);

        $this->assertSame('Hi Maks', $driver->getMessages()[0]->getMessage());
    }

    /** @test */
    public function it_detects_bots()
    {
        $driver = $this->getDriver($this->getResponseData());
        $this->assertFalse($driver->isBot());
    }

    /** @test */
    public function it_returns_the_user_object()
    {
        $driver = $this->getDriver($this->getResponseData());
        $message = $driver->getMessages()[0];
        $user = $driver->getUser($message);

        $this->assertSame($user->getId(), '29:1zPNq1EP2_H-mik_1MQgKYp0nZu9tUljr2VEdTlGhEo7VlZ1YVDVSUZ0g70sk1');
        $this->assertNull($user->getFirstName());
        $this->assertNull($user->getLastName());
        $this->assertSame($user->getUsername(), 'Julia');
    }

    /** @test */
    public function it_returns_the_user_id()
    {
        $driver = $this->getDriver($this->getResponseData());
        $this->assertSame('29:1zPNq1EP2_H-mik_1MQgKYp0nZu9tUljr2VEdTlGhEo7VlZ1YVDVSUZ0g70sk1', $driver->getMessages()[0]->getUser());
    }

    /** @test */
    public function it_returns_the_channel_id()
    {
        $driver = $this->getDriver($this->getResponseData());
        $this->assertSame('29:1zPNq1EP2_H-mik_1MQgKYp0nZu9tUljr2VEdTlGhEo7VlZ1YVDVSUZ0g70sk1', $driver->getMessages()[0]->getChannel());
    }

    /** @test */
    public function it_detects_users_from_interactive_messages()
    {
        $driver = $this->getDriver([
            'type' => 'message',
            'id' => '4IIOjFkzcYy1HbYO',
            'timestamp' => '2016-11-29T21:58:31.879Z',
            'serviceUrl' => 'https://skype.botframework.com',
            'channelId' => 'skype',
            'from' => [
                'id' => '29:1zPNq1EP2_H-mik_1MQgKYp0nZu9tUljr2VEdTlGhEo7VlZ1YVDVSUZ0g70sk1',
                'name' => 'Julia',
            ],
            'conversation' => [
                'id' => '29:1zPNq1EP2_H-mik_1MQgKYp0nZu9tUljr2VEdTlGhEo7VlZ1YVDVSUZ0g70sk1',
            ],
            'recipient' => [
                'id' => '28:a91af6d0-0e59-4adb-abcf-b6a1f728e3f3',
                'name' => 'BotMan',
            ],
            'text' => 'hey there<botman value="a" />',
            'entities' => [],
        ]);

        $this->assertSame('29:1zPNq1EP2_H-mik_1MQgKYp0nZu9tUljr2VEdTlGhEo7VlZ1YVDVSUZ0g70sk1', $driver->getMessages()[0]->getUser());
    }

    /** @test */
    public function it_detects_channels_from_interactive_messages()
    {
        $driver = $this->getDriver([
            'type' => 'message',
            'id' => '4IIOjFkzcYy1HbYO',
            'timestamp' => '2016-11-29T21:58:31.879Z',
            'serviceUrl' => 'https://skype.botframework.com',
            'channelId' => 'skype',
            'from' => [
                'id' => '29:1zPNq1EP2_H-mik_1MQgKYp0nZu9tUljr2VEdTlGhEo7VlZ1YVDVSUZ0g70sk1',
                'name' => 'Julia',
            ],
            'conversation' => [
                'id' => '29:1zPNq1EP2_H-mik_1MQgKYp0nZu9tUljr2VEdTlGhEo7VlZ1YVDVSUZ0g70sk1',
            ],
            'recipient' => [
                'id' => '28:a91af6d0-0e59-4adb-abcf-b6a1f728e3f3',
                'name' => 'BotMan',
            ],
            'text' => 'hey there<botman value="a" />',
            'entities' => [],
        ]);

        $this->assertSame('29:1zPNq1EP2_H-mik_1MQgKYp0nZu9tUljr2VEdTlGhEo7VlZ1YVDVSUZ0g70sk1', $driver->getMessages()[0]->getChannel());
    }

    /** @test */
    public function it_is_configured()
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn('');
        $htmlInterface = m::mock(Curl::class);

        $driver = new BotFrameworkDriver($request, [
            'microsoft_app_id' => 'app_id',
            'microsoft_app_key' => 'app_key',
        ], $htmlInterface);

        $this->assertTrue($driver->isConfigured());

        $driver = new BotFrameworkDriver($request, [
            'microsoft_app_id' => null,
            'microsoft_app_key' => null,
        ], $htmlInterface);

        $this->assertFalse($driver->isConfigured());

        $driver = new BotFrameworkDriver($request, [], $htmlInterface);

        $this->assertFalse($driver->isConfigured());
    }

    /** @test */
    public function it_can_reply_string_messages()
    {
        $responseData = $this->getResponseData();

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://login.microsoftonline.com/botframework.com/oauth2/v2.0/token', [], [
                'client_id' => 'app_id',
                'client_secret' => 'app_key',
                'grant_type' => 'client_credentials',
                'scope' => 'https://api.botframework.com/.default',
            ])
            ->andReturn(new Response(json_encode([
                'access_token' => 'SECRET_TOKEN',
            ])));

        $html->shouldReceive('post')
            ->once()
            ->with('https://skype.botframework.com/v3/conversations/29%3A1zPNq1EP2_H-mik_1MQgKYp0nZu9tUljr2VEdTlGhEo7VlZ1YVDVSUZ0g70sk1/activities', [], [
                'type' => 'message',
                'text' => 'Test',
            ], [
                'Content-Type:application/json',
                'Authorization:Bearer SECRET_TOKEN',
            ], true);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new BotFrameworkDriver($request, [
            'microsoft_app_id' => 'app_id',
            'microsoft_app_key' => 'app_key',
        ], $html);

        $message = $driver->getMessages()[0];
        $driver->reply('Test', $message);
    }

    /** @test */
    public function it_can_reply_string_messages_on_originated_messages()
    {
        $responseData = $this->getResponseData();

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://login.microsoftonline.com/botframework.com/oauth2/v2.0/token', [], [
                'client_id' => 'app_id',
                'client_secret' => 'app_key',
                'grant_type' => 'client_credentials',
                'scope' => 'https://api.botframework.com/.default',
            ])
            ->andReturn(new Response(json_encode([
                'access_token' => 'SECRET_TOKEN',
            ])));

        $html->shouldReceive('post')
            ->once()
            ->with('/v3/conversations/29%3A1zPNq1EP2_H-mik_1MQgKYp0nZu9tUljr2VEdTlGhEo7VlZ1YVDVSUZ0g70sk1/activities', [], [
                'type' => 'message',
                'text' => 'Test',
            ], [
                'Content-Type:application/json',
                'Authorization:Bearer SECRET_TOKEN',
            ], true);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new BotFrameworkDriver($request, [
            'microsoft_app_id' => 'app_id',
            'microsoft_app_key' => 'app_key',
        ], $html);

        $user = '29:1zPNq1EP2_H-mik_1MQgKYp0nZu9tUljr2VEdTlGhEo7VlZ1YVDVSUZ0g70sk1';
        $message = new Message('hey there', $user, $user);
        $driver->reply('Test', $message);
    }

    /** @test */
    public function it_can_reply_with_additional_parameters()
    {
        $responseData = $this->getResponseData();

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://login.microsoftonline.com/botframework.com/oauth2/v2.0/token', [], [
                'client_id' => 'app_id',
                'client_secret' => 'app_key',
                'grant_type' => 'client_credentials',
                'scope' => 'https://api.botframework.com/.default',
            ])
            ->andReturn(new Response(json_encode([
                'access_token' => 'SECRET_TOKEN',
            ])));

        $html->shouldReceive('post')
            ->once()
            ->with('https://skype.botframework.com/v3/conversations/29%3A1zPNq1EP2_H-mik_1MQgKYp0nZu9tUljr2VEdTlGhEo7VlZ1YVDVSUZ0g70sk1/activities', [], [
                'type' => 'message',
                'text' => 'Test',
                'foo' => 'bar',
            ], [
                'Content-Type:application/json',
                'Authorization:Bearer SECRET_TOKEN',
            ], true);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new BotFrameworkDriver($request, [
            'microsoft_app_id' => 'app_id',
            'microsoft_app_key' => 'app_key',
        ], $html);

        $message = $driver->getMessages()[0];
        $driver->reply('Test', $message, [
            'foo' => 'bar',
        ]);
    }

    /** @test */
    public function it_can_reply_message_objects()
    {
        $responseData = $this->getResponseData();

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://login.microsoftonline.com/botframework.com/oauth2/v2.0/token', [], [
                'client_id' => 'app_id',
                'client_secret' => 'app_key',
                'grant_type' => 'client_credentials',
                'scope' => 'https://api.botframework.com/.default',
            ])
            ->andReturn(new Response(json_encode([
                'access_token' => 'SECRET_TOKEN',
            ])));

        $html->shouldReceive('post')
            ->once()
            ->with('https://skype.botframework.com/v3/conversations/29%3A1zPNq1EP2_H-mik_1MQgKYp0nZu9tUljr2VEdTlGhEo7VlZ1YVDVSUZ0g70sk1/activities', [], [
                'type' => 'message',
                'text' => 'Test',
            ], [
                'Content-Type:application/json',
                'Authorization:Bearer SECRET_TOKEN',
            ], true);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new BotFrameworkDriver($request, [
            'microsoft_app_id' => 'app_id',
            'microsoft_app_key' => 'app_key',
        ], $html);

        $message = $driver->getMessages()[0];
        $driver->reply(\Mpociot\BotMan\Messages\Message::create('Test'), $message);
    }

    /** @test */
    public function it_can_reply_message_objects_with_image()
    {
        $responseData = $this->getResponseData();

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://login.microsoftonline.com/botframework.com/oauth2/v2.0/token', [], [
                'client_id' => 'app_id',
                'client_secret' => 'app_key',
                'grant_type' => 'client_credentials',
                'scope' => 'https://api.botframework.com/.default',
            ])
            ->andReturn(new Response(json_encode([
                'access_token' => 'SECRET_TOKEN',
            ])));

        $html->shouldReceive('post')
            ->once()
            ->with('https://skype.botframework.com/v3/conversations/29%3A1zPNq1EP2_H-mik_1MQgKYp0nZu9tUljr2VEdTlGhEo7VlZ1YVDVSUZ0g70sk1/activities', [], [
                'type' => 'message',
                'text' => 'Test',
                'attachments' => [
                    [
                        'contentType' => 'image/png',
                        'contentUrl' => 'http://foo.com/bar.png',
                    ],
                ],
            ], [
                'Content-Type:application/json',
                'Authorization:Bearer SECRET_TOKEN',
            ], true);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new BotFrameworkDriver($request, [
            'microsoft_app_id' => 'app_id',
            'microsoft_app_key' => 'app_key',
        ], $html);

        $message = $driver->getMessages()[0];
        $driver->reply(\Mpociot\BotMan\Messages\Message::create('Test')->image('http://foo.com/bar.png'), $message);
    }

    /** @test */
    public function it_can_reply_message_objects_with_video()
    {
        $responseData = $this->getResponseData();

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://login.microsoftonline.com/botframework.com/oauth2/v2.0/token', [], [
                'client_id' => 'app_id',
                'client_secret' => 'app_key',
                'grant_type' => 'client_credentials',
                'scope' => 'https://api.botframework.com/.default',
            ])
            ->andReturn(new Response(json_encode([
                'access_token' => 'SECRET_TOKEN',
            ])));

        $html->shouldReceive('post')
            ->once()
            ->with('https://skype.botframework.com/v3/conversations/29%3A1zPNq1EP2_H-mik_1MQgKYp0nZu9tUljr2VEdTlGhEo7VlZ1YVDVSUZ0g70sk1/activities', [], [
                'type' => 'message',
                'text' => 'Test',
                'attachments' => [
                    [
                        'contentType' => 'video/mp4',
                        'contentUrl' => 'http://foo.com/bar.mp4',
                    ],
                ],
            ], [
                'Content-Type:application/json',
                'Authorization:Bearer SECRET_TOKEN',
            ], true);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new BotFrameworkDriver($request, [
            'microsoft_app_id' => 'app_id',
            'microsoft_app_key' => 'app_key',
        ], $html);

        $message = $driver->getMessages()[0];
        $driver->reply(\Mpociot\BotMan\Messages\Message::create('Test')->video('http://foo.com/bar.mp4'), $message);
    }

    /** @test */
    public function it_interactive_responses_correctly()
    {
        $driver = $this->getDriver([
            'event' => [
                'user' => '29:2ZC81QtrQQti_yclfvcaNZnRgNSihIXUc6rgigeq82us',
            ],
        ]);

        $text = 'I use botman with Skype<botman value="yes"></botman>';
        $message = new Message($text, '29:2ZC81QtrQQti_yclfvcaNZnRgNSihIXUc6rgigeq82us', '29:2ZC81QtrQQti_yclfvcaNZnRgNSihIXUc6rgigeq82us');

        $answer = $driver->getConversationAnswer($message);
        $this->assertSame($text, $answer->getText());
        $this->assertTrue($answer->isInteractiveMessageReply());
        $this->assertSame('yes', $answer->getValue());
    }
}
