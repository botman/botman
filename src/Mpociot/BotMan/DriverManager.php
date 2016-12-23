<?php

namespace Mpociot\BotMan;

use Mpociot\BotMan\Http\Curl;
use Mpociot\BotMan\Drivers\Driver;
use Mpociot\BotMan\Drivers\NullDriver;
use Mpociot\BotMan\Drivers\NexmoDriver;
use Mpociot\BotMan\Drivers\SlackDriver;
use Mpociot\BotMan\Drivers\HipChatDriver;
use Mpociot\BotMan\Drivers\FacebookDriver;
use Mpociot\BotMan\Drivers\TelegramDriver;
use Mpociot\BotMan\Interfaces\HttpInterface;
use Symfony\Component\HttpFoundation\Request;
use Mpociot\BotMan\Drivers\BotFrameworkDriver;

class DriverManager
{
    /**
     * @var array
     */
    protected static $drivers = [
        SlackDriver::class,
        FacebookDriver::class,
        TelegramDriver::class,
        BotFrameworkDriver::class,
        NexmoDriver::class,
        HipChatDriver::class,
    ];

    /** @var array */
    protected $config;

    /** @var HttpInterface */
    protected $http;

    /**
     * DriverManager constructor.
     * @param array $config
     * @param HttpInterface $http
     */
    public function __construct(array $config, HttpInterface $http)
    {
        $this->config = $config;
        $this->http = $http;
    }

    /**
     * @return array
     */
    public static function getAvailableDrivers()
    {
        return self::$drivers;
    }

    /**
     * Load a driver by using its name.
     *
     * @param string $name
     * @param array $config
     * @param Request|null $request
     * @return mixed|Driver|NullDriver
     */
    public static function loadFromName($name, array $config, Request $request = null)
    {
        /**
         * Use the driver name constant if we try to load a driver by it's
         * fully qualified class name.
         */
        if (class_exists($name) && is_subclass_of($name, Driver::class)) {
            $name = $name::DRIVER_NAME;
        }
        if (is_null($request)) {
            $request = Request::createFromGlobals();
        }
        foreach (self::getAvailableDrivers() as $driver) {
            /** @var Driver $driver */
            $driver = new $driver($request, $config, new Curl());
            if ($driver->getName() === $name) {
                return $driver;
            }
        }

        return new NullDriver($request, [], new Curl());
    }

    /**
     * @param array $config
     * @return array
     */
    public static function getConfiguredDrivers(array $config)
    {
        $drivers = [];

        foreach (self::getAvailableDrivers() as $driver) {
            $driver = new $driver(Request::createFromGlobals(), $config, new Curl());
            if ($driver->isConfigured()) {
                $drivers[] = $driver;
            }
        }

        return $drivers;
    }

    /**
     * @param Request $request
     * @return Driver
     */
    public function getMatchingDriver(Request $request)
    {
        foreach (self::getAvailableDrivers() as $driver) {
            /** @var Driver $driver */
            $driver = new $driver($request, $this->config, $this->http);
            if ($driver->matchesRequest()) {
                return $driver;
            }
        }

        return new NullDriver($request, [], $this->http);
    }
}
