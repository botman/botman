<?php

namespace Mpociot\BotMan\Drivers\Tests;

use Mpociot\BotMan\User;
use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Mpociot\BotMan\Interfaces\DriverInterface;
use Symfony\Component\HttpFoundation\Response;

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
class FakeDriver implements DriverInterface
{
    /** @var bool */
    public $matchesRequest = true;
    /** @var bool */
    public $hasMatchingEvent = false;
    /** @var Message[] */
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
        return $this->messages;
    }

    public function isBot()
    {
        return $this->isBot;
    }

    public function isConfigured()
    {
        return $this->isConfigured;
    }

    public function getUser(Message $matchingMessage)
    {
        return new User($matchingMessage->getSender());
    }

    public function getConversationAnswer(Message $message)
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
        return 'Fake Driver';
    }

    public function types(Message $matchingMessage)
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
}
