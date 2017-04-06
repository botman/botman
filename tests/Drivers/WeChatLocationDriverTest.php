<?php

namespace Mpociot\BotMan\Tests\Drivers;

use Mockery as m;
use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Attachments\Location;
use Symfony\Component\HttpFoundation\Request;
use Mpociot\BotMan\Drivers\WeChatLocationDriver;

class WeChatLocationDriverTest extends PHPUnit_Framework_TestCase
{
    /**
     * Valid WeChat location XML.
     * @var string
     */
    protected $validXml;

    public function setUp()
    {
        parent::setUp();

        $this->validXml = '<xml><ToUserName><![CDATA[to_user_name]]></ToUserName>
            <FromUserName><![CDATA[from_user_name]]></FromUserName>
            <CreateTime>1483534197</CreateTime>
            <MsgType><![CDATA[location]]></MsgType>
            <PicUrl><![CDATA[http://test.com/picurl]]></PicUrl>
            <Content><![CDATA[Hi Julia]]></Content>
            <Location_X><![CDATA[40.7]]></Location_X>
            <Location_Y><![CDATA[-74.1]]></Location_Y>
            <MsgId>1234567890</MsgId>
            </xml>';
    }

    private function getDriver($xmlData, $htmlInterface = null)
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn($xmlData);
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        return new WeChatLocationDriver($request, [], $htmlInterface);
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver([]);
        $this->assertSame('WeChatLocation', $driver->getName());
    }

    /** @test */
    public function it_matches_the_request()
    {
        $driver = $this->getDriver('foo');
        $this->assertFalse($driver->matchesRequest());

        $driver = $this->getDriver($this->validXml);
        $this->assertTrue($driver->matchesRequest());
    }

    /** @test */
    public function it_returns_the_message_object()
    {
        $driver = $this->getDriver($this->validXml);
        $this->assertTrue(is_array($driver->getMessages()));
    }

    /** @test */
    public function it_returns_the_location_pattern()
    {
        $driver = $this->getDriver($this->validXml);
        $this->assertSame('%%%_LOCATION_%%%', $driver->getMessages()[0]->getMessage());
    }

    /** @test */
    public function it_returns_the_location()
    {
        $driver = $this->getDriver($this->validXml);
        $message = $driver->getMessages()[0];
        $this->assertSame(BotMan::LOCATION_PATTERN, $message->getMessage());
        $this->assertInstanceOf(Location::class, $message->getLocation());
        $this->assertEquals('40.7', $message->getLocation()->getLatitude());
        $this->assertEquals('-74.1', $message->getLocation()->getLongitude());
    }
}
