<?php

namespace BotMan\BotMan\Cache;

use Redis;
use RuntimeException;
use BotMan\BotMan\Interfaces\CacheInterface;

/**
 * Redis <http://redis.io> cache backend
 * Requires phpredis native extension <https://github.com/phpredis/phpredis#installation>.
 */
class RedisCache implements CacheInterface
{
    const KEY_PREFIX = 'botman:cache:';

    /** @var Redis */
    private $redis;
    private $host;
    private $port;
    private $auth;

    /**
     * RedisCache constructor.
     * @param $host
     * @param $port
     * @param $auth
     */
    public function __construct($host = '127.0.0.1', $port = 6379, $auth = null)
    {
        if (! class_exists('Redis')) {
            throw new RuntimeException('phpredis extension is required for RedisCache');
        }
        $this->host = $host;
        $this->port = $port;
        $this->auth = $auth;
        $this->connect();
    }

    /**
     * Determine if an item exists in the cache.
     *
     * @param  string $key
     * @return bool
     */
    public function has($key)
    {
        /*
         * Version >= 4.0 of phpredis returns an integer instead of bool
         */
        $check = $this->redis->exists($this->decorateKey($key));

        if (is_bool($check)) {
            return $check;
        }

        return $check > 0;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->redis->get($this->decorateKey($key)) ?: $default;
    }

    /**
     * Retrieve an item from the cache and delete it.
     *
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        $redisKey = $this->decorateKey($key);
        $r = $this->redis->multi()
            ->get($redisKey)
            ->del($redisKey)
            ->exec();

        return $r[0] ?: $default;
    }

    /**
     * Store an item in the cache.
     *
     * @param  string $key
     * @param  mixed $value
     * @param  \DateTime|int $minutes
     * @return void
     */
    public function put($key, $value, $minutes)
    {
        if ($minutes instanceof \Datetime) {
            $seconds = $minutes->getTimestamp() - time();
        } else {
            $seconds = $minutes * 60;
        }
        $this->redis->setex($this->decorateKey($key), $seconds, $value);
    }

    /**
     * Namespace botman keys in redis.
     *
     * @param $key
     * @return string
     */
    private function decorateKey($key)
    {
        return self::KEY_PREFIX.$key;
    }

    private function connect()
    {
        $this->redis = new Redis();
        $this->redis->connect($this->host, $this->port);
        if ($this->auth !== null) {
            $this->redis->auth($this->auth);
        }

        if (function_exists('igbinary_serialize') && defined('Redis::SERIALIZER_IGBINARY')) {
            $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);
        } else {
            $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        }
    }

    public function __wakeup()
    {
        $this->connect();
    }
}
