<?php

namespace Mpociot\BotMan;

use Slack\RealTimeClient;
use Phergie\Irc\Connection;
use Mpociot\BotMan\Http\Curl;
use Illuminate\Support\Collection;
use React\EventLoop\LoopInterface;
use Mpociot\BotMan\Cache\ArrayCache;
use Phergie\Irc\Client\React\Client;
use Mpociot\BotMan\Drivers\IrcDriver;
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
     * Create a new BotMan instance to use with the IRC driver.
     *
     * @param array $config
     * @param LoopInterface $loop
     * @param CacheInterface $cache
     * @param StorageInterface $storageDriver
     * @return \Mpociot\BotMan\BotMan
     */
    public static function createForIRC(array $config, LoopInterface $loop, CacheInterface $cache = null, StorageInterface $storageDriver = null)
    {
        $client = new Client();
        $client->setLoop($loop);

        return self::createUsingIRC($config, $client, $cache, $storageDriver);
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
        $client = new RealTimeClient($loop);

        return self::createUsingRTM($config, $client, $cache, $storageDriver);
    }

    /**
     * Create a new BotMan instance.
     *
     * @param array $config
     * @param RealTimeClient $client
     * @param CacheInterface $cache
     * @param StorageInterface $storageDriver
     * @return BotMan
     * @internal param LoopInterface $loop
     */
    public static function createUsingRTM(array $config, RealTimeClient $client, CacheInterface $cache = null, StorageInterface $storageDriver = null)
    {
        if (empty($cache)) {
            $cache = new ArrayCache();
        }

        if (empty($storageDriver)) {
            $storageDriver = new FileStorage(__DIR__);
        }

        $client->setToken(Collection::make($config)->get('slack_token'));

        $botman = new BotMan($cache, new SlackRTMDriver($config, $client), $config, $storageDriver);

        $client->on('message', function () use ($botman) {
            $botman->listen();
        });

        $client->connect();

        return $botman;
    }

    /**
     * Create a new BotMan instance using IRC.
     *
     * @param array $config
     * @param Client $client
     * @param CacheInterface $cache
     * @param StorageInterface $storageDriver
     * @return BotMan
     * @internal param LoopInterface $loop
     */
    public static function createUsingIRC(array $config, Client $client, CacheInterface $cache = null, StorageInterface $storageDriver = null)
    {
        if (empty($cache)) {
            $cache = new ArrayCache();
        }

        if (empty($storageDriver)) {
            $storageDriver = new FileStorage(__DIR__);
        }

        $connection = new Connection([
            'ServerHostname' => 'chat.freenode.net',
            'Nickname' => 'botman-bot',
            'Username' => 'botman-bot',
            'Realname' => 'botman bot',
            'Hostname' => 'botman.io',
        ]);
        $channels = '#botman-io';

        $botman = new BotMan($cache, new IrcDriver($config, $client), $config, $storageDriver);

        $client->on('irc.received', function ($message, $write, $connection, $logger) use ($botman, $channels) {
            if (isset($message['code']) && ($message['code'] === 'RPL_ENDOFMOTD' || $message['code'] === 'ERR_NOMOTD')) {
                $write->ircJoin($channels);
            }
            var_dump($message);
            $botman->listen();
        });
        $client->run($connection, false);

        return $botman;
    }
}
