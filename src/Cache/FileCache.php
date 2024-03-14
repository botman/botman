<?php

namespace BotMan\BotMan\Cache;

use DateTime;
use DateInterval;
use BotMan\BotMan\Interfaces\CacheInterface;
use RuntimeException;

class FileCache implements CacheInterface
{
    public function __construct(
        ?string $storageDirectory = null
    ) {

        if (! is_null($storageDirectory) && ! file_exists($storageDirectory)) {
            throw new RuntimeException(
                sprintf('The storage directory %s does not exist', $storageDirectory)
            );
        }

        $this->storageDirectory = rtrim($storageDirectory,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;;
    }

    /**
     * Determine if an item exists in the cache.
     *
     * @param  string $key
     * @return bool
     */
    public function has($key)
    {
        return file_exists($this->storageDirectory.$key.'.json');
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (! file_exists($this->storageDirectory.$key.'.json')) {
            return $default;
        }

        $data = json_decode(file_get_contents($this->storageDirectory.$key.'.json'));

        if ((new DateTime) > DateTime::createFromFormat('YmdHis', $data->expires_at)) {
            unlink($this->storageDirectory.$key.'.json');
            return $default;
        }

        return unserialize($data->value);
    }

    /**
     * Retrieve an item from the cache and delete it.
     *
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        $data = $this->get($key);

        if (! is_null($data)) {
            unlink($this->storageDirectory.$key.'.json');
        }

        return is_null($data) ? $default : $data;
    }

    /**
     * Store an item in the cache.
     *
     * @param  string $key
     * @param  mixed $value
     * @param  \DateTime|int $minutes
     * @return void
     */
    public function put($key, $value, $minutes)
    {

        if (! $minutes instanceof \DateTime) {
            $minutes = $minutes * 60;
        }

        $data = json_encode([
            'value' => serialize($value),
            'expires_at' => (new DateTime())->add(new DateInterval('PT'.$minutes.'M'))->format('YmdHis')
        ]);

        file_put_contents($this->storageDirectory.$key.'.json', $data);
    }
}
