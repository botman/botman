<?php

namespace BotMan\BotMan\Storages;

use BotMan\BotMan\Interfaces\StorageInterface;
use Illuminate\Support\Collection;

class Storage implements StorageInterface
{
    /** @var StorageInterface */
    protected $driver = '';

    /** @var string */
    protected $prefix = '';

    /** @var string */
    protected $defaultKey = '';

    /**
     * Storage constructor.
     * @param StorageInterface $driver
     */
    public function __construct(StorageInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @param $key
     * @return string
     */
    protected function getKey($key)
    {
        return sha1($this->prefix.$key);
    }

    /**
     * @param string $prefix
     * @return $this
     */
    public function setPrefix($prefix = '')
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param string $defaultKey
     * @return $this
     */
    public function setDefaultKey($defaultKey)
    {
        $this->defaultKey = $defaultKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultKey()
    {
        return $this->defaultKey;
    }

    /**
     * Save an item in the storage with a specific key and data.
     *
     * @param  array $data
     * @param  string $key
     */
    public function save(array $data, $key = null)
    {
        if (is_null($key)) {
            $key = $this->defaultKey;
        }

        return $this->driver->save($data, $this->getKey($key));
    }

    /**
     * Retrieve an item from the storage by key.
     *
     * @param  string $key
     * @return Collection
     */
    public function find($key = null)
    {
        if (is_null($key)) {
            $key = $this->defaultKey;
        }

        return $this->driver->get($this->getKey($key));
    }

    /**
     * Retrieve an item from the default key object.
     *
     * @param  string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->find()->get($key);
    }

    /**
     * Delete a stored item by its key.
     *
     * @param  string $key
     */
    public function delete($key = null)
    {
        if (is_null($key)) {
            $key = $this->defaultKey;
        }

        return $this->driver->delete($this->getKey($key));
    }

    /**
     * Return all stored entries.
     *
     * @return array
     */
    public function all()
    {
        return $this->driver->all();
    }
}
