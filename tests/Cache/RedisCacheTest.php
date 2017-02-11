<?php

namespace Mpociot\BotMan\Tests;

use Redis;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Cache\ArrayCache;
use Mpociot\BotMan\Cache\RedisCache;

/**
 * @group integration
 */
class RedisCacheTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        $redis = new Redis();
        $redis->connect('127.0.0.1');
        $script = sprintf("for i, name in ipairs(redis.call('KEYS', '%s*')) do redis.call('DEL', name); end", RedisCache::KEY_PREFIX);
        $redis->eval($script);
    }

    /** @test */
    public function has()
    {
        $cache = new RedisCache();
        $cache->put('foo', 'bar', 1);
        static::assertTrue($cache->has('foo'));
    }

    /** @test */
    public function has_not()
    {
        $cache = new RedisCache();
        static::assertFalse($cache->has('foo'));
    }

    /** @test */
    public function get_existing_key()
    {
        $cache = new RedisCache();
        $cache->put('foo', 'bar', 5);
        static::assertTrue($cache->has('foo'));
        static::assertEquals('bar', $cache->get('foo'));
    }

    /** @test */
    public function get_non_existing_key()
    {
        $cache = new RedisCache();
        static::assertNull($cache->get('foo'));
    }

    /** @test */
    public function pull_existing_key()
    {
        $cache = new RedisCache();
        $cache->put('foo', 'bar', 5);
        static::assertTrue($cache->has('foo'));
        static::assertEquals('bar', $cache->pull('foo'));
        static::assertFalse($cache->has('foo'));
        static::assertNull($cache->get('foo'));
    }

    /** @test */
    public function pull_non_existing_key()
    {
        $cache = new RedisCache();
        static::assertNull($cache->pull('foo'));
    }

    /** @test */
    public function pull_non_existing_key_with_default_value()
    {
        $cache = new ArrayCache();
        static::assertEquals('bar', $cache->pull('foo', 'bar'));
    }
}
