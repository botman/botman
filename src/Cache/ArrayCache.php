<?php

namespace BotMan\BotMan\Cache;

use BotMan\BotMan\Interfaces\CacheInterface;

class ArrayCache implements CacheInterface
{
    /**
     * @var array
     */
    private $cache = [];

    /**
     * Determine if an item exists in the cache.
     *
     * @param  string $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->cache[$key]);
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
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        return $default;
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
        if (isset($this->cache[$key])) {
            $cached = $this->cache[$key];
            unset($this->cache[$key]);

            return $cached;
        }

        return $default;
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
        $this->cache[$key] = $value;
    }
}
