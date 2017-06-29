<?php

namespace BotMan\BotMan\Drivers\Tests;

use BotMan\BotMan\Users\User;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Interfaces\DriverInterface;
use BotMan\BotMan\Interfaces\VerifiesService;
use BotMan\BotMan\Messages\Outgoing\Question;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

/**
 * A fake driver for tests. Must be used with ProxyDriver.
 *
 * Example to set it up in a unit test:
 *
 * <code>
 *  public static function setUpBeforeClass()
 *  {
 *      DriverManager::loadDriver(ProxyDriver::class);
 *  }
 *  public function setUp()
 *  {
 *      $this->fakeDriver = new FakeDriver();
 *      ProxyDriver::setInstance($this->fakeDriver);
 *  }
 * </code>
 */
class FakeDriver implements DriverInterface, VerifiesService
{
    /** @var bool */
    public $matchesRequest = true;
    /** @var bool */
    public $hasMatchingEvent = false;
    /** @var \BotMan\BotMan\Messages\Incoming\IncomingMessage[] */
    public $messages = [];
    /** @var bool */
    public $isBot = false;
    /** @var bool */
    public $isConfigured = true;

    /** @var array */
    private $botMessages = [];
    /** @var bool */
    private $botIsTyping = false;

    /**
     * @return FakeDriver
     */
    public static function create()
    {
        return new static;
    }

    /**
     * @return FakeDriver
     */
    public static function createInactive()
    {
        $driver = new static;
        $driver->isConfigured = false;

        return $driver;
    }

    public function matchesRequest()
    {
        return $this->matchesRequest;
    }

    public function getMessages()
    {
        foreach ($this->messages as &$message) {
            $message->setIsFromBot($this->isBot());
        }

        return $this->messages;
    }

    protected function isBot()
    {
        return $this->isBot;
    }

    public function isConfigured()
    {
        return $this->isConfigured;
    }

    public function getUser(IncomingMessage $matchingMessage)
    {
        return new User($matchingMessage->getSender());
    }

    public function getConversationAnswer(IncomingMessage $message)
    {
        return Answer::create($message->getText())->setMessage($message);
    }

    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        return $message;
    }

    public function sendPayload($payload)
    {
        $this->botMessages[] = $payload;

        return Response::create(json_encode($payload->getText()));
    }

    public function getName()
    {
        return 'Fake';
    }

    public function types(IncomingMessage $matchingMessage)
    {
        $this->botIsTyping = true;
    }

    /**
     * Returns true if types() has been called.
     *
     * @return bool
     */
    public function isBotTyping()
    {
        return $this->botIsTyping;
    }

    /**
     * @return bool
     */
    public function hasMatchingEvent()
    {
        return $this->hasMatchingEvent;
    }

    /**
     * Returns array of messages from bot.
     *
     * @return string[]|Question[]
     */
    public function getBotMessages()
    {
        return $this->botMessages;
    }

    /**
     * Clear received messages from bot.
     */
    public function resetBotMessages()
    {
        $this->botIsTyping = false;
        $this->botMessages = [];
    }

    /**
     * Tells if the stored conversation callbacks are serialized.
     *
     * @return bool
     */
    public function serializesCallbacks()
    {
        return true;
    }

    public function verifyRequest(Request $request)
    {
        $_SERVER['driver_verified'] = true;

        return true;
    }
}
