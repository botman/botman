<?php

namespace Mpociot\BotMan\Tests\Drivers;

use Mockery as m;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Drivers\ApiDriver;
use Symfony\Component\HttpFoundation\Request;

class ApiDriverTest extends PHPUnit_Framework_TestCase
{
    private function getDriver($responseData, $htmlInterface = null)
    {
        $request = Request::create('', 'POST', $responseData);
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        return new ApiDriver($request, [], $htmlInterface);
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver([]);
        $this->assertSame('Api', $driver->getName());
    }

    /** @test */
    public function it_matches_the_request()
    {
        $driver = $this->getDriver([
            'driver' => 'web',
            'message' => 'Hi Julia',
            'userId' => '12345'
        ]);
        $this->assertFalse($driver->matchesRequest());

        $driver = $this->getDriver([
            'driver' => 'api',
            'message' => 'Hi Julia',
            'userId' => '12345'
        ]);
        $this->assertTrue($driver->matchesRequest());
    }

    /** @test */
    public function it_returns_the_message_object()
    {
        $driver = $this->getDriver([
            'driver' => 'web',
            'message' => 'Hi Julia',
            'userId' => '12345'
        ]);
        $this->assertTrue(is_array($driver->getMessages()));
    }

    /** @test */
    public function it_returns_the_message_text()
    {
        $driver = $this->getDriver([
            'driver' => 'web',
            'message' => 'Hi Julia',
            'userId' => '12345'
        ]);
        $this->assertSame('Hi Julia', $driver->getMessages()[0]->getMessage());
    }

    /** @test */
    public function it_returns_the_user_id()
    {
        $driver = $this->getDriver([
            'driver' => 'web',
            'message' => 'Hi Julia',
            'userId' => '12345'
        ]);
        $this->assertSame('12345', $driver->getMessages()[0]->getUser());
    }

    /** @test */
    public function it_can_reply_string_messages()
    {
        $driver = $this->getDriver([
            'driver' => 'web',
            'message' => 'Hi Julia',
            'userId' => '12345'
        ]);

        $message = new Message('', '', '1234567890');

        $driver->reply('Test one From API', $message);
        $driver->reply('Test two From API', $message);

        $this->expectOutputString('{"status":200,"messages":[{"type":"text","text":"Test one From API"},{"type":"text","text":"Test two From API"}]}');
    }
}