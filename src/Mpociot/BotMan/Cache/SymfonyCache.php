<?php

namespace Mpociot\BotMan\Cache;

use Mpociot\BotMan\Interfaces\CacheInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class SymfonyCache implements CacheInterface
{
    /**
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return $this->adapter->hasItem($key);
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed|null
     */
    public function get($key, $default = null)
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
     */
    public function pull($key, $default = null)
    {
        $item = $this->adapter->getItem($key);
        if ($item->isHit()) {
            $this->adapter->deleteItem($key);

            return $item->get();
        }

        return $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param \DateTime|int $minutes
     */
    public function put($key, $value, $minutes)
    {
        $item = $this->adapter->getItem($key);
        $item->set($value);

        if ($minutes instanceof \DateTimeInterface) {
            $item->expiresAt($minutes);
        } else {
            $item->expiresAfter(new \DateInterval(sprintf('PT%dM', $minutes)));
        }

        $this->adapter->save($item);
    }
}
