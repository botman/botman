<?php

namespace Mpociot\BotMan\Tests;

use Mockery as m;
use PHPUnit_Framework_TestCase;
use Psr\Cache\CacheItemInterface;
use Mpociot\BotMan\Cache\Psr6Cache;
use Psr\Cache\CacheItemPoolInterface;

class Psr6CacheTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    /** @test */
    public function has()
    {
        $driver = m::mock(CacheItemPoolInterface::class);
        $driver->shouldReceive('hasItem')->once()->andReturn(true);

        $cache = new Psr6Cache($driver);
        $this->assertTrue($cache->has('foo'));
    }

    /** @test */
    public function has_not()
    {
        $driver = m::mock(CacheItemPoolInterface::class);
        $driver->shouldReceive('hasItem')->once()->andReturn(false);

        $cache = new Psr6Cache($driver);
        $this->assertFalse($cache->has('foo'));
    }

    /** @test */
    public function get_existing_key()
    {
        $item = m::mock(CacheItemInterface::class);
        $item->shouldReceive('isHit')->once()->andReturn(true);
        $item->shouldReceive('get')->once()->andReturn('bar');

        $driver = m::mock(CacheItemPoolInterface::class);
        $driver->shouldReceive('getItem')->once()->andReturn($item);

        $cache = new Psr6Cache($driver);
        $this->assertEquals('bar', $cache->get('foo', null));
    }

    /** @test */
    public function get_non_existing_key()
    {
        $item = m::mock(CacheItemInterface::class);
        $item->shouldReceive('isHit')->once()->andReturn(false);

        $driver = m::mock(CacheItemPoolInterface::class);
        $driver->shouldReceive('getItem')->once()->andReturn($item);

        $cache = new Psr6Cache($driver);
        $this->assertNull($cache->get('foo', null));
    }

    /** @test */
    public function pull_existing_key()
    {
        $item = m::mock(CacheItemInterface::class);
        $item->shouldReceive('isHit')->once()->andReturn(true);
        $item->shouldReceive('get')->once()->andReturn('bar');

        $driver = m::mock(CacheItemPoolInterface::class);
        $driver->shouldReceive('getItem')->once()->andReturn($item);
        $driver->shouldReceive('deleteItem')->once();

        $cache = new Psr6Cache($driver);
        $this->assertEquals('bar', $cache->pull('foo', null));
    }

    /** @test */
    public function pull_non_existing_key()
    {
        $item = m::mock(CacheItemInterface::class);
        $item->shouldReceive('isHit')->once()->andReturn(false);

        $driver = m::mock(CacheItemPoolInterface::class);
        $driver->shouldReceive('getItem')->once()->andReturn($item);

        $cache = new Psr6Cache($driver);
        $this->assertNull($cache->pull('foo', null));
    }

    /** @test */
    public function pull_non_existing_key_with_default_value()
    {
        $item = m::mock(CacheItemInterface::class);
        $item->shouldReceive('isHit')->once()->andReturn(false);

        $driver = m::mock(CacheItemPoolInterface::class);
        $driver->shouldReceive('getItem')->once()->andReturn($item);

        $cache = new Psr6Cache($driver);
        $this->assertEquals('bar', $cache->pull('foo', 'bar'));
    }

    /** @test */
    public function put()
    {
        $item = m::mock(CacheItemInterface::class);
        $item->shouldReceive('set')->once()->withArgs(['bar']);
        $item->shouldReceive('expiresAfter')->once();

        $driver = m::mock(CacheItemPoolInterface::class);
        $driver->shouldReceive('getItem')->once()->withArgs(['foo'])->andReturn($item);
        $driver->shouldReceive('save')->once();

        $cache = new Psr6Cache($driver);
        $cache->put('foo', 'bar', 5);
    }

    /** @test */
    public function put_with_datetime()
    {
        $item = m::mock(CacheItemInterface::class);
        $item->shouldReceive('set')->once()->withArgs(['bar']);
        $item->shouldReceive('expiresAt')->once();

        $driver = m::mock(CacheItemPoolInterface::class);
        $driver->shouldReceive('getItem')->once()->withArgs(['foo'])->andReturn($item);
        $driver->shouldReceive('save')->once();

        $cache = new Psr6Cache($driver);
        $cache->put('foo', 'bar', new \DateTime('+5 minutes'));
    }
}
