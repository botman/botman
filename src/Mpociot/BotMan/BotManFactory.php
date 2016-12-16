<?php

namespace Mpociot\BotMan;

use Slack\RealTimeClient;
use Mpociot\BotMan\Http\Curl;
use Illuminate\Support\Collection;
use React\EventLoop\LoopInterface;
use Mpociot\BotMan\Cache\ArrayCache;
use Mpociot\BotMan\Drivers\SlackRTMDriver;
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
    
    /**
     * Create a new BotMan instance.
     *
     * @param array $config
     * @param LoopInterface $loop
     * @param CacheInterface $cache
     * @return \Mpociot\BotMan\BotMan
     */
    public static function createForRTM(array $config, LoopInterface $loop, CacheInterface $cache = null)
    {
        if (empty($cache)) {
            $cache = new ArrayCache();
        }

        $client = new RealTimeClient($loop);
        $client->setToken(Collection::make($config)->get('slack_token'));

        $botman = new BotMan($cache, new SlackRTMDriver($config, $client), $config);

        $client->on('message', function() use ($botman) {
            $botman->loadActiveConversation();
            $botman->listen();
        });

        $client->connect();

        return $botman;
    }
}
