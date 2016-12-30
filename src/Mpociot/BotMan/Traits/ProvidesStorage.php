<?php

namespace Mpociot\BotMan\Traits;

use Mpociot\BotMan\Storages\Storage;

trait ProvidesStorage
{
    /**
     * @return Storage
     */
    public function userStorage()
    {
        return (new Storage($this->storage))
            ->setPrefix('user_')
            ->setDefaultKey($this->getMessage()->getUser());
    }

    /**
     * @return Storage
     */
    public function channelStorage()
    {
        return (new Storage($this->storage))
            ->setPrefix('channel_')
            ->setDefaultKey($this->getMessage()->getChannel());
    }

    /**
     * @return Storage
     */
    public function driverStorage()
    {
        return (new Storage($this->storage))
            ->setPrefix('driver_')
            ->setDefaultKey($this->getDriver()->getName());
    }
}
