<?php

namespace BotMan\BotMan\Storages\Drivers;

use Redis;
use RuntimeException;
use Illuminate\Support\Collection;
use BotMan\BotMan\Interfaces\StorageInterface;

class RedisStorage implements StorageInterface
{
    const KEY_PREFIX = 'botman:storage:';

    /**
     * @var Redis
     */
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
        if (! class_exists(Redis::class)) {
            throw new RuntimeException('phpredis extension is required for RedisStorage');
        }
        $this->host = $host;
        $this->port = $port;
        $this->auth = $auth;
        $this->connect();
    }

    /**
     * Save an item in the storage with a specific key and data.
     *
     * @param  array $data
     * @param  string $key
     */
    public function save(array $data, $key)
    {
        $this->redis->set($this->decorateKey($key), $data);
    }

    /**
     * Retrieve an item from the storage by key.
     *
     * @param  string $key
     * @return Collection
     */
    public function get($key)
    {
        $value = $this->redis->get($this->decorateKey($key));

        return $value ? Collection::make($value) : new Collection();
    }

    /**
     * Delete a stored item by its key.
     *
     * @param  string $key
     */
    public function delete($key)
    {
        $this->redis->del($this->decorateKey($key));
    }

    /**
     * Return all stored entries.
     *
     * @return array
     */
    public function all()
    {
        $entries = [];
        while ($keys = $this->redis->scan($it, self::KEY_PREFIX.'*')) {
            foreach ($keys as $key) {
                $entries[substr($key, strlen(self::KEY_PREFIX))] = Collection::make($this->redis->get($key));
            }
        }

        return $entries;
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
