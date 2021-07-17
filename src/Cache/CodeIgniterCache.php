<?php

namespace BotMan\BotMan\Cache;

use BotMan\BotMan\Interfaces\CacheInterface;
use CI_Cache;

class CodeIgniterCache implements CacheInterface
{
    /**
     * The codeigniter cache driver.
     *
     * @var \CI_Cache
     */
    private $driver;

    /**
     * @param \CI_Cache $driver
     */
    public function __construct(CI_Cache $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Determine if an item exists in the cache.
     *
     * @param  string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->driver->get($key) !== false;
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if ($this->has($key)) {
            return $this->driver->get($key);
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
    public function pull(string $key, $default = null)
    {
        if (!$this->has($key)) {
            return $default;
        }

        $cached = $this->driver->get($key);
        $this->driver->delete($key);

        return $cached;
    }

    /**
     * Store an item in the cache.
     *
     * @param  string $key
     * @param  mixed $value
     * @param  \DateTime|int $minutes
     * @return void
     */
    public function put(string $key, $value, $minutes)
    {
        if ($minutes instanceof \Datetime) {
            $seconds = $minutes->getTimestamp() - time();
        } else {
            $seconds = $minutes * 60;
        }

        $this->driver->save($key, $value, $seconds);
    }
}
