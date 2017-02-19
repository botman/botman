<?php

namespace Mpociot\BotMan\Tests\Drivers;

use Mockery as m;
use Mpociot\BotMan\Button;
use Mpociot\BotMan\Question;
use Mpociot\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Drivers\WeChatDriver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WeChatDriverTest extends PHPUnit_Framework_TestCase
{
    private function getDriver($xmlData, $htmlInterface = null)
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn($xmlData);
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        return new WeChatDriver($request, [], $htmlInterface);
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver([]);
        $this->assertSame('WeChat', $driver->getName());
    }

    /** @test */
    public function it_matches_the_request()
    {
        $driver = $this->getDriver('foo');
        $this->assertFalse($driver->matchesRequest());

        $driver = $this->getDriver('<xml><ToUserName><![CDATA[to_user_name]]></ToUserName>
<FromUserName><![CDATA[from_user_name]]></FromUserName>
<CreateTime>1483534197</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[foo]]></Content>
<MsgId>1234567890</MsgId>
</xml>');
        $this->assertTrue($driver->matchesRequest());
    }

    /** @test */
    public function it_returns_the_message_object()
    {
        $driver = $this->getDriver('<xml><ToUserName><![CDATA[to_user_name]]></ToUserName>
<FromUserName><![CDATA[from_user_name]]></FromUserName>
<CreateTime>1483534197</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[foo]]></Content>
<MsgId>1234567890</MsgId>
</xml>');
        $this->assertTrue(is_array($driver->getMessages()));
    }

    /** @test */
    public function it_returns_the_message_text()
    {
        $driver = $this->getDriver('<xml><ToUserName><![CDATA[to_user_name]]></ToUserName>
<FromUserName><![CDATA[from_user_name]]></FromUserName>
<CreateTime>1483534197</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[Hi Julia]]></Content>
<MsgId>1234567890</MsgId>
</xml>');
        $this->assertSame('Hi Julia', $driver->getMessages()[0]->getMessage());
    }

    /** @test */
    public function it_detects_bots()
    {
        $driver = $this->getDriver('<xml><ToUserName><![CDATA[to_user_name]]></ToUserName>
<FromUserName><![CDATA[from_user_name]]></FromUserName>
<CreateTime>1483534197</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[foo]]></Content>
<MsgId>1234567890</MsgId>
</xml>');
        $this->assertFalse($driver->isBot());
    }

    /** @test */
    public function it_returns_the_user_id()
    {
        $driver = $this->getDriver('<xml><ToUserName><![CDATA[to_user_name]]></ToUserName>
<FromUserName><![CDATA[from_user_name]]></FromUserName>
<CreateTime>1483534197</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[foo]]></Content>
<MsgId>1234567890</MsgId>
</xml>');
        $this->assertSame('to_user_name', $driver->getMessages()[0]->getUser());
    }

    /** @test */
    public function it_returns_the_channel_id()
    {
        $driver = $this->getDriver('<xml><ToUserName><![CDATA[to_user_name]]></ToUserName>
<FromUserName><![CDATA[from_user_name]]></FromUserName>
<CreateTime>1483534197</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[foo]]></Content>
<MsgId>1234567890</MsgId>
</xml>');
        $this->assertSame('from_user_name', $driver->getMessages()[0]->getChannel());
    }

    /** @test */
    public function it_can_reply_string_messages()
    {
        $xmlData = '<xml><ToUserName><![CDATA[to_user_name]]></ToUserName>
<FromUserName><![CDATA[from_user_name]]></FromUserName>
<CreateTime>1483534197</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[foo]]></Content>
<MsgId>1234567890</MsgId>
</xml>';

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://api.wechat.com/cgi-bin/token?grant_type=client_credential&appid=WECHAT-APP-ID&secret=WECHAT-APP-KEY', [], [])
            ->andReturn(new Response(json_encode([
                'access_token' => 'SECRET_TOKEN',
            ])));

        $html->shouldReceive('post')
            ->once()
            ->with('https://api.wechat.com/cgi-bin/message/custom/send?access_token=SECRET_TOKEN', [], [
                'touser' => 'from_user_name',
                'msgtype' => 'text',
                'text' => [
                    'content' => 'Test',
                ],
            ], [], true);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn($xmlData);

        $driver = new WeChatDriver($request, [
            'wechat_app_id' => 'WECHAT-APP-ID',
            'wechat_app_key' => 'WECHAT-APP-KEY',
        ], $html);

        $message = $driver->getMessages()[0];
        $driver->reply('Test', $message);
    }

    /** @test */
    public function it_can_reply_questions()
    {
        $xmlData = '<xml><ToUserName><![CDATA[to_user_name]]></ToUserName>
<FromUserName><![CDATA[from_user_name]]></FromUserName>
<CreateTime>1483534197</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[foo]]></Content>
<MsgId>1234567890</MsgId>
</xml>';

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://api.wechat.com/cgi-bin/token?grant_type=client_credential&appid=WECHAT-APP-ID&secret=WECHAT-APP-KEY', [], [])
            ->andReturn(new Response(json_encode([
                'access_token' => 'SECRET_TOKEN',
            ])));

        $html->shouldReceive('post')
            ->once()
            ->with('https://api.wechat.com/cgi-bin/message/custom/send?access_token=SECRET_TOKEN', [], [
                'touser' => 'from_user_name',
                'msgtype' => 'text',
                'text' => [
                    'content' => 'How are you doing?',
                ],
            ], [], true);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn($xmlData);

        $question = Question::create('How are you doing?')
            ->addButton(Button::create('Great')->value('great'))
            ->addButton(Button::create('Good')->value('good'));

        $driver = new WeChatDriver($request, [
            'wechat_app_id' => 'WECHAT-APP-ID',
            'wechat_app_key' => 'WECHAT-APP-KEY',
        ], $html);

        $message = $driver->getMessages()[0];
        $driver->reply($question, $message);
    }

    /** @test */
    public function it_can_reply_with_additional_parameters()
    {
        $xmlData = '<xml><ToUserName><![CDATA[to_user_name]]></ToUserName>
<FromUserName><![CDATA[from_user_name]]></FromUserName>
<CreateTime>1483534197</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[foo]]></Content>
<MsgId>1234567890</MsgId>
</xml>';

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://api.wechat.com/cgi-bin/token?grant_type=client_credential&appid=WECHAT-APP-ID&secret=WECHAT-APP-KEY', [], [])
            ->andReturn(new Response(json_encode([
                'access_token' => 'SECRET_TOKEN',
            ])));

        $html->shouldReceive('post')
            ->once()
            ->with('https://api.wechat.com/cgi-bin/message/custom/send?access_token=SECRET_TOKEN', [], [
                'touser' => 'from_user_name',
                'msgtype' => 'text',
                'text' => [
                    'content' => 'Test',
                ],
                'foo' => 'bar',
            ], [], true);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn($xmlData);

        $driver = new WeChatDriver($request, [
            'wechat_app_id' => 'WECHAT-APP-ID',
            'wechat_app_key' => 'WECHAT-APP-KEY',
        ], $html);

        $message = $driver->getMessages()[0];
        $driver->reply('Test', $message, [
            'foo' => 'bar',
        ]);
    }

    /** @test */
    public function it_is_configured()
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn('');
        $htmlInterface = m::mock(Curl::class);

        $driver = new WeChatDriver($request, [
            'wechat_app_id' => 'WECHAT-APP-ID',
            'wechat_app_key' => 'WECHAT-APP-KEY',
        ], $htmlInterface);

        $this->assertTrue($driver->isConfigured());

        $driver = new WeChatDriver($request, [
            'wechat_app_id' => null,
            'wechat_app_key' => null,
        ], $htmlInterface);

        $this->assertFalse($driver->isConfigured());

        $driver = new WeChatDriver($request, [], $htmlInterface);

        $this->assertFalse($driver->isConfigured());
    }

    /** @test */
    public function it_can_reply_message_objects()
    {
        $xmlData = '<xml><ToUserName><![CDATA[to_user_name]]></ToUserName>
<FromUserName><![CDATA[from_user_name]]></FromUserName>
<CreateTime>1483534197</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[foo]]></Content>
<MsgId>1234567890</MsgId>
</xml>';

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://api.wechat.com/cgi-bin/token?grant_type=client_credential&appid=WECHAT-APP-ID&secret=WECHAT-APP-KEY', [], [])
            ->andReturn(new Response(json_encode([
                'access_token' => 'SECRET_TOKEN',
            ])));

        $html->shouldReceive('post')
            ->once()
            ->with('https://api.wechat.com/cgi-bin/message/custom/send?access_token=SECRET_TOKEN', [], [
                'touser' => 'from_user_name',
                'msgtype' => 'news',
                'news' => [
                    'articles' => [
                        [
                            'title' => 'Test',
                            'picurl' => null,
                        ],
                    ],
                ],
            ], [], true);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn($xmlData);

        $driver = new WeChatDriver($request, [
            'wechat_app_id' => 'WECHAT-APP-ID',
            'wechat_app_key' => 'WECHAT-APP-KEY',
        ], $html);

        $message = $driver->getMessages()[0];
        $driver->reply(\Mpociot\BotMan\Messages\Message::create('Test'), $message);
    }

    /** @test */
    public function it_can_reply_message_objects_with_image()
    {
        $xmlData = '<xml><ToUserName><![CDATA[to_user_name]]></ToUserName>
<FromUserName><![CDATA[from_user_name]]></FromUserName>
<CreateTime>1483534197</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[foo]]></Content>
<MsgId>1234567890</MsgId>
</xml>';

        $html = m::mock(Curl::class);
        $html->shouldReceive('post')
            ->once()
            ->with('https://api.wechat.com/cgi-bin/token?grant_type=client_credential&appid=WECHAT-APP-ID&secret=WECHAT-APP-KEY', [], [])
            ->andReturn(new Response(json_encode([
                'access_token' => 'SECRET_TOKEN',
            ])));

        $html->shouldReceive('post')
            ->once()
            ->with('https://api.wechat.com/cgi-bin/message/custom/send?access_token=SECRET_TOKEN', [], [
                'touser' => 'from_user_name',
                'msgtype' => 'news',
                'news' => [
                    'articles' => [
                        [
                            'title' => 'Test',
                            'picurl' => 'http://image.url/foo.png',
                        ],
                    ],
                ],
            ], [], true);

        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn($xmlData);

        $driver = new WeChatDriver($request, [
            'wechat_app_id' => 'WECHAT-APP-ID',
            'wechat_app_key' => 'WECHAT-APP-KEY',
        ], $html);

        $message = $driver->getMessages()[0];
        $driver->reply(\Mpociot\BotMan\Messages\Message::create('Test', 'http://image.url/foo.png'), $message);
    }
}
