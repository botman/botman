<?php

namespace BotMan\BotMan\Drivers\Tests;

use BotMan\BotMan\Users\User;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Interfaces\DriverInterface;
use BotMan\BotMan\Interfaces\VerifiesService;
use BotMan\BotMan\Messages\Outgoing\Question;
use Symfony\Component\HttpFoundation\Request;
use BotMan\BotMan\Drivers\Events\GenericEvent;
use Symfony\Component\HttpFoundation\Response;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

/**
 * A fake driver for tests. Must be used with ProxyDriver.
 * Example to set it up in a unit test:
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
 * </code>.
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
    public $isInteractiveMessageReply = false;

    /** @var bool */
    public $isConfigured = true;

    /** @var array */
    private $botMessages = [];

    /** @var bool */
    private $botIsTyping = false;

    /** @var string */
    private $driver_name = 'Fake';

    /** @var string */
    private $event_name;

    /** @var array */
    private $event_payload;

    /** @var string */
    private $user_id = null;

    /** @var string */
    private $user_first_name = 'Marcel';

    /** @var string */
    private $user_last_name = 'Pociot';

    /** @var string */
    private $username = 'BotMan';

    /** @var array */
    private $user_info = [];

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

    public function setUser(array $user_info)
    {
        $this->user_id = $user_info['id'] ?? $this->user_id;
        $this->user_first_name = $user_info['first_name'] ?? $this->user_first_name;
        $this->user_last_name = $user_info['last_name'] ?? $this->user_last_name;
        $this->username = $user_info['username'] ?? $this->username;
        $this->user_info = $user_info;
    }

    public function getUser(IncomingMessage $matchingMessage)
    {
        return new User($this->user_id ?? $matchingMessage->getSender(), $this->user_first_name, $this->user_last_name, $this->username, $this->user_info);
    }

    public function getConversationAnswer(IncomingMessage $message)
    {
        $answer = Answer::create($message->getText())->setMessage($message)->setValue($message->getText());
        $answer->setInteractiveReply($this->isInteractiveMessageReply);

        return $answer;
    }

    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        return $message;
    }

    public function sendPayload($payload)
    {
        $this->botMessages[] = $payload;
        $text = method_exists($payload, 'getText') ? $payload->getText() : '';

        return Response::create(json_encode($text));
    }

    public function setName($name)
    {
        $this->driver_name = $name;
    }

    public function getName()
    {
        return $this->driver_name;
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
     * @return void
     */
    public function setEventName($name)
    {
        $this->event_name = $name;
    }

    /**
     * @return void
     */
    public function setEventPayload($payload)
    {
        $this->event_payload = $payload;
    }

    /**
     * @return bool
     */
    public function hasMatchingEvent()
    {
        if (isset($this->event_name)) {
            $event = new GenericEvent($this->event_payload);
            $event->setName($this->event_name);

            return $event;
        }

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
