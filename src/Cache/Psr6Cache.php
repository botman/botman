<?php

namespace BotMan\BotMan\Cache;

use BotMan\BotMan\Interfaces\CacheInterface;
use DateInterval;
use DateTimeInterface;
use Psr\Cache\CacheItemPoolInterface;

class Psr6Cache implements CacheInterface
{
    /**
     * @var CacheItemPoolInterface
     */
    protected $adapter;

    /**
     * @param CacheItemPoolInterface $adapter
     */
    public function __construct(CacheItemPoolInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @param string $key
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function has(string $key): bool
    {
        return $this->adapter->hasItem($key);
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed|null
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function get(string $key, $default = null)
    {
        $item = $this->adapter->getItem($key);
        if ($item->isHit()) {
            return $item->get();
        }

        return $default;
    }

    /**
     * @param string $key
     * @param null $default
     * @return null
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function pull(string $key, $default = null)
    {
        $item = $this->adapter->getItem($key);

        if (!$item->isHit()) {
            return $default;
        }

        $this->adapter->deleteItem($key);
        return $item->get();
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param \DateTime|int $minutes
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function put(string $key, $value, $minutes)
    {
        $item = $this->adapter->getItem($key);
        $item->set($value);

        if ($minutes instanceof DateTimeInterface) {
            $item->expiresAt($minutes);
        } else {
            $item->expiresAfter(new DateInterval(sprintf('PT%dM', $minutes)));
        }

        $this->adapter->save($item);
    }
}
