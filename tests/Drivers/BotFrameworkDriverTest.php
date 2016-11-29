<?php

namespace Mpociot\BotMan\Tests\Drivers;

use Mockery as m;
use Mpociot\BotMan\Drivers\BotFrameworkDriver;
use Mpociot\BotMan\Http\Curl;
use Mpociot\BotMan\Message;
use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpFoundation\Request;

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
        ]);
        $this->assertTrue($driver->matchesRequest());
    }

    /** @test */
    public function it_returns_the_message_object()
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
            'text' => 'hey there',
            'entities' => [],
        ]);
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
    public function it_detects_bots()
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
            'text' => 'hey there',
            'entities' => [],
        ]);
        $this->assertFalse($driver->isBot());
    }

    /** @test */
    public function it_returns_the_user_id()
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
            'text' => 'hey there',
            'entities' => [],
        ]);
        $this->assertSame('29:1zPNq1EP2_H-mik_1MQgKYp0nZu9tUljr2VEdTlGhEo7VlZ1YVDVSUZ0g70sk1', $driver->getMessages()[0]->getUser());
    }

    /** @test */
    public function it_returns_the_channel_id()
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
            'text' => 'hey there',
            'entities' => [],
        ]);
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
}
