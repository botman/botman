<?php

namespace Mpociot\BotMan\tests\Drivers;

use Mockery as m;
use Mpociot\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\BotManFactory;
use Mpociot\BotMan\Cache\ArrayCache;
use Symfony\Component\HttpFoundation\Request;
use Mpociot\BotMan\Messages\Attachments\Location;
use Mpociot\BotMan\Messages\Incoming\IncomingMessage;
use Mpociot\BotMan\Drivers\Facebook\FacebookLocationDriver;

class FacebookLocationDriverTest extends PHPUnit_Framework_TestCase
{
    /**
     * Get correct Facebook request data for location.
     *
     * @return array
     */
    private function getCorrectRequestData()
    {
        return [
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'PAGE_ID',
                    'time' => 1472672934319,
                    'messaging' => [
                        [
                            'sender' => [
                                'id' => 'USER_ID',
                            ],
                            'recipient' => [
                                'id' => 'PAGE_ID',
                            ],
                            'timestamp' => 1472672934259,
                            'message' => [
                                'mid' => 'mid.1472672934017:db566db5104b5b5c08',
                                'seq' => 297,
                                'attachments' => [
                                    [
                                        'type' => 'location',
                                        'payload' => [
                                            'coordinates' => [
                                                'lat' => 37.483872693672,
                                                'long' => -122.14900441942,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getDriver($responseData, $htmlInterface = null)
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        return new FacebookLocationDriver($request, [], $htmlInterface);
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver([]);
        $this->assertSame('FacebookLocation', $driver->getName());
    }

    /**
     * @test
     **/
    public function it_matches_the_request()
    {
        $driver = $this->getDriver([
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'PAGE_ID',
                    'time' => 1472672934319,
                    'messaging' => [
                        [
                            'sender' => [
                                'id' => 'USER_ID',
                            ],
                            'recipient' => [
                                'id' => 'PAGE_ID',
                            ],
                            'timestamp' => 1472672934259,
                            'message' => [
                                'mid' => 'mid.1472672934017:db566db5104b5b5c08',
                                'seq' => 297,
                                'attachments' => [
                                    [
                                        'type' => 'attachment',
                                        'payload' => [
                                            'url' => 'http://facebookattachmenturl.com',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertFalse($driver->matchesRequest());

        $driver = $this->getDriver($this->getCorrectRequestData());
        $this->assertTrue($driver->matchesRequest());
    }

    private function getRequest($responseData)
    {
        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        return $request;
    }

    /** @test */
    public function it_matches_the_request_using_the_driver_manager()
    {
        $request = $this->getRequest($this->getCorrectRequestData());

        $botman = BotManFactory::create([], new ArrayCache(), $request);
        $this->assertInstanceOf(FacebookLocationDriver::class, $botman->getDriver());
    }

    /**
     * @test
     **/
    public function it_returns_the_message_object()
    {
        $driver = $this->getDriver($this->getCorrectRequestData());
        $messages = $driver->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertEquals(1, count($messages));
        $this->assertInstanceOf(IncomingMessage::class, $messages[0]);
    }

    /**
     * @test
     **/
    public function it_returns_location_from_request()
    {
        $driver = $this->getDriver($this->getCorrectRequestData());
        $messages = $driver->getMessages();
        $location = $messages[0]->getLocation();

        $this->assertInstanceOf(Location::class, $location);
        $this->assertSame(37.483872693672, $location->getLatitude());
        $this->assertSame(-122.14900441942, $location->getLongitude());
        $this->assertEquals([
            'coordinates' => [
                'lat' => 37.483872693672,
                'long' => -122.14900441942,
            ],
        ], $location->getPayload());
    }
}
