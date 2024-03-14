<?php

namespace BotMan\BotMan\Tests\Cache;

use BotMan\BotMan\Cache\FileCache;
use PHPUnit\Framework\TestCase;

class FileCacheTest extends TestCase
{
    /** @test */
    public function has()
    {
        $cache = new FileCache(dirname(__FILE__));
        $cache->put('foo', 'bar', 1);
        $this->assertTrue($cache->has('foo'));

        unlink(dirname(__FILE__).'\foo.json');
    }

    /** @test */
    public function has_not()
    {
        $cache = new FileCache(dirname(__FILE__));
        $this->assertFalse($cache->has('foo'));
    }

    /** @test */
    public function get_existing_key()
    {
        $cache = new FileCache(dirname(__FILE__));
        $cache->put('foo', 'bar', 5);
        $this->assertTrue($cache->has('foo'));
        $this->assertEquals('bar', $cache->get('foo'));

        unlink(dirname(__FILE__).'\foo.json');
    }

    /** @test */
    public function get_non_existing_key()
    {
        $cache = new FileCache(dirname(__FILE__));
        $this->assertNull($cache->get('foo'));
    }

    /** @test */
    public function pull_existing_key()
    {
        $cache = new FileCache(dirname(__FILE__));
        $cache->put('foo', 'bar', 5);
        $this->assertTrue($cache->has('foo'));
        $this->assertEquals('bar', $cache->pull('foo'));
        $this->assertFalse($cache->has('foo'));
        $this->assertNull($cache->get('foo'));
    }

    /** @test */
    public function pull_non_existing_key()
    {
        $cache = new FileCache();
        $this->assertNull($cache->pull('foo'));
    }

    /** @test */
    public function pull_non_existing_key_with_default_value()
    {
        $cache = new FileCache();
        $this->assertEquals('bar', $cache->pull('foo', 'bar'));
    }
}
