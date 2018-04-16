<?php

namespace BotMan\BotMan\Drivers;

use BotMan\BotMan\Http\Curl;
use Illuminate\Support\Collection;
use BotMan\BotMan\Interfaces\HttpInterface;
use BotMan\BotMan\Interfaces\DriverInterface;
use BotMan\BotMan\Interfaces\VerifiesService;
use Symfony\Component\HttpFoundation\Request;

class DriverManager
{
    /**
     * @var array
     */
    protected static $drivers = [];

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
     * @return array
     */
    public static function getAvailableHttpDrivers()
    {
        return Collection::make(self::$drivers)->filter(function ($driver) {
            return is_subclass_of($driver, HttpDriver::class);
        })->toArray();
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
            $name = preg_replace('#(Driver$)#', '', basename(str_replace('\\', '/', $name)));
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

        foreach (self::getAvailableHttpDrivers() as $driver) {
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
     * @param bool $explicit Only load this one driver and not any additional (sub)-drivers
     */
    public static function loadDriver($driver, $explicit = false)
    {
        array_unshift(self::$drivers, $driver);
        if (method_exists($driver, 'loadExtension')) {
            call_user_func([$driver, 'loadExtension']);
        }

        if (method_exists($driver, 'additionalDrivers') && $explicit === false) {
            $additionalDrivers = (array) call_user_func([$driver, 'additionalDrivers']);
            foreach ($additionalDrivers as $additionalDriver) {
                self::loadDriver($additionalDriver);
            }
        }

        self::$drivers = array_unique(self::$drivers);
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
     * Verify service webhook URLs.
     *
     * @param array $config
     * @param Request|null $request
     * @return bool
     */
    public static function verifyServices(array $config, Request $request = null)
    {
        $request = (isset($request)) ? $request : Request::createFromGlobals();
        foreach (self::getAvailableHttpDrivers() as $driver) {
            $driver = new $driver($request, $config, new Curl());
            if ($driver instanceof VerifiesService && ! is_null($driver->verifyRequest($request))) {
                return true;
            }
        }

        return false;
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
