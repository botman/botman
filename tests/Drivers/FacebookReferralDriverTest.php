<?php

namespace Mpociot\BotMan\Tests\Drivers;

use Mockery as m;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpFoundation\Request;
use Mpociot\BotMan\Drivers\Facebook\FacebookReferralDriver;

class FacebookReferralDriverTest extends PHPUnit_Framework_TestCase
{
    private function getDriver($responseData, array $config = ['facebook_token' => 'Foo'], $signature = '')
    {
        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn($responseData);
        $request->headers->set('X_HUB_SIGNATURE', $signature);

        return new FacebookReferralDriver($request, $config, new Curl());
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver('');
        $this->assertSame('FacebookReferral', $driver->getName());
    }

    /** @test */
    public function it_matches_the_request()
    {
        $request = '{}';
        $driver = $this->getDriver($request);
        $this->assertFalse($driver->matchesRequest());

        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"referral":{"ref":"MY_REF","source": "MY_SOURCE","type": "MY_TYPE"}}]}]}';
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
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"referral":{"ref":"MY_REF","source": "MY_SOURCE","type": "MY_TYPE"}}]}]}';
        $driver = $this->getDriver($request);
        $this->assertSame('MY_REF', $driver->getMessages()[0]->getMessage());
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
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"referral":{"ref":"MY_REF","source": "MY_SOURCE","type": "MY_TYPE"}}]}]}';
        $driver = $this->getDriver($request);

        $this->assertSame('111899832631525', $driver->getMessages()[0]->getUser());
    }

    /** @test */
    public function it_returns_the_channel_id()
    {
        $request = '{"object":"page","entry":[{"id":"111899832631525","time":1480279487271,"messaging":[{"sender":{"id":"1433960459967306"},"recipient":{"id":"111899832631525"},"timestamp":1480279487147,"referral":{"ref":"MY_REF","source": "MY_SOURCE","type": "MY_TYPE"}}]}]}';
        $driver = $this->getDriver($request);

        $this->assertSame('1433960459967306', $driver->getMessages()[0]->getChannel());
    }

    /** @test */
    public function it_is_configured()
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn('');
        $htmlInterface = m::mock(Curl::class);

        $driver = new FacebookReferralDriver($request, [
            'facebook_token' => 'token',
        ], $htmlInterface);

        $this->assertFalse($driver->isConfigured());

        $driver = new FacebookReferralDriver($request, [
            'facebook_token' => null,
        ], $htmlInterface);

        $this->assertFalse($driver->isConfigured());

        $driver = new FacebookReferralDriver($request, [], $htmlInterface);

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

        $driver = new FacebookReferralDriver($request, [
            'facebook_token' => 'Foo',
        ], $html);

        $message = new Message('', '', '1234567890');
        $driver->sendPayload($driver->buildServicePayload('Test', $message));
    }
}
