<?php

namespace BotMan\BotMan\Drivers\Tests;

use BotMan\BotMan\Drivers\NullDriver;
use BotMan\BotMan\Http\Curl;
use BotMan\BotMan\Interfaces\DriverInterface;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use Symfony\Component\HttpFoundation\Request;

/**
 * A driver that acts as a proxy for a global driver instance. Useful for mock/fake drivers in integration tests.
 */
final class ProxyDriver implements DriverInterface
{
    /**
     * @var DriverInterface
     */
    private static $instance;

    /**
     * Set driver instance to be used.
     *
     * @param DriverInterface $driver
     */
    public static function setInstance(DriverInterface $driver)
    {
        self::$instance = $driver;
    }

    /**
     * @return DriverInterface
     */
    private static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new NullDriver(new Request, [], new Curl);
        }

        return self::$instance;
    }

    public function matchesRequest()
    {
        return self::instance()->matchesRequest();
    }

    public function hasMatchingEvent()
    {
        return self::instance()->hasMatchingEvent();
    }

    public function getMessages()
    {
        return self::instance()->getMessages();
    }

    public function isBot()
    {
        return self::instance()->isBot();
    }

    public function isConfigured()
    {
        return self::instance()->isConfigured();
    }

    public function getUser(IncomingMessage $matchingMessage)
    {
        return self::instance()->getUser($matchingMessage);
    }

    public function getUserWithFields(array $fields, IncomingMessage $matchingMessage)
    {
        return self::instance()->getUser($matchingMessage);
    }

    public function getConversationAnswer(IncomingMessage $message)
    {
        return self::instance()->getConversationAnswer($message);
    }

    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        return self::instance()->buildServicePayload($message, $matchingMessage, $additionalParameters);
    }

    public function sendPayload($payload)
    {
        return self::instance()->sendPayload($payload);
    }

    public function getName()
    {
        return self::instance()->getName();
    }

    public function types(IncomingMessage $matchingMessage)
    {
        return self::instance()->types($matchingMessage);
    }

    /**
     * Send a typing indicator and wait for the given amount of seconds.
     * @param IncomingMessage $matchingMessage
     * @param int $seconds
     * @param float $seconds
     * @return mixed
     */
    public function typesAndWaits(IncomingMessage $matchingMessage, float $seconds)
    {
        $this->types($matchingMessage);
    }

    /**
     * Tells if the stored conversation callbacks are serialized.
     *
     * @return bool
     */
    public function serializesCallbacks()
    {
        return self::instance()->serializesCallbacks();
    }
}
