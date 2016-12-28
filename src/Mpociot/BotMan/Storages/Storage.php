<?php

namespace Mpociot\BotMan\Storages;

use Mpociot\BotMan\Interfaces\StorageInterface;

abstract class Storage implements StorageInterface
{

    /** @var string */
    protected $prefix = '';

    /**
     * @param string $prefix
     * @return $this
     */
    public function setPrefix($prefix = '')
    {
        $this->prefix = $prefix;
        return $this;
    }
}