<?php

namespace Mpociot\BotMan;

use Mpociot\BotMan\Http\Curl;
use Mpociot\BotMan\Drivers\Driver;
use Mpociot\BotMan\Drivers\NullDriver;
use Mpociot\BotMan\Drivers\NexmoDriver;
use Mpociot\BotMan\Drivers\SlackDriver;
use Mpociot\BotMan\Drivers\WeChatDriver;
use Mpociot\BotMan\Drivers\HipChatDriver;
use Mpociot\BotMan\Drivers\FacebookDriver;
use Mpociot\BotMan\Drivers\TelegramDriver;
use Mpociot\BotMan\Interfaces\HttpInterface;
use Symfony\Component\HttpFoundation\Request;
use Mpociot\BotMan\Drivers\BotFrameworkDriver;
use Mpociot\BotMan\Interfaces\DriverInterface;
use Mpociot\BotMan\Drivers\FacebookAudioDriver;
use Mpociot\BotMan\Drivers\FacebookImageDriver;
use Mpociot\BotMan\Drivers\FacebookVideoDriver;
use Mpociot\BotMan\Drivers\TelegramPhotoDriver;
use Mpociot\BotMan\Drivers\FacebookLocationDriver;
use Mpociot\BotMan\Drivers\FacebookPostbackDriver;
use Mpociot\BotMan\Drivers\FacebookAttachmentDriver;

class DriverManager
{
    /**
     * @var array
     */
    protected static $drivers = [
        SlackDriver::class,
        FacebookDriver::class,
        FacebookImageDriver::class,
        FacebookVideoDriver::class,
        FacebookAudioDriver::class,
        FacebookPostbackDriver::class,
        FacebookAttachmentDriver::class,
        FacebookLocationDriver::class,
        TelegramPhotoDriver::class,
        TelegramDriver::class,
        BotFrameworkDriver::class,
        NexmoDriver::class,
        HipChatDriver::class,
        WeChatDriver::class,
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
        /*
        * Use the driver class basename without "Driver" if we're dealing with a
        * DriverInterface object.
        */
        if (class_exists($name) && is_subclass_of($name, DriverInterface::class)) {
            $name = rtrim(basename(str_replace('\\', '/', $name)), 'Driver');
        }
        /*
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
     * Append a driver to the list of loadable drivers.
     *
     * @param string $driver Driver class name
     */
    public static function loadDriver($driver)
    {
        self::$drivers[] = $driver;
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
