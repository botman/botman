<?php

namespace BotMan\BotMan\Tests;

use BotMan\BotMan\Cache\ArrayCache;
use BotMan\BotMan\Cache\RedisCache;
use PHPUnit\Framework\TestCase;
use Redis;
use RedisException;

/**
 * @group integration
 * @group redis-auth
 */
class RedisCacheTest extends TestCase
{
    protected function setUp(): void
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension required');
        }
    }

    protected function tearDown(): void
    {
        $script = sprintf("for i, name in ipairs(redis.call('KEYS', '%s*')) do redis.call('DEL', name); end", RedisCache::KEY_PREFIX);

        $redis = new Redis();
        $redis->connect($this->getRedisHost());
        $redis->eval($script);
        $redis->close();

        $redis = new Redis();
        $redis->connect($this->getRedisHost(), $this->getAuthRedisPort());
        $redis->auth('secret');
        $redis->eval($script);
        $redis->close();
    }

    /** @test */
    public function valid_auth()
    {
        $cache = new RedisCache($this->getRedisHost(), $this->getAuthRedisPort(), 'secret');
        $cache->put('foo', 'bar', 1);
        static::assertTrue($cache->has('foo'));
    }

    /**
     * @test
     * @expectedException RedisException
     */
    public function invalid_auth()
    {
        $cache = new RedisCache($this->getRedisHost(), $this->getAuthRedisPort(), 'invalid');
        $cache->put('foo', 'bar', 1);
    }

    /** @test */
    public function has()
    {
        $cache = new RedisCache($this->getRedisHost());
        $cache->put('foo', 'bar', 1);
        static::assertTrue($cache->has('foo'));
    }

    /** @test */
    public function has_not()
    {
        $cache = new RedisCache($this->getRedisHost(), $this->getAuthRedisPort());
        static::assertFalse($cache->has('foo'));
    }

    /** @test */
    public function get_existing_key()
    {
        $cache = new RedisCache($this->getRedisHost(), $this->getAuthRedisPort());
        $cache->put('foo', 'bar', 5);
        static::assertTrue($cache->has('foo'));
        static::assertEquals('bar', $cache->get('foo'));
    }

    /** @test */
    public function get_non_existing_key()
    {
        $cache = new RedisCache($this->getRedisHost(), $this->getAuthRedisPort());
        static::assertNull($cache->get('foo'));
    }

    /** @test */
    public function pull_existing_key()
    {
        $cache = new RedisCache($this->getRedisHost(), $this->getAuthRedisPort());
        $cache->put('foo', 'bar', 5);
        static::assertTrue($cache->has('foo'));
        static::assertEquals('bar', $cache->pull('foo'));
        static::assertFalse($cache->has('foo'));
        static::assertNull($cache->get('foo'));
    }

    /** @test */
    public function pull_non_existing_key()
    {
        $cache = new RedisCache($this->getRedisHost(), $this->getAuthRedisPort());
        static::assertNull($cache->pull('foo'));
    }

    /** @test */
    public function pull_non_existing_key_with_default_value()
    {
        $cache = new ArrayCache();
        static::assertEquals('bar', $cache->pull('foo', 'bar'));
    }

    /**
     * Get redis host.
     *
     * @return string
     */
    protected function getRedisHost()
    {
        return getenv('REDIS_HOST') ?? '127.0.0.1';
    }

    /**
     * Get redis port.
     *
     * @return int
     */
    protected function getAuthRedisPort()
    {
        return (int) (getenv('REDIS_PORT') ?? 6380);
    }
}
