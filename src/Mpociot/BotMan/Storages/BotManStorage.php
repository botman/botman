<?php

namespace Mpociot\BotMan\Storages;


class BotManStorage
{

    /** @var Storage */
    private $storage;

    /**
     * BotManStorage constructor.
     * @param Storage $storage
     */
    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @return Storage
     */
    public function users()
    {
        return $this->storage->setPrefix('user_');
    }

    /**
     * @return Storage
     */
    public function teams()
    {
        return $this->storage->setPrefix('team_');
    }

    /**
     * @return Storage
     */
    public function channel()
    {
        return $this->storage->setPrefix('channel_');
    }

    /**
     * @return Storage
     */
    public function driver()
    {
        return $this->storage->setPrefix('driver_');
    }
}