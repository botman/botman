<?php

namespace BotMan\BotMan\Tests\Cache;

use BotMan\BotMan\Cache\ArrayCache;
use PHPUnit\Framework\TestCase;

class ArrayCacheTest extends TestCase
{
    /** @test */
    public function has()
    {
        $cache = new ArrayCache();
        $cache->put('foo', 'bar', 1);
        $this->assertTrue($cache->has('foo'));
    }

    /** @test */
    public function has_not()
    {
        $cache = new ArrayCache();
        $this->assertFalse($cache->has('foo'));
    }

    /** @test */
    public function get_existing_key()
    {
        $cache = new ArrayCache();
        $cache->put('foo', 'bar', 5);
        $this->assertTrue($cache->has('foo'));
        $this->assertEquals('bar', $cache->get('foo'));
    }

    /** @test */
    public function get_non_existing_key()
    {
        $cache = new ArrayCache();
        $this->assertNull($cache->get('foo'));
    }

    /** @test */
    public function pull_existing_key()
    {
        $cache = new ArrayCache();
        $cache->put('foo', 'bar', 5);
        $this->assertTrue($cache->has('foo'));
        $this->assertEquals('bar', $cache->pull('foo'));
        $this->assertFalse($cache->has('foo'));
        $this->assertNull($cache->get('foo'));
    }

    /** @test */
    public function pull_non_existing_key()
    {
        $cache = new ArrayCache();
        $this->assertNull($cache->pull('foo'));
    }

    /** @test */
    public function pull_non_existing_key_with_default_value()
    {
        $cache = new ArrayCache();
        $this->assertEquals('bar', $cache->pull('foo', 'bar'));
    }
}
