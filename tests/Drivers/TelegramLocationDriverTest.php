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
use Mpociot\BotMan\Drivers\Telegram\TelegramLocationDriver;

class TelegramLocationDriverTest extends PHPUnit_Framework_TestCase
{
    private function getDriver($responseData, $htmlInterface = null)
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        return new TelegramLocationDriver($request, [], $htmlInterface);
    }

    private function getWorkingRequestData()
    {
        return [
            'update_id' => '1234567890',
            'message' => [
                'message_id' => '123',
                'from' => [
                    'id' => 'from_id',
                ],
                'chat' => [
                    'id' => 'chat_id',
                ],
                'location' => [
                    'latitude' => 41.123987123,
                    'longitude' => -122.123854248,
                ],
            ],
        ];
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver([]);
        $this->assertSame('TelegramLocation', $driver->getName());
    }

    /** @test */
    public function it_matches_the_request()
    {
        $driver = $this->getDriver([
            'update_id' => '1234567890',
            'message' => [
                'message_id' => '123',
                'from' => [
                    'id' => 'from_id',
                ],
                'chat' => [
                    'id' => 'chat_id',
                ],
                'text' => 'Hallo',
            ],
        ]);
        $this->assertFalse($driver->matchesRequest());

        $driver = $this->getDriver($this->getWorkingRequestData());
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
        $request = $this->getRequest($this->getWorkingRequestData());

        $botman = BotManFactory::create([], new ArrayCache(), $request);
        $this->assertInstanceOf(TelegramLocationDriver::class, $botman->getDriver());
    }

    /** @test */
    public function it_returns_the_message_object()
    {
        $driver = $this->getDriver($this->getWorkingRequestData());
        $messages = $driver->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertEquals(1, count($messages));
        $this->assertInstanceOf(IncomingMessage::class, $messages[0]);
    }

    /** @test */
    public function it_returns_the_location()
    {
        $driver = $this->getDriver($this->getWorkingRequestData());
        $messages = $driver->getMessages();
        $location = $messages[0]->getLocation();

        $this->assertInstanceOf(Location::class, $location);
        $this->assertSame(41.123987123, $location->getLatitude());
        $this->assertSame(-122.123854248, $location->getLongitude());
        $this->assertSame([
            'latitude' => 41.123987123,
            'longitude' => -122.123854248,
        ], $location->getPayload());
    }
}
