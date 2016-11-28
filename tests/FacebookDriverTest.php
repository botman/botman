<?php

namespace Mpociot\SlackBot\Tests;

use Mockery as m;
use Mpociot\SlackBot\Drivers\FacebookDriver;
use Mpociot\SlackBot\Http\Curl;
use Mpociot\SlackBot\Message;
use PHPUnit_Framework_TestCase;

class FacebookDriverTest extends PHPUnit_Framework_TestCase
{

    private function getDriver($responseData)
    {
        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn($responseData);

        return new FacebookDriver($request, [], new Curl());
    }

    /** @test */
    public function it_matches_the_request()
    {
        $request = '{}';
        $driver = $this->getDriver($request);
        $this->assertFalse($driver->matchesRequest());

        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"message":{"mid":"mid.1480279487147:4388d3b344","seq":36,"text":"Hi"}}]}]}';
        $driver = $this->getDriver($request);
        $this->assertTrue($driver->matchesRequest());
    }

    /** @test */
    public function it_returns_the_message()
    {
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"message":{"mid":"mid.1480279487147:4388d3b344","seq":36,"text":"Hi Julia"}}]}]}';
        $driver = $this->getDriver($request);

        $this->assertSame('Hi Julia', $driver->getMessages()[0]->getMessage());
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
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"message":{"mid":"mid.1480279487147:4388d3b344","seq":36,"text":"Hi Julia"}}]}]}';
        $driver = $this->getDriver($request);

        $this->assertSame('1433960459967306', $driver->getMessages()[0]->getUser());
    }

    /** @test */
    public function it_returns_the_channel_id()
    {
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"message":{"mid":"mid.1480279487147:4388d3b344","seq":36,"text":"Hi Julia"}}]}]}';
        $driver = $this->getDriver($request);

        $this->assertSame('111899832631525', $driver->getMessages()[0]->getChannel());
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
                                'id' => '1234567890'
                            ],
                            'recipient' => [
                                'id' => '0987654321'
                            ],
                            'message' => [
                                'text' => 'test'
                            ]
                        ]
                    ]
                ]
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://graph.facebook.com/v2.6/me/messages', [], [
                'recipient' => [
                    'id' => '1234567890'
                ],
                'message' => [
                    'text' => 'Test'
                ],
                'access_token' => 'Foo'
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new FacebookDriver($request, [
            'facebook_token' => 'Foo'
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
                                'id' => '1234567890'
                            ],
                            'recipient' => [
                                'id' => '0987654321'
                            ],
                            'message' => [
                                'text' => 'test'
                            ]
                        ]
                    ]
                ]
            ],
        ];

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://graph.facebook.com/v2.6/me/messages', [], [
                'recipient' => [
                    'id' => '1234567890'
                ],
                'message' => [
                    'text' => 'Test'
                ],
                'access_token' => 'Foo',
                'custom' => 'payload'
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new FacebookDriver($request, [
            'facebook_token' => 'Foo'
        ], $html);

        $message = new Message('', '', '1234567890');
        $driver->reply('Test', $message, [
            'custom' => 'payload'
        ]);
    }

}
