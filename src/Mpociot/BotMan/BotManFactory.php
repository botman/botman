<?php

namespace Mpociot\BotMan;

use SuperClosure\Serializer;
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
     * @param Request $request
     * @param CacheInterface $cache
     * @return \Mpociot\BotMan\BotMan
     */
    public static function create(array $config, Request $request = null, CacheInterface $cache = null)
    {
        if (empty($request)) {
            $request = Request::createFromGlobals();
        }
        if (empty($cache)) {
            $cache = new ArrayCache();
        }

        $driverManager = new DriverManager($config, new Curl());
        $driver = $driverManager->getMatchingDriver($request);

        return new BotMan(new Serializer(), $cache, $driver);
    }
}
