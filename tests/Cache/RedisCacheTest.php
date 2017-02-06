<?php

namespace Mpociot\BotMan\Tests;

use Redis;
use Mpociot\BotMan\Cache\RedisCache;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Cache\ArrayCache;

class RedisCacheTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        $redis = new Redis();
        $redis->connect('127.0.0.1');
        $redis->flushAll();
    }

    /** @test */
    public function has()
    {
        $cache = new RedisCache();
        $cache->put('foo', 'bar', 1);
        $this->assertTrue($cache->has('foo'));
    }

    /** @test */
    public function has_not()
    {
        $cache = new RedisCache();
        $this->assertFalse($cache->has('foo'));
    }

    /** @test */
    public function get_existing_key()
    {
        $cache = new RedisCache();
        $cache->put('foo', 'bar', 5);
        $this->assertTrue($cache->has('foo'));
        $this->assertEquals('bar', $cache->get('foo'));
    }

    /** @test */
    public function get_non_existing_key()
    {
        $cache = new RedisCache();
        $this->assertNull($cache->get('foo'));
    }

    /** @test */
    public function pull_existing_key()
    {
        $cache = new RedisCache();
        $cache->put('foo', 'bar', 5);
        $this->assertTrue($cache->has('foo'));
        $this->assertEquals('bar', $cache->pull('foo'));
        $this->assertFalse($cache->has('foo'));
        $this->assertNull($cache->get('foo'));
    }

    /** @test */
    public function pull_non_existing_key()
    {
        $cache = new RedisCache();
        $this->assertNull($cache->pull('foo'));
    }

    /** @test */
    public function pull_non_existing_key_with_default_value()
    {
        $cache = new ArrayCache();
        $this->assertEquals('bar', $cache->pull('foo', 'bar'));
    }
}
