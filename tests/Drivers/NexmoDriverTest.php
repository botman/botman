<?php

namespace Mpociot\BotMan\Tests\Drivers;

use Mockery as m;
use Mpociot\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Drivers\NexmoDriver;
use Symfony\Component\HttpFoundation\Request;

class NexmoDriverTest extends PHPUnit_Framework_TestCase
{
    private function getDriver($responseData, $htmlInterface = null)
    {
        $request = Request::create('', 'POST', $responseData);
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        return new NexmoDriver($request, [], $htmlInterface);
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver([]);
        $this->assertSame('Nexmo', $driver->getName());
    }

    /** @test */
    public function it_matches_the_request()
    {
        $driver = $this->getDriver([
            'to' => '41766013098',
            'messageId' => '0C000000075069C7',
            'text' => 'Hi Julia',
            'type' => 'text',
            'keyword' => 'HEY',
            'message_timestamp' => '2016-11-30 19:27:46',
        ]);
        $this->assertFalse($driver->matchesRequest());

        $driver = $this->getDriver([
            'msisdn' => '491762012309022505',
            'to' => '4176260130298',
            'messageId' => '0C000000075069C7',
            'text' => 'Hi Julia',
            'type' => 'text',
            'keyword' => 'HEY',
            'message_timestamp' => '2016-11-30 19:27:46',
        ]);
        $this->assertTrue($driver->matchesRequest());
    }

    /** @test */
    public function it_returns_the_message_object()
    {
        $driver = $this->getDriver([
            'msisdn' => '491762012309022505',
            'to' => '4176260130298',
            'messageId' => '0C000000075069C7',
            'text' => 'Hi Julia',
            'type' => 'text',
            'keyword' => 'HEY',
            'message_timestamp' => '2016-11-30 19:27:46',
        ]);
        $this->assertTrue(is_array($driver->getMessages()));
    }

    /** @test */
    public function it_returns_the_message_text()
    {
        $driver = $this->getDriver([
            'msisdn' => '491762012309022505',
            'to' => '4176260130298',
            'messageId' => '0C000000075069C7',
            'text' => 'Hi Julia',
            'type' => 'text',
            'keyword' => 'HEY',
            'message_timestamp' => '2016-11-30 19:27:46',
        ]);
        $this->assertSame('Hi Julia', $driver->getMessages()[0]->getMessage());
    }

    /** @test */
    public function it_detects_bots()
    {
        $driver = $this->getDriver([
            'msisdn' => '491762012309022505',
            'to' => '4176260130298',
            'messageId' => '0C000000075069C7',
            'text' => 'Hey there',
            'type' => 'text',
            'keyword' => 'HEY',
            'message_timestamp' => '2016-11-30 19:27:46',
        ]);
        $this->assertFalse($driver->isBot());
    }

    /** @test */
    public function it_returns_the_user_id()
    {
        $driver = $this->getDriver([
            'msisdn' => '491762012309022505',
            'to' => '4176260130298',
            'messageId' => '0C000000075069C7',
            'text' => 'Hey there',
            'type' => 'text',
            'keyword' => 'HEY',
            'message_timestamp' => '2016-11-30 19:27:46',
        ]);
        $this->assertSame('4176260130298', $driver->getMessages()[0]->getUser());
    }

    /** @test */
    public function it_returns_the_channel_id()
    {
        $driver = $this->getDriver([
            'msisdn' => '491762012309022505',
            'to' => '4176260130298',
            'messageId' => '0C000000075069C7',
            'text' => 'Hi Julia',
            'type' => 'text',
            'keyword' => 'HEY',
            'message_timestamp' => '2016-11-30 19:27:46',
        ]);
        $this->assertSame('491762012309022505', $driver->getMessages()[0]->getChannel());
    }

    /** @test */
    public function it_returns_the_user_object()
    {
        $driver = $this->getDriver([
            'msisdn' => '491762012309022505',
            'to' => '4176260130298',
            'messageId' => '0C000000075069C7',
            'text' => 'Hi Julia',
            'type' => 'text',
            'keyword' => 'HEY',
            'message_timestamp' => '2016-11-30 19:27:46',
        ]);

        $message = $driver->getMessages()[0];
        $user = $driver->getUser($message);

        $this->assertSame($user->getId(), '491762012309022505');
        $this->assertNull($user->getFirstName());
        $this->assertNull($user->getLastName());
        $this->assertNull($user->getUsername());
    }

    /** @test */
    public function it_is_configured()
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn('');
        $htmlInterface = m::mock(Curl::class);

        $driver = new NexmoDriver($request, [
            'nexmo_key' => 'key',
            'nexmo_secret' => 'secret',
        ], $htmlInterface);

        $this->assertTrue($driver->isConfigured());

        $driver = new NexmoDriver($request, [
            'nexmo_key' => null,
            'nexmo_secret' => null,
        ], $htmlInterface);

        $this->assertFalse($driver->isConfigured());

        $driver = new NexmoDriver($request, [], $htmlInterface);

        $this->assertFalse($driver->isConfigured());
    }
}
