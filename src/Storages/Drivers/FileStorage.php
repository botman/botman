<?php

namespace BotMan\BotMan\Storages\Drivers;

use Illuminate\Support\Collection;
use BotMan\BotMan\Interfaces\StorageInterface;

class FileStorage implements StorageInterface
{
    /** @var string */
    private $path;

    public function __construct($path = '')
    {
        $this->path = $path;
    }

    /**
     * @param $key
     * @return string
     */
    protected function getFilename($key)
    {
        return $this->path.DIRECTORY_SEPARATOR.$key.'.json';
    }

    /**
     * Save an item in the storage with a specific key and data.
     *
     * @param  array $data
     * @param  string $key
     */
    public function save(array $data, $key)
    {
        $file = $this->getFilename($key);

        $saved = $this->get($key)->merge($data);

        if (! is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }
        file_put_contents($file, json_encode($saved->all()));
    }

    /**
     * Retrieve an item from the storage by key.
     *
     * @param  string $key
     * @return Collection
     */
    public function get($key)
    {
        $file = $this->getFilename($key);
        $data = [];
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
        }

        return Collection::make($data);
    }

    /**
     * Delete a stored item by its key.
     *
     * @param  string $key
     */
    public function delete($key)
    {
        $file = $this->getFilename($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Return all stored entries.
     *
     * @return array
     */
    public function all()
    {
        $keys = glob($this->path.'/*.json');
        $data = [];
        foreach ($keys as $key) {
            $data[] = $this->get(basename($key, '.json'));
        }

        return $data;
    }
}
