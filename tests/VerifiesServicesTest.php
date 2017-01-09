<?php

namespace Mpociot\BotMan\Tests;

use Mockery as m;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Traits\VerifiesServices;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class VerifiesServicesTest.
 */
class VerifiesServicesTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_verify_facebook()
    {
        $data = [
            'hub_challenge' => 'facebook_hub_challenge',
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'facebook_token',
        ];
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')
            ->once()
            ->andReturn(json_encode($data));

        $verification = new VerifyServices();
        $verification->request = new Request($data);
        $response = $verification->verifyServices([
            'facebook' => 'facebook_token'
        ]);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('facebook_hub_challenge', $response->getContent());
    }

    /** @test */
    public function it_can_verify_facebook_the_old_way() // depricated
    {
        $data = [
            'hub_challenge' => 'facebook_hub_challenge',
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'facebook_token',
        ];
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')
            ->once()
            ->andReturn(json_encode($data));

        $verification = new VerifyServices();
        $verification->request = new Request($data);
        $response = $verification->verifyServices('facebook_token');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('facebook_hub_challenge', $response->getContent());
    }
    
    /** @test */
    public function it_can_verify_slack()
    {
        $data = [
            'type' => 'url_verification',
            'challenge' => 'slack_challenge',
            'token' => 'slack_token',
        ];
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')
            ->once()
            ->andReturn(json_encode($data));

        $verification = new VerifyServices();
        $verification->request = new Request($data);
        $response = $verification->verifyServices([
            'slack' => 'slack_token'
        ]);

        $this->assertSame('slack_challenge', $response->getContent());
    }
}

class VerifyServices
{
    use VerifiesServices;

    public $request;
}
