<?php
/**
 * Created by PhpStorm.
 * User: marcel
 * Date: 27/11/2016
 * Time: 20:53
 */

namespace Mpociot\SlackBot;


use Mpociot\SlackBot\Drivers\Driver;
use Mpociot\SlackBot\Drivers\FacebookDriver;
use Mpociot\SlackBot\Drivers\NullDriver;
use Mpociot\SlackBot\Drivers\SlackDriver;
use Mpociot\SlackBot\Interfaces\HttpInterface;
use Symfony\Component\HttpFoundation\Request;

class DriverManager
{
    /**
     * @var array
     */
    protected $drivers = [
        SlackDriver::class,
        FacebookDriver::class
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