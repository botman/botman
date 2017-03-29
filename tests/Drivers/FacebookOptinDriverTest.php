<?php

namespace Mpociot\BotMan\Tests\Drivers;

use Mockery as m;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpFoundation\Request;
use Mpociot\BotMan\Drivers\FacebookOptinDriver;

class FacebookOptinDriverTest extends PHPUnit_Framework_TestCase
{
    private function getDriver($responseData, array $config = ['facebook_token' => 'Foo'], $signature = '')
    {
        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn($responseData);
        $request->headers->set('X_HUB_SIGNATURE', $signature);

        return new FacebookOptinDriver($request, $config, new Curl());
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver('');
        $this->assertSame('FacebookOptin', $driver->getName());
    }

    /** @test */
    public function it_matches_the_request()
    {
        $request = '{}';
        $driver = $this->getDriver($request);
        $this->assertFalse($driver->matchesRequest());

        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"optin": {"ref":"optin","user_ref":"1234"}}]}]}';
        $driver = $this->getDriver($request);
        $this->assertTrue($driver->matchesRequest());

        $config = ['facebook_token' => 'Foo', 'facebook_app_secret' => 'Bar'];
        $request = '{}';
        $driver = $this->getDriver($request, $config);
        $this->assertFalse($driver->matchesRequest());
    }

    /** @test */
    public function it_returns_the_message()
    {
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"optin": {"ref":"optin","user_ref":"1234"}}]}]}';
        $driver = $this->getDriver($request);
        $this->assertSame('optin', $driver->getMessages()[0]->getMessage());
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
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"optin": {"ref":"optin","user_ref":"1234"}}]}]}';
        $driver = $this->getDriver($request);

        $this->assertSame('111899832631525', $driver->getMessages()[0]->getUser());
    }

    /** @test */
    public function it_returns_the_channel_id()
    {
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"optin": {"ref":"optin","user_ref":"1234"}}]}]}';
        $driver = $this->getDriver($request);

        $this->assertSame('1234', $driver->getMessages()[0]->getChannel());
    }

    /** @test */
    public function it_is_configured()
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn('');
        $htmlInterface = m::mock(Curl::class);

        $driver = new FacebookOptinDriver($request, [
            'facebook_token' => 'token',
        ], $htmlInterface);

        $this->assertFalse($driver->isConfigured());

        $driver = new FacebookOptinDriver($request, [
            'facebook_token' => null,
        ], $htmlInterface);

        $this->assertFalse($driver->isConfigured());

        $driver = new FacebookOptinDriver($request, [], $htmlInterface);

        $this->assertFalse($driver->isConfigured());
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
                            'recipient' => [
                                'id' => '0987654321',
                            ],
                            'timestamp' => 1234567890,
                            'optin' => [
                                'ref' => 'PASS_THROUGH_PARAM',
                                'user_ref' => '1234',
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
                    'user_ref' => '1234',
                ],
                'message' => [
                    'text' => 'Test',
                ],
                'access_token' => 'Foo',
            ]);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        $driver = new FacebookOptinDriver($request, [
            'facebook_token' => 'Foo',
        ], $html);

        $message = new Message('', '', '1234');
        $driver->reply('Test', $message);
    }
}
