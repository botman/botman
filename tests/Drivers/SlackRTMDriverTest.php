<?php

namespace Mpociot\BotMan\Tests\Drivers;

use Slack\File;
use Mockery as m;
use Slack\RealTimeClient;
use Mpociot\BotMan\Message;
use React\EventLoop\Factory;
use PHPUnit_Framework_TestCase;
use React\Promise\FulfilledPromise;
use Mpociot\BotMan\Drivers\SlackRTMDriver;
use Mpociot\BotMan\Messages\Message as IncomingMessage;

class SlackRTMDriverTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    private function getDriver($responseData = [], $htmlInterface = null)
    {
        $loop = Factory::create();
        $client = new RealTimeClient($loop);
        $driver = new SlackRTMDriver([], $client);
        $client->emit('message', [$responseData]);

        return $driver;
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver([]);
        $this->assertSame('SlackRTM', $driver->getName());
    }

    /** @test */
    public function it_matches_the_request()
    {
        $driver = $this->getDriver([]);
        $this->assertFalse($driver->matchesRequest());
    }

    /** @test */
    public function it_returns_the_message_object()
    {
        $driver = $this->getDriver([
            'user' => 'U0X12345',
            'text' => 'Hi Julia',
        ]);
        $this->assertTrue(is_array($driver->getMessages()));
    }

    /** @test */
    public function it_returns_the_message_text()
    {
        $driver = $this->getDriver([
            'user' => 'U0X12345',
            'text' => 'Hi Julia',
        ]);
        $this->assertSame('Hi Julia', $driver->getMessages()[0]->getMessage());
    }

    /** @test */
    public function it_detects_bots()
    {
        $driver = $this->getDriver([
            'user' => 'U0X12345',
            'text' => 'Hi Julia',
        ]);
        $this->assertFalse($driver->isBot());

        $driver = $this->getDriver([
            'user' => 'U0X12345',
            'bot_id' => 'foo',
            'text' => 'Hi Julia',
        ]);
        $this->assertTrue($driver->isBot());
    }

    /** @test */
    public function it_returns_the_user_id()
    {
        $driver = $this->getDriver([
            'user' => 'U0X12345',
        ]);
        $this->assertSame('U0X12345', $driver->getMessages()[0]->getUser());
    }

    /** @test */
    public function it_returns_the_channel_id()
    {
        $driver = $this->getDriver([
            'user' => 'U0X12345',
            'channel' => 'general',
        ]);
        $this->assertSame('general', $driver->getMessages()[0]->getChannel());
    }

    /** @test */
    public function it_calls_files_upload_api()
    {
        $filePath = __FILE__;

        $channelId = str_random();

        $loop = Factory::create();

        $client = new RealTimeClient($loop);

        $clientMock = m::mock($client);

        $clientMock->shouldReceive('fileUpload')
            ->with(m::on(function (File $file) use ($filePath) {
                return $file->getPath() === $filePath;
            }), [$channelId])
            ->once()
            ->andReturn(new FulfilledPromise([]));

        $driver = new SlackRTMDriver([], $clientMock);

        $message = IncomingMessage::create('File')
            ->filePath($filePath);

        $matchingMessage = new Message('A command', 'U0X12345', $channelId);

        $driver->reply($message, $matchingMessage);
    }
}
