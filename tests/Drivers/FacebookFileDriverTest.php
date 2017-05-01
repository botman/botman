<?php

namespace Mpociot\BotMan\Tests\Drivers;

use Mockery as m;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\BotManFactory;
use Mpociot\BotMan\Cache\ArrayCache;
use Symfony\Component\HttpFoundation\Request;
use Mpociot\BotMan\Drivers\Facebook\FacebookFileDriver;

class FacebookFileDriverTest extends PHPUnit_Framework_TestCase
{
    /**
     * Get correct Facebook request data for audio.
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
                                        'type' => 'file',
                                        'payload' => [
                                            'url' => 'http://facebookfile.com/file.pdf',
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

    private function getRequest($responseData)
    {
        $request = m::mock(\Illuminate\Http\Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn(json_encode($responseData));

        return $request;
    }

    private function getDriver($responseData, $htmlInterface = null)
    {
        $request = $this->getRequest($responseData);
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        return new FacebookFileDriver($request, [], $htmlInterface);
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver([]);
        $this->assertSame('FacebookFile', $driver->getName());
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
                                        'type' => 'audio',
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

    /** @test */
    public function it_matches_the_request_using_the_driver_manager()
    {
        $request = $this->getRequest($this->getCorrectRequestData());

        $botman = BotManFactory::create([], new ArrayCache(), $request);
        $this->assertInstanceOf(FacebookFileDriver::class, $botman->getDriver());
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
        $this->assertInstanceOf(Message::class, $messages[0]);
    }

    /**
     * @test
     **/
    public function it_returns_file_from_request()
    {
        $driver = $this->getDriver($this->getCorrectRequestData());
        $messages = $driver->getMessages()[0];
        $files = $messages->getFiles();

        $this->assertTrue(is_array($files));
        $this->assertEquals('http://facebookfile.com/file.pdf', $files[0]->getUrl());
        $this->assertEquals([
                    'url' => 'http://facebookfile.com/file.pdf',
            ], $files[0]->getPayload());
    }
}
