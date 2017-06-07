<?php

namespace BotMan\BotMan\Cache;

use Cache;
use BotMan\BotMan\Interfaces\CacheInterface;

/**
 * The Laravel Cache implementation.
 * Since the Laravel Cache uses closures, it cannot be serialized,
 * that's why I'm using the facade in here.
 */
class LaravelCache implements CacheInterface
{
    /**
     * Determine if an item exists in the cache.
     *
     * @param  string $key
     * @return bool
     */
    public function has($key)
    {
        return Cache::has($key);
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
        return Cache::get($key, $default = null);
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
        return Cache::pull($key, $default);
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
        Cache::put($key, $value, $minutes);
    }
}
