<?php

namespace Mpociot\BotMan;

use Mpociot\BotMan\Drivers\Driver;
use Mpociot\BotMan\Drivers\NullDriver;
use Mpociot\BotMan\Drivers\NexmoDriver;
use Mpociot\BotMan\Drivers\SlackDriver;
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
    protected $drivers = [
        SlackDriver::class,
        FacebookDriver::class,
        TelegramDriver::class,
        BotFrameworkDriver::class,
        NexmoDriver::class,
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
     * @param Request $request
     * @return Driver
     */
    public function getMatchingDriver(Request $request)
    {
        foreach ($this->drivers as $driver) {
            /** @var Driver $driver */
            $driver = new $driver($request, $this->config, $this->http);
            if ($driver->matchesRequest()) {
                return $driver;
            }
        }

        return new NullDriver($request, [], $this->http);
    }
}
