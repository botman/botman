<?php

namespace BotMan\BotMan\Drivers;

use BotMan\BotMan\Http\Curl;
use BotMan\BotMan\Drivers\Kik\KikDriver;
use BotMan\BotMan\Interfaces\HttpInterface;
use BotMan\BotMan\Drivers\Nexmo\NexmoDriver;
use BotMan\BotMan\Drivers\Slack\SlackDriver;
use Symfony\Component\HttpFoundation\Request;
use BotMan\BotMan\Interfaces\DriverInterface;
use BotMan\BotMan\Drivers\WeChat\WeChatDriver;
use BotMan\BotMan\Drivers\HipChat\HipChatDriver;
use BotMan\BotMan\Drivers\WeChat\WeChatPhotoDriver;
use BotMan\BotMan\Drivers\WeChat\WeChatVideoDriver;
use BotMan\BotMan\Drivers\WeChat\WeChatLocationDriver;

class DriverManager
{
    /**
     * @var array
     */
    protected static $drivers = [
        SlackDriver::class,
        KikDriver::class,
        NexmoDriver::class,
        HipChatDriver::class,
        WeChatPhotoDriver::class,
        WeChatLocationDriver::class,
        WeChatVideoDriver::class,
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
     * @return mixed|HttpDriver|NullDriver
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
        if (class_exists($name) && is_subclass_of($name, HttpDriver::class)) {
            $name = $name::DRIVER_NAME;
        }
        if (is_null($request)) {
            $request = Request::createFromGlobals();
        }
        foreach (self::getAvailableDrivers() as $driver) {
            /** @var HttpDriver $driver */
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
        array_unshift(self::$drivers, $driver);
    }

    /**
     * Remove a driver from the list of loadable drivers.
     *
     * @param string $driver Driver class name
     */
    public static function unloadDriver($driver)
    {
        foreach (array_keys(self::$drivers, $driver) as $key) {
            unset(self::$drivers[$key]);
        }
    }

    /**
     * @param Request $request
     * @return HttpDriver
     */
    public function getMatchingDriver(Request $request)
    {
        foreach (self::getAvailableDrivers() as $driver) {
            /** @var HttpDriver $driver */
            $driver = new $driver($request, $this->config, $this->http);
            if ($driver->matchesRequest() || $driver->hasMatchingEvent()) {
                return $driver;
            }
        }

        return new NullDriver($request, [], $this->http);
    }
}
