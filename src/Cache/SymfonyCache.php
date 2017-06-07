<?php

namespace BotMan\BotMan\Cache;

use Symfony\Component\Cache\Adapter\AdapterInterface;

class SymfonyCache extends Psr6Cache
{
    /**
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }
}
