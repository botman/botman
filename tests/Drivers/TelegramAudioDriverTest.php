<?php

namespace Mpociot\BotMan\Tests\Drivers;

use Mockery as m;
use Mpociot\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\BotManFactory;
use Mpociot\BotMan\Cache\ArrayCache;
use Mpociot\BotMan\Attachments\Audio;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Mpociot\BotMan\Drivers\Telegram\TelegramAudioDriver;

class TelegramAudioDriverTest extends PHPUnit_Framework_TestCase
{
    private function getDriver($responseData, $htmlInterface = null)
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        return new TelegramAudioDriver($request, [], $htmlInterface);
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver([]);
        $this->assertSame('TelegramAudio', $driver->getName());
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
                'audio' => [
                    'mime_type' => 'audio/x-m4a"',
                    'file_id' => 'AgADAgAD6KcxG4tSUUnK3tsu3YsxCu8VSw0ABO72aPxtHuGxcGMFAAEC',
                ],
            ],
        ]);
        $this->assertTrue($driver->matchesRequest());

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
                'voice' => [
                    'mime_type' => 'audio/ogg',
                    'file_id' => 'AgADAgAD6KcxG4tSUUnK3tsu3YsxCu8VSw0ABO72aPxtHuGxcGMFAAEC',
                ],
            ],
        ]);
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
        $request = $this->getRequest([
            'update_id' => '1234567890',
            'message' => [
                'message_id' => '123',
                'from' => [
                    'id' => 'from_id',
                ],
                'chat' => [
                    'id' => 'chat_id',
                ],
                'voice' => [
                    'mime_type' => 'audio/ogg',
                    'file_id' => 'AgADAgAD6KcxG4tSUUnK3tsu3YsxCu8VSw0ABO72aPxtHuGxcGMFAAEC',
                ],
            ],
        ]);

        $botman = BotManFactory::create([], new ArrayCache(), $request);
        $this->assertInstanceOf(TelegramAudioDriver::class, $botman->getDriver());
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
                'audio' => [
                    'mime_type' => 'video/quicktime',
                    'file_id' => 'AgADAgAD6KcxG4tSUUnK3tsu3YsxCu8VSw0ABO72aPxtHuGxcGMFAAEC',
                ],
            ],
        ], $htmlInterface);
        $this->assertTrue(is_array($driver->getMessages()));
    }

    /** @test */
    public function it_returns_the_audio()
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
                'audio' => [
                    'mime_type' => 'audio/x-m4a"',
                    'file_id' => 'AgADAgAD6KcxG4tSUUnK3tsu3YsxCu8VSw0ABO72aPxtHuGxcGMFAAEC',
                ],
            ],
        ], $htmlInterface);
        $message = $driver->getMessages()[0];
        $this->assertSame(Audio::PATTERN, $message->getText());
        $this->assertSame('https://api.telegram.org/file/bot/foo', $message->getAudio()[0]->getUrl());
        $this->assertSame([
            'mime_type' => 'audio/x-m4a"',
            'file_id' => 'AgADAgAD6KcxG4tSUUnK3tsu3YsxCu8VSw0ABO72aPxtHuGxcGMFAAEC',
        ], $message->getAudio()[0]->getPayload());
    }

    /** @test */
    public function it_returns_the_audio_for_voices()
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
                'voice' => [
                    'mime_type' => 'audio/x-m4a"',
                    'file_id' => 'AgADAgAD6KcxG4tSUUnK3tsu3YsxCu8VSw0ABO72aPxtHuGxcGMFAAEC',
                ],
            ],
        ], $htmlInterface);
        $message = $driver->getMessages()[0];
        $this->assertSame(Audio::PATTERN, $message->getText());
        $this->assertSame('https://api.telegram.org/file/bot/foo', $message->getAudio()[0]->getUrl());
        $this->assertSame([
            'mime_type' => 'audio/x-m4a"',
            'file_id' => 'AgADAgAD6KcxG4tSUUnK3tsu3YsxCu8VSw0ABO72aPxtHuGxcGMFAAEC',
        ], $message->getAudio()[0]->getPayload());
    }
}
