<?php

namespace BotMan\BotMan\Tests;

use BotMan\BotMan\Drivers\HttpDriver;
use BotMan\BotMan\Interfaces\UserInterface;
use BotMan\BotMan\Interfaces\VerifiesService;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use PHPUnit_Framework_TestCase;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class VerifiesServicesTest.
 */
class VerifiesServicesTest extends PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_verify_drivers()
    {
        $this->assertFalse(isset($_SERVER['driver_verified']));

        DriverManager::loadDriver(DummyDriver::class);

        $botman = BotManFactory::create([]);
        $botman->listen();

        $this->assertTrue($_SERVER['driver_verified']);
    }
}

class DummyDriver extends HttpDriver implements VerifiesService
{

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return true;
    }

    /**
     * Retrieve the chat message(s).
     *
     * @return array
     */
    public function getMessages()
    {
        return [new IncomingMessage('', '', '')];
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return true;
    }

    /**
     * Retrieve User information.
     * @param IncomingMessage $matchingMessage
     * @return UserInterface
     */
    public function getUser(IncomingMessage $matchingMessage)
    {

    }

    /**
     * @param IncomingMessage $message
     * @return \BotMan\BotMan\Messages\Incoming\Answer
     */
    public function getConversationAnswer(IncomingMessage $message)
    {
        return new Answer('');
    }

    /**
     * @param string|\BotMan\BotMan\Messages\Outgoing\Question $message
     * @param IncomingMessage $matchingMessage
     * @param array $additionalParameters
     * @return $this
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {

    }

    /**
     * @param mixed $payload
     * @return Response
     */
    public function sendPayload($payload)
    {

    }

    /**
     * @param Request $request
     * @return void
     */
    public function buildPayload(Request $request)
    {

    }

    /**
     * Low-level method to perform driver specific API requests.
     *
     * @param string $endpoint
     * @param array $parameters
     * @param \BotMan\BotMan\Messages\Incoming\IncomingMessage $matchingMessage
     * @return void
     */
    public function sendRequest($endpoint, array $parameters, IncomingMessage $matchingMessage)
    {

    }

    public function verifyRequest(Request $request)
    {
        $_SERVER['driver_verified'] = true;
    }
}