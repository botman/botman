<?php
namespace Mpociot\SlackBot\Cache;

use Illuminate\Cache\Repository;
use Mpociot\SlackBot\Interfaces\CacheInterface;

class LaravelCache implements CacheInterface
{

    /**
     * @var Repository
     */
    private $cache;

    /**
     * LaravelCache constructor.
     * @param Repository $cache
     */
    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Determine if an item exists in the cache.
     *
     * @param  string $key
     * @return bool
     */
    public function has($key)
    {
        return $this->cache->has($key);
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->cache->get($key, $default = null);
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
        return $this->cache->pull($key, $default);
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
        $this->cache->put($key, $value, $minutes);
    }
}