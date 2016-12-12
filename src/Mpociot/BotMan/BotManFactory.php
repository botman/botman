<?php

namespace Mpociot\BotMan;

use Mpociot\BotMan\Http\Curl;
use Mpociot\BotMan\Cache\ArrayCache;
use Mpociot\BotMan\Interfaces\CacheInterface;
use Symfony\Component\HttpFoundation\Request;

class BotManFactory
{
    /**
     * Create a new BotMan instance.
     *
     * @param array $config
     * @param CacheInterface $cache
     * @param Request $request
     * @return \Mpociot\BotMan\BotMan
     */
    public static function create(array $config, CacheInterface $cache = null, Request $request = null)
    {
        if (empty($cache)) {
            $cache = new ArrayCache();
        }
        if (empty($request)) {
            $request = Request::createFromGlobals();
        }

        $driverManager = new DriverManager($config, new Curl());
        $driver = $driverManager->getMatchingDriver($request);

        return new BotMan($cache, $driver, $config);
    }
}
