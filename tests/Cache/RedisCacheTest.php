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

        if (! $this->isSecure())
        {
            $redis = new Redis();
            $redis->connect($this->getRedisHost(), $this->getAuthRedisPort());
            $redis->eval($script);
            $redis->close();
        } else {
            $redis = new Redis();
            $redis->connect($this->getRedisHost(), $this->getAuthRedisPort());
            $redis->auth('secret');
            $redis->eval($script);
            $redis->close();
        }
    }

    /** @test */
    public function valid_auth()
    {
        if(! $this->isSecure()) {
            $this->markTestSkipped('This function needs a secure instance');
        }

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
        if(! $this->isSecure()) {
            $this->markTestSkipped('This function needs a secure instance');
        }

        $cache = new RedisCache($this->getRedisHost(), $this->getAuthRedisPort(), 'invalid');
        $cache->put('foo', 'bar', 1);
    }

    /** @test */
    public function has()
    {
        if($this->isSecure()) {
            $this->markTestSkipped('This function needs an isecure instance');
        }

        $cache = new RedisCache($this->getRedisHost(), $this->getAuthRedisPort());
        $cache->put('foo', 'bar', 1);
        static::assertTrue($cache->has('foo'));
    }

    /** @test */
    public function has_not()
    {
        if($this->isSecure()) {
            $this->markTestSkipped('This function needs an isecure instance');
        }

        $cache = new RedisCache($this->getRedisHost(), $this->getAuthRedisPort());
        static::assertFalse($cache->has('foo'));
    }

    /** @test */
    public function get_existing_key()
    {
        if($this->isSecure()) {
            $this->markTestSkipped('This function needs an isecure instance');
        }

        $cache = new RedisCache($this->getRedisHost(), $this->getAuthRedisPort());
        $cache->put('foo', 'bar', 5);
        static::assertTrue($cache->has('foo'));
        static::assertEquals('bar', $cache->get('foo'));
    }

    /** @test */
    public function get_non_existing_key()
    {
        if($this->isSecure()) {
            $this->markTestSkipped('This function needs an isecure instance');
        }

        $cache = new RedisCache($this->getRedisHost(), $this->getAuthRedisPort());
        static::assertNull($cache->get('foo'));
    }

    /** @test */
    public function pull_existing_key()
    {
        if($this->isSecure()) {
            $this->markTestSkipped('This function needs an isecure instance');
        }

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
        if($this->isSecure()) {
            $this->markTestSkipped('This function needs an isecure instance');
        }

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
        return $_ENV['REDIS_HOST'] ?? '127.0.0.1';
    }

    /**
     * Get redis port.
     *
     * @return int
     */
    protected function getAuthRedisPort()
    {
        return (int) ($_ENV['REDIS_PORT'] ?? 6380);
    }

    /**
     * is secure.
     *
     * @return int
     */
    protected function isSecure()
    {
        return (bool) ($_ENV['REDIS_SECURE'] ?? false);
    }
}
