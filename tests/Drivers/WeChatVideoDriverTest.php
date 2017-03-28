<?php

namespace Mpociot\BotMan\Tests\Drivers;

use Mockery as m;
use Mpociot\BotMan\BotMan;
use Illuminate\Http\Response;
use Mpociot\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Drivers\WeChatVideoDriver;
use Symfony\Component\HttpFoundation\Request;

class WeChatVideoDriverTest extends PHPUnit_Framework_TestCase
{
    /**
     * Valid WeChat video XML.
     * @var string
     */
    protected $validXml;

    /**
     * Invalid WeChat video XML.
     * @var string
     */
    protected $invalidXml;

    public function setUp()
    {
        parent::setUp();

        $this->validXml = '<xml><ToUserName><![CDATA[to_user_name]]></ToUserName>
            <FromUserName><![CDATA[from_user_name]]></FromUserName>
            <CreateTime>1483534197</CreateTime>
            <MsgType><![CDATA[video]]></MsgType>
            <Content><![CDATA[foo]]></Content>
            <MsgId>1234567890</MsgId>
            <MediaId>12345</MediaId>
            </xml>';

        $this->invalidXml = '<xml><ToUserName><![CDATA[to_user_name]]></ToUserName>
            <FromUserName><![CDATA[from_user_name]]></FromUserName>
            <CreateTime>1483534197</CreateTime>
            <MsgType><![CDATA[photo]]></MsgType>
            <Content><![CDATA[foo]]></Content>
            <MsgId>1234567890</MsgId>
            </xml>';
    }

    public function tearDown()
    {
        m::close();
    }

    private function getDriver($xmlData, $htmlInterface = null)
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn($xmlData);
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        return new WeChatVideoDriver($request, [], $htmlInterface);
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver([]);
        $this->assertSame('WeChatVideo', $driver->getName());
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
    public function it_returns_the_video_pattern()
    {
        $html = m::mock(Curl::class);
        $html->shouldReceive('post')->once()->with('https://api.wechat.com/cgi-bin/token?grant_type=client_credential&appid=WECHAT-APP-ID&secret=WECHAT-APP-KEY',
                [], [])->andReturn(new Response(json_encode([
                'access_token' => 'SECRET_TOKEN',
            ])));

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn($this->validXml);

        $driver = new WeChatVideoDriver($request, [
            'wechat_app_id' => 'WECHAT-APP-ID',
            'wechat_app_key' => 'WECHAT-APP-KEY',
        ], $html);

        $messages = $driver->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertEquals('%%%_VIDEO_%%%', $messages[0]->getMessage());
    }

    /** @test */
    public function it_returns_the_video()
    {
        $html = m::mock(Curl::class);
        $html->shouldReceive('post')->once()->with('https://api.wechat.com/cgi-bin/token?grant_type=client_credential&appid=WECHAT-APP-ID&secret=WECHAT-APP-KEY',
                [], [])->andReturn(new Response(json_encode([
                'access_token' => 'SECRET_TOKEN',
            ])));

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn($this->validXml);

        $driver = new WeChatVideoDriver($request, [
            'wechat_app_id' => 'WECHAT-APP-ID',
            'wechat_app_key' => 'WECHAT-APP-KEY',
        ], $html);

        $message = $driver->getMessages()[0];
        $this->assertSame(BotMan::VIDEO_PATTERN, $message->getMessage());
        $this->assertSame(['http://file.api.wechat.com/cgi-bin/media/get?access_token=SECRET_TOKEN&media_id=12345'],
            $message->getVideos());
    }
}
