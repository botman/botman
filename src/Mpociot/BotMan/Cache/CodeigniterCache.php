<?php

namespace Mpociot\BotMan\Cache;

use Mpociot\BotMan\Interfaces\CacheInterface;

class CodeigniterCache implements CacheInterface {
	/**
	 * @var array
	 */
	private $cache;

	/**
	 * @param Cache $driver
	 */
	public function __construct($driver) {
		$this->cache = $driver;
	}

	/**
	 * Determine if an item exists in the cache.
	 *
	 * @param  string $key
	 * @return bool
	 */
	public function has($key) {
		return $this->cache->get($key);
	}

	/**
	 * Retrieve an item from the cache by key.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function get($key, $default = null) {
		if ($this->cache->get($key)) {
			return $this->cache->get($key);
		}

		return $default;
	}

	/**
	 * Retrieve an item from the cache and delete it.
	 *
	 * @param  string $key
	 * @param  mixed $default
	 * @return mixed
	 */
	public function pull($key, $default = null) {
		if ($this->cache->get($key)) {
			$cached = $this->cache->get($key);
			$this->cache->delete($key);
			return $cached;
		}

		return $default;
	}

	/**
	 * Store an item in the cache.
	 *
	 * @param  string $key
	 * @param  mixed $value
	 * @param  \DateTime|int $minutes
	 * @return void
	 */
	public function put($key, $value, $minutes) {
		$this->cache->save($key, $value, $minutes);
	}
}
