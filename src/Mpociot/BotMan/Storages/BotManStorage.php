<?php

namespace Mpociot\BotMan\Storages;

use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Interfaces\StorageInterface;

class BotManStorage
{
    /** @var StorageInterface */
    private $storage;

    /** @var BotMan */
    private $botman;

    /**
     * BotManStorage constructor.
     * @param StorageInterface $storage
     */
    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param BotMan $botman
     */
    public function setBotman(BotMan $botman)
    {
        $this->botman = $botman;
    }

    /**
     * @return Storage
     */
    public function users()
    {
        return (new Storage($this->storage))
            ->setPrefix('user_')
            ->setDefaultKey($this->botman->getMessage()->getUser());
    }

    /**
     * @return Storage
     */
    public function channel()
    {
        return (new Storage($this->storage))
            ->setPrefix('channel_')
            ->setDefaultKey($this->botman->getMessage()->getChannel());
    }

    /**
     * @return Storage
     */
    public function driver()
    {
        return (new Storage($this->storage))
            ->setPrefix('driver_')
            ->setDefaultKey($this->botman->getDriver()->getName());
    }
}
