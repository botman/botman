<?php

namespace BotMan\BotMan\Cache;

use BotMan\BotMan\Interfaces\CacheInterface;
use Doctrine\Common\Cache\Cache;

class DoctrineCache implements CacheInterface
{
    /**
     * @var Cache
     */
    private $driver;

    /**
     * @param Cache $driver
     */
    public function __construct(Cache $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Determine if an item exists in the cache.
     *
     * @param  string $key
     * @return bool
     */
    public function has($key)
    {
        return $this->driver->contains($key);
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
        $value = $this->driver->fetch($key);
        if ($value !== false) {
            return $value;
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
        if ($this->has($key)) {
            $cached = $this->get($key, $default);
            $this->driver->delete($key);

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
        if ($minutes instanceof \Datetime) {
            $seconds = $minutes->getTimestamp() - time();
        } else {
            $seconds = $minutes * 60;
        }

        $this->driver->save($key, $value, $seconds);
    }
}
