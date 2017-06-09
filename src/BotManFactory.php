<?php

namespace BotMan\BotMan;

use React\Socket\Server;
use Slack\RealTimeClient;
use BotMan\BotMan\Http\Curl;
use Illuminate\Support\Collection;
use React\EventLoop\LoopInterface;
use BotMan\BotMan\Cache\ArrayCache;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Interfaces\CacheInterface;
use Symfony\Component\HttpFoundation\Request;
use BotMan\BotMan\Interfaces\StorageInterface;
use BotMan\BotMan\Drivers\Slack\SlackRTMDriver;
use BotMan\BotMan\Storages\Drivers\FileStorage;

class BotManFactory
{
    /**
     * Create a new BotMan instance.
     *
     * @param array $config
     * @param CacheInterface $cache
     * @param Request $request
     * @param StorageInterface $storageDriver
     * @return \BotMan\BotMan\BotMan
     */
    public static function create(
        array $config,
        CacheInterface $cache = null,
        Request $request = null,
        StorageInterface $storageDriver = null
    ) {
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
     * Create a new BotMan instance that listens on a socket.
     *
     * @param array $config
     * @param LoopInterface $loop
     * @param CacheInterface $cache
     * @param StorageInterface $storageDriver
     * @return \BotMan\BotMan\BotMan
     */
    public static function createForSocket(
        array $config,
        LoopInterface $loop,
        CacheInterface $cache = null,
        StorageInterface $storageDriver = null
    ) {
        $port = isset($config['port']) ? $config['port'] : 8080;

        $socket = new Server($loop);

        if (empty($cache)) {
            $cache = new ArrayCache();
        }

        if (empty($storageDriver)) {
            $storageDriver = new FileStorage(__DIR__);
        }

        $driverManager = new DriverManager($config, new Curl());

        $botman = new BotMan($cache, DriverManager::loadFromName('Null', $config), $config, $storageDriver);
        $botman->runsOnSocket(true);

        $socket->on('connection', function ($conn) use ($botman, $driverManager) {
            $conn->on('data', function ($data) use ($botman, $driverManager) {
                $requestData = json_decode($data, true);
                $request = new Request($requestData['query'], $requestData['request'], $requestData['attributes'], [], [], [], $requestData['content']);
                $driver = $driverManager->getMatchingDriver($request);
                $botman->setDriver($driver);
                $botman->listen();
            });
        });
        $socket->listen($port);

        return $botman;
    }

    /**
     * Pass an incoming HTTP request to the socket.
     *
     * @param  int      $port    The port to use. Default is 8080
     * @param  Request|null $request
     * @return void
     */
    public static function passRequestToSocket($port = 8080, Request $request = null)
    {
        if (empty($request)) {
            $request = Request::createFromGlobals();
        }

        $client = stream_socket_client('tcp://127.0.0.1:'.$port);
        fwrite($client, json_encode([
            'attributes' => $request->attributes->all(),
            'query' => $request->query->all(),
            'request' => $request->request->all(),
            'content' => $request->getContent(),
        ]));
        fclose($client);
    }

    /**
     * Create a new BotMan instance.
     *
     * @param array $config
     * @param LoopInterface $loop
     * @param CacheInterface $cache
     * @param StorageInterface $storageDriver
     * @return \BotMan\BotMan\BotMan
     */
    public static function createForRTM(
        array $config,
        LoopInterface $loop,
        CacheInterface $cache = null,
        StorageInterface $storageDriver = null
    ) {
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
    public static function createUsingRTM(
        array $config,
        RealTimeClient $client,
        CacheInterface $cache = null,
        StorageInterface $storageDriver = null
    ) {
        if (empty($cache)) {
            $cache = new ArrayCache();
        }

        if (empty($storageDriver)) {
            $storageDriver = new FileStorage(__DIR__);
        }

        $client->setToken(Collection::make($config)->get('slack_token'));

        $driver = new SlackRTMDriver($config, $client);
        $botman = new BotMan($cache, $driver, $config, $storageDriver);

        $client->on('_internal_message', function () use ($botman) {
            $botman->listen();
        });

        $client->connect()->then(function () use ($driver) {
            $driver->connected();
        });

        return $botman;
    }
}
