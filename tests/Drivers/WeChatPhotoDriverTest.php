<?php

namespace BotMan\BotMan\tests\Drivers;

use Mockery as m;
use BotMan\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use BotMan\BotMan\Messages\Attachments\Image;
use Symfony\Component\HttpFoundation\Request;
use BotMan\BotMan\Drivers\WeChat\WeChatPhotoDriver;

class WeChatPhotoDriverTest extends PHPUnit_Framework_TestCase
{
    /**
     * Valid WeChat image XML.
     * @var string
     */
    protected $validXml;

    public function setUp()
    {
        parent::setUp();

        $this->validXml = '<xml><ToUserName><![CDATA[to_user_name]]></ToUserName>
            <FromUserName><![CDATA[from_user_name]]></FromUserName>
            <CreateTime>1483534197</CreateTime>
            <MsgType><![CDATA[image]]></MsgType>
            <Content><![CDATA[foo]]></Content>
            <MsgId>1234567890</MsgId>
            <PicUrl>http://test.com/picurl</PicUrl>
            </xml>';
    }

    private function getDriver($xmlData, $htmlInterface = null)
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn($xmlData);
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        return new WeChatPhotoDriver($request, [], $htmlInterface);
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver([]);
        $this->assertSame('WeChatPhoto', $driver->getName());
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
    public function it_returns_the_image_pattern()
    {
        $driver = $this->getDriver($this->validXml);
        $this->assertSame('%%%_IMAGE_%%%', $driver->getMessages()[0]->getText());
    }

    /** @test */
    public function it_returns_the_image()
    {
        $driver = $this->getDriver($this->validXml);
        $message = $driver->getMessages()[0];
        $this->assertSame(Image::PATTERN, $message->getText());
        $this->assertSame('http://test.com/picurl', $message->getImages()[0]->getUrl());
        $this->assertSame($message->getPayload(), $message->getImages()[0]->getPayload());
    }
}
