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
use Mpociot\BotMan\Interfaces\StorageInterface;
use Mpociot\BotMan\Storages\Drivers\FileStorage;

class BotManFactory
{
    /**
     * Create a new BotMan instance.
     *
     * @param array $config
     * @param CacheInterface $cache
     * @param Request $request
     * @param StorageInterface $storageDriver
     * @return \Mpociot\BotMan\BotMan
     */
    public static function create(array $config, CacheInterface $cache = null, Request $request = null, StorageInterface $storageDriver = null)
    {
        if (empty($cache)) {
            $cache = new ArrayCache();
        }
        if (empty($request)) {
            $request = Request::createFromGlobals();
        }
        if (empty($storageDriver)) {
            $storageDriver = new FileStorage(__DIR__);
        }

        $driverManager = new DriverManager($config, new Curl());
        $driver = $driverManager->getMatchingDriver($request);

        return new BotMan($cache, $driver, $config, $storageDriver);
    }

    /**
     * Create a new BotMan instance.
     *
     * @param array $config
     * @param LoopInterface $loop
     * @param CacheInterface $cache
     * @param StorageInterface $storageDriver
     * @return \Mpociot\BotMan\BotMan
     */
    public static function createForRTM(array $config, LoopInterface $loop, CacheInterface $cache = null, StorageInterface $storageDriver = null)
    {
        if (empty($cache)) {
            $cache = new ArrayCache();
        }
        if (empty($storageDriver)) {
            $storageDriver = new FileStorage(__DIR__);
        }

        $client = new RealTimeClient($loop);
        $client->setToken(Collection::make($config)->get('slack_token'));

        $botman = new BotMan($cache, new SlackRTMDriver($config, $client), $config, $storageDriver);

        $client->on('message', function () use ($botman) {
            $botman->loadActiveConversation();
            $botman->listen();
        });

        $client->connect();

        return $botman;
    }
}
