<?php

namespace Mpociot\BotMan\Drivers;

use Mpociot\BotMan\User;
use Mpociot\BotMan\Answer;
use Mpociot\BotMan\Message;
use Mpociot\BotMan\Question;
use Mpociot\BotMan\Interfaces\DriverInterface;

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
        return new User($matchingMessage->getUser());
    }

    public function getConversationAnswer(Message $message)
    {
        return Answer::create($message->getMessage())->setMessage($message);
    }

    public function reply($message, $matchingMessage, $additionalParameters = [])
    {
        $this->botMessages[] = $message;

        return $this;
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
}
