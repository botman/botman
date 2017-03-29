<?php

namespace Mpociot\BotMan\Tests\Drivers;

use Mockery as m;
use Mpociot\BotMan\Button;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Mpociot\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpFoundation\Request;
use Mpociot\BotMan\Drivers\FacebookPostbackDriver;

class FacebookPostbackDriverTest extends PHPUnit_Framework_TestCase
{
    private function getDriver($responseData, array $config = ['facebook_token' => 'Foo'], $signature = '')
    {
        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn($responseData);
        $request->headers->set('X_HUB_SIGNATURE', $signature);

        return new FacebookPostbackDriver($request, $config, new Curl());
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver('');
        $this->assertSame('FacebookPostback', $driver->getName());
    }

    /** @test */
    public function it_matches_the_request()
    {
        $request = '{}';
        $driver = $this->getDriver($request);
        $this->assertFalse($driver->matchesRequest());

        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"postback":{"payload":"MY_PAYLOAD"}}]}]}';
        $driver = $this->getDriver($request);
        $this->assertTrue($driver->matchesRequest());

        $config = ['facebook_token' => 'Foo', 'facebook_app_secret' => 'Bar'];
        $request = '{}';
        $driver = $this->getDriver($request, $config);
        $this->assertFalse($driver->matchesRequest());

        $signature = 'Foo';
        $config = ['facebook_token' => 'Foo', 'facebook_app_secret' => 'Bar'];
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"message":{"mid":"mid.1480279487147:4388d3b344","seq":36,"text":"Hi"}}]}]}';
        $driver = $this->getDriver($request, $config, $signature);
        $this->assertFalse($driver->matchesRequest());

        $signature = 'sha1=74432bfe572675092cc81b5ac903ff3f971b04e5';
        $config = ['facebook_token' => 'Foo', 'facebook_app_secret' => 'Bar'];
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"message":{"mid":"mid.1480279487147:4388d3b344","seq":36,"text":"Hi"}}]}]}';
        $driver = $this->getDriver($request, $config, $signature);
        $this->assertFalse($driver->matchesRequest());
    }

    /** @test */
    public function it_returns_the_message()
    {
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"postback":{"payload":"MY_PAYLOAD"}}]}]}';
        $driver = $this->getDriver($request);
        $this->assertSame('MY_PAYLOAD', $driver->getMessages()[0]->getMessage());
    }

    /** @test */
    public function it_returns_an_empty_message_if_nothing_matches()
    {
        $request = '';
        $driver = $this->getDriver($request);

        $this->assertSame('', $driver->getMessages()[0]->getMessage());
    }

    /** @test */
    public function it_detects_bots()
    {
        $driver = $this->getDriver('');
        $this->assertFalse($driver->isBot());
    }

    /** @test */
    public function it_returns_the_user_id()
    {
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"postback":{"payload":"MY_PAYLOAD"}}]}]}';
        $driver = $this->getDriver($request);

        $this->assertSame('111899832631525', $driver->getMessages()[0]->getUser());
    }

    /** @test */
    public function it_returns_the_channel_id()
    {
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"postback":{"payload":"MY_PAYLOAD"}}]}]}';
        $driver = $this->getDriver($request);

        $this->assertSame('1433960459967306', $driver->getMessages()[0]->getChannel());
    }

    /** @test */
    public function it_can_reply_string_messages()
    {
        $responseData = [
            'object' => 'page',
            'event' => [
                [
                    'messaging' => [
                        [
                            'sender' => [
                                'id' => '1234567890',
                            ],
                            'recipient' => [
                                'id' => '0987654321',
                            ],
                            'message' => [
                                'text' => 'test',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://graph.facebook.com/v2.6/me/messages', [], [
                'recipient' => [
                    'id' => '1234567890',
                ],
                'message' => [
                    'text' => 'Test',
                ],
                'access_token' => 'Foo',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new FacebookPostbackDriver($request, [
            'facebook_token' => 'Foo',
        ], $html);

        $message = new Message('', '', '1234567890');
        $driver->reply('Test', $message);
    }

    /** @test */
    public function it_can_reply_with_additional_parameters()
    {
        $responseData = [
            'object' => 'page',
            'event' => [
                [
                    'messaging' => [
                        [
                            'sender' => [
                                'id' => '1234567890',
                            ],
                            'recipient' => [
                                'id' => '0987654321',
                            ],
                            'message' => [
                                'text' => 'test',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://graph.facebook.com/v2.6/me/messages', [], [
                'recipient' => [
                    'id' => '1234567890',
                ],
                'message' => [
                    'text' => 'Test',
                ],
                'access_token' => 'Foo',
                'custom' => 'payload',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new FacebookPostbackDriver($request, [
            'facebook_token' => 'Foo',
        ], $html);

        $message = new Message('', '', '1234567890');
        $driver->reply('Test', $message, [
            'custom' => 'payload',
        ]);
    }

    /** @test */
    public function it_returns_answer_from_interactive_messages()
    {
        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode([]));

        $driver = new FacebookPostbackDriver($request, [
            'facebook_token' => 'Foo',
        ], m::mock(Curl::class));

        $message = new Message('Red', '0987654321', '1234567890', [
            'sender' => [
                'id' => '1234567890',
            ],
            'recipient' => [
                'id' => '0987654321',
            ],
            'message' => [
                'text' => 'Red',
                'quick_reply' => [
                    'payload' => 'DEVELOPER_DEFINED_PAYLOAD',
                ],
            ],
        ]);

        $this->assertSame('Red', $driver->getConversationAnswer($message)->getText());
        $this->assertSame('DEVELOPER_DEFINED_PAYLOAD', $driver->getConversationAnswer($message)->getValue());
    }

    /** @test */
    public function it_returns_answer_from_regular_messages()
    {
        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode([]));

        $driver = new FacebookPostbackDriver($request, [
            'facebook_token' => 'Foo',
        ], m::mock(Curl::class));

        $message = new Message('Red', '0987654321', '1234567890', [
            'sender' => [
                'id' => '1234567890',
            ],
            'recipient' => [
                'id' => '0987654321',
            ],
            'message' => [
                'text' => 'Red',
            ],
        ]);

        $this->assertSame('Red', $driver->getConversationAnswer($message)->getText());
        $this->assertSame(null, $driver->getConversationAnswer($message)->getValue());
    }

    /** @test */
    public function it_can_reply_questions()
    {
        $question = Question::create('How are you doing?')
            ->addButton(Button::create('Great')->value('great'))
            ->addButton(Button::create('Good')->value('good'));

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://graph.facebook.com/v2.6/me/messages', [], [
                'recipient' => [
                    'id' => '1234567890',
                ],
                'message' => [
                    'text' => 'How are you doing?',
                    'quick_replies' => [
                        [
                            'content_type' => 'text',
                            'title' => 'Great',
                            'payload' => 'great',
                            'image_url' => null,
                        ],
                        [
                            'content_type' => 'text',
                            'title' => 'Good',
                            'payload' => 'good',
                            'image_url' => null,
                        ],
                    ],
                ],
                'access_token' => 'Foo',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn('[]');

        $driver = new FacebookPostbackDriver($request, [
            'facebook_token' => 'Foo',
        ], $html);

        $message = new Message('', '', '1234567890');
        $driver->reply($question, $message);
    }

    /** @test */
    public function it_is_configured()
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn('');
        $htmlInterface = m::mock(Curl::class);

        $driver = new FacebookPostbackDriver($request, [
            'facebook_token' => 'token',
        ], $htmlInterface);

        $this->assertFalse($driver->isConfigured());

        $driver = new FacebookPostbackDriver($request, [
            'facebook_token' => null,
        ], $htmlInterface);

        $this->assertFalse($driver->isConfigured());

        $driver = new FacebookPostbackDriver($request, [], $htmlInterface);

        $this->assertFalse($driver->isConfigured());
    }

    /** @test */
    public function it_can_reply_message_objects()
    {
        $responseData = [
            'object' => 'page',
            'event' => [
                [
                    'messaging' => [
                        [
                            'sender' => [
                                'id' => '1234567890',
                            ],
                            'recipient' => [
                                'id' => '0987654321',
                            ],
                            'message' => [
                                'text' => 'test',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://graph.facebook.com/v2.6/me/messages', [], [
                'recipient' => [
                    'id' => '1234567890',
                ],
                'message' => [
                    'text' => 'Test',
                ],
                'access_token' => 'Foo',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new FacebookPostbackDriver($request, [
            'facebook_token' => 'Foo',
        ], $html);

        $message = new Message('', '', '1234567890');
        $driver->reply(\Mpociot\BotMan\Messages\Message::create('Test'), $message);
    }

    /** @test */
    public function it_can_reply_message_objects_with_image()
    {
        $responseData = [
            'object' => 'page',
            'event' => [
                [
                    'messaging' => [
                        [
                            'sender' => [
                                'id' => '1234567890',
                            ],
                            'recipient' => [
                                'id' => '0987654321',
                            ],
                            'message' => [
                                'text' => 'test',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://graph.facebook.com/v2.6/me/messages', [], [
                'recipient' => [
                    'id' => '1234567890',
                ],
                'message' => [
                    'attachment' => [
                        'type' => 'image',
                        'payload' => [
                            'url' => 'http://image.url//foo.png',
                        ],
                    ],
                ],
                'access_token' => 'Foo',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new FacebookPostbackDriver($request, [
            'facebook_token' => 'Foo',
        ], $html);

        $message = new Message('', '', '1234567890');
        $driver->reply(\Mpociot\BotMan\Messages\Message::create('Test', 'http://image.url//foo.png'), $message);
    }
}
