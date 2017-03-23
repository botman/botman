<?php

namespace Mpociot\BotMan\Tests\Drivers;

use Mockery as m;
use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Mpociot\BotMan\Drivers\TelegramVideoDriver;

class TelegramVideoDriverTest extends PHPUnit_Framework_TestCase
{
    private function getDriver($responseData, $htmlInterface = null)
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        return new TelegramVideoDriver($request, [], $htmlInterface);
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver([]);
        $this->assertSame('TelegramVideo', $driver->getName());
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
                'document' => [
                    'mime_type' => 'image/png',
                    'file_id' => 'AgADAgAD6KcxG4tSUUnK3tsu3YsxCu8VSw0ABO72aPxtHuGxcGMFAAEC',
                ],
            ],
        ]);
        $this->assertFalse($driver->matchesRequest());

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
                'video' => [
                    'mime_type' => 'video/quicktime',
                    'file_id' => 'AgADAgAD6KcxG4tSUUnK3tsu3YsxCu8VSw0ABO72aPxtHuGxcGMFAAEC',
                ],
            ],
        ]);
        $this->assertTrue($driver->matchesRequest());
    }

    /** @test */
    public function it_returns_the_message_object()
    {
        $response = new Response('{"result": {"file_path": "foo"}}');
        $htmlInterface = m::mock(Curl::class);
        $htmlInterface->shouldReceive('get')->with('https://api.telegram.org/bot/getFile', [
            'file_id' => 'AgADAgAD6KcxG4tSUUnK3tsu3YsxCu8VSw0ABO72aPxtHuGxcGMFAAEC',
        ])->andReturn($response);

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
                'video' => [
                    'mime_type' => 'video/quicktime',
                    'file_id' => 'AgADAgAD6KcxG4tSUUnK3tsu3YsxCu8VSw0ABO72aPxtHuGxcGMFAAEC',
                ],
            ],
        ], $htmlInterface);
        $this->assertTrue(is_array($driver->getMessages()));
    }

    /** @test */
    public function it_returns_the_video()
    {
        $response = new Response('{"result": {"file_path": "foo"}}');
        $htmlInterface = m::mock(Curl::class);
        $htmlInterface->shouldReceive('get')->with('https://api.telegram.org/bot/getFile', [
            'file_id' => 'AgADAgAD6KcxG4tSUUnK3tsu3YsxCu8VSw0ABO72aPxtHuGxcGMFAAEC',
        ])->andReturn($response);

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
                'video' => [
                    'mime_type' => 'video/quicktime',
                    'file_id' => 'AgADAgAD6KcxG4tSUUnK3tsu3YsxCu8VSw0ABO72aPxtHuGxcGMFAAEC',
                ],
            ],
        ], $htmlInterface);
        $message = $driver->getMessages()[0];
        $this->assertSame(BotMan::VIDEO_PATTERN, $message->getMessage());
        $this->assertSame(['https://api.telegram.org/file/bot/foo'], $message->getVideos());
    }
}
