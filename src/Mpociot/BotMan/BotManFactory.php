<?php

namespace Mpociot\BotMan;

use SuperClosure\Serializer;
use Mpociot\BotMan\Http\Curl;
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
    public static function create(array $config, Request $request, CacheInterface $cache)
    {
        $driverManager = new DriverManager($config, new Curl());
        $driver = $driverManager->getMatchingDriver($request);

        return new BotMan(new Serializer(), $cache, $driver);
    }
}
