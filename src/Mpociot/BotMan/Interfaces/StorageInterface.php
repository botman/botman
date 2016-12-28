<?php

namespace Mpociot\BotMan\Interfaces;

use Illuminate\Support\Collection;

interface StorageInterface
{
    /**
     * Save an item in the storage with a specific key and data.
     *
     * @param  string  $key
     * @param  array  $data
     */
    public function save($key, array $data);

    /**
     * Retrieve an item from the storage by key.
     *
     * @param  string  $key
     * @return Collection
     */
    public function get($key);

    /**
     * Delete a stored item by its key.
     *
     * @param  string  $key
     */
    public function delete($key);

    /**
     * Return all stored entries.
     *
     * @return array
     */
    public function all();
}
