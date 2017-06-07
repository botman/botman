<?php

namespace BotMan\BotMan\tests\Drivers;

use Mockery as m;
use BotMan\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Cache\ArrayCache;
use Symfony\Component\HttpFoundation\Request;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Drivers\BotFramework\BotFrameworkImageDriver;

class BotFrameworkImageDriverTest extends PHPUnit_Framework_TestCase
{
    private function getDriver($responseData, $htmlInterface = null)
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        return new BotFrameworkImageDriver($request, [], $htmlInterface);
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
            'attachments' => [
                [
                    'contentType' => 'image',
                    'contentUrl' => 'http://foo.bar/baz.png',
                    'name' => 'baz.png',
                ],
            ],
            'entities' => [],
        ];
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver([]);
        $this->assertSame('BotFrameworkImage', $driver->getName());
    }

    /** @test */
    public function it_matches_the_request()
    {
        $driver = $this->getDriver([
            'attachments' => [],
        ]);
        $this->assertFalse($driver->matchesRequest());

        $driver = $this->getDriver($this->getResponseData());
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
        $request = $this->getRequest($this->getResponseData());

        $botman = BotManFactory::create([], new ArrayCache(), $request);
        $this->assertInstanceOf(BotFrameworkImageDriver::class, $botman->getDriver());
    }

    /** @test */
    public function it_returns_the_message_object()
    {
        $driver = $this->getDriver($this->getResponseData());
        $this->assertTrue(is_array($driver->getMessages()));
    }

    /** @test */
    public function it_returns_the_image()
    {
        $driver = $this->getDriver($this->getResponseData());
        $message = $driver->getMessages()[0];
        $this->assertSame(Image::PATTERN, $message->getText());

        $image = $message->getImages()[0];
        $this->assertSame('http://foo.bar/baz.png', $image->getUrl());
        $this->assertSame([
            'contentType' => 'image',
            'contentUrl' => 'http://foo.bar/baz.png',
            'name' => 'baz.png',
        ], $image->getPayload());
    }
}
