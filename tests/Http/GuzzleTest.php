<?php

namespace BotMan\BotMan\Tests\Http;

use BotMan\BotMan\Http\Guzzle;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;

class GuzzleTest extends TestCase
{
    /**
     * @var MockHandler
     */
    private $mockHandler;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Guzzle
     */
    private $guzzle;

    public function setUp()
    {
        $this->mockHandler = new MockHandler();
        $this->client = new Client(['handler' => $this->mockHandler]);
        $this->guzzle = new Guzzle($this->client, new HttpFoundationFactory());
    }

    /** @test */
    public function it_can_send_get_request_with_headers_and_query_string()
    {
        $body = '{
          "args": {
            "param1": "1", 
            "param2": "2"
          }, 
          "headers": {
            "Accept": "application/json", 
            "Connection": "close", 
            "Host": "httpbin.org", 
            "User-Agent": "GuzzleHttp/6.3.3 curl/7.47.0 PHP/7.1.18-1+ubuntu16.04.1+deb.sury.org+1"
          },
          "url": "http://httpbin.org/get?param1=1&param2=2"
        }';
        $this->mockHandler->append(new Response(200, [], $body));

        $headers = ['Accept' => 'application/json'];
        $urlParameters = ['param1' => 1, 'param2' => 2];
        $response = $this->guzzle->get('http://httpbin.org/get', $urlParameters, $headers);

        $responseArray = json_decode($response->getContent(), true);
        $this->assertEquals('application/json', $responseArray['headers']['Accept']);
        $this->assertEquals(1, $responseArray['args']['param1']);
        $this->assertEquals(2, $responseArray['args']['param2']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_does_not_throw_on_errors()
    {
        $this->mockHandler->append(new Response(404, [], 'Not found'));
        $this->mockHandler->append(new Response(500, [], 'Internal error'));

        $response = $this->guzzle->get('http://httpbin.org/status/404');
        $this->assertEquals(404, $response->getStatusCode());

        $response = $this->guzzle->get('http://httpbin.org/status/500');
        $this->assertEquals(500, $response->getStatusCode());
    }

    /** @test */
    public function it_can_send_post_request()
    {
        $body = '{
          "args": {
            "get_param": "1"
          }, 
          "data": "", 
          "files": {}, 
          "form": {
            "post_param": "2"
          }, 
          "headers": {
            "Accept": "application/json", 
            "Connection": "close", 
            "Content-Length": "12", 
            "Content-Type": "application/x-www-form-urlencoded", 
            "Host": "httpbin.org", 
            "User-Agent": "GuzzleHttp/6.3.3 curl/7.47.0 PHP/7.1.18-1+ubuntu16.04.1+deb.sury.org+1"
          }, 
          "url": "http://httpbin.org/post?get_param=1"
        }';
        $this->mockHandler->append(new Response(200, [], $body));

        $headers = ['Accept' => 'application/json'];
        $urlParameters = ['get_param' => 1];
        $postParameters = ['post_param' => 2];
        $response = $this->guzzle->post('http://httpbin.org/post', $urlParameters, $postParameters, $headers);

        $responseArray = json_decode($response->getContent(), true);
        $this->assertEquals('application/json', $responseArray['headers']['Accept']);
        $this->assertEquals(1, $responseArray['args']['get_param']);
        $this->assertEquals(2, $responseArray['form']['post_param']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_can_send_post_request_as_json()
    {
        $body = '{
          "args": {
            "get_param": "1"
          }, 
          "data": "{\"post_param\":2}", 
          "files": {}, 
          "form": {}, 
          "headers": {
            "Accept": "application/json", 
            "Connection": "close", 
            "Content-Length": "16", 
            "Content-Type": "application/json", 
            "Host": "httpbin.org", 
            "User-Agent": "GuzzleHttp/6.3.3 curl/7.47.0 PHP/7.1.18-1+ubuntu16.04.1+deb.sury.org+1"
          }, 
          "json": {
            "post_param": 2
          }, 
          "url": "http://httpbin.org/post?get_param=1"
        }';
        $this->mockHandler->append(new Response(200, [], $body));

        $headers = ['Accept' => 'application/json'];
        $urlParameters = ['get_param' => 1];
        $postParameters = ['post_param' => 2];
        $asJson = true;
        $response = $this->guzzle->post('http://httpbin.org/post', $urlParameters, $postParameters, $headers, $asJson);

        $responseArray = json_decode($response->getContent(), true);
        $this->assertEquals('application/json', $responseArray['headers']['Accept']);
        $this->assertEquals(1, $responseArray['args']['get_param']);
        $this->assertEquals(2, $responseArray['json']['post_param']);
        $this->assertEmpty($responseArray['form']);
        $this->assertEquals(200, $response->getStatusCode());
    }
}