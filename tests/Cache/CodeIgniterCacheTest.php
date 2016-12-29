<?php

namespace Mpociot\BotMan\Tests;

use CI_Cache;
use Mockery as m;
use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Cache\CodeIgniterCache;

class CodeIgniterCacheTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    /** @test */
    public function has()
    {
        $driver = m::mock(CI_Cache::class);
        $driver->shouldReceive('get')->once()->andReturn('foo');

        $cache = new CodeIgniterCache($driver);
        $this->assertTrue($cache->has('foo'));
    }

    /** @test */
    public function has_not()
    {
        $driver = m::mock(CI_Cache::class);
        $driver->shouldReceive('get')->once()->andReturn(false);

        $cache = new CodeIgniterCache($driver);
        $this->assertFalse($cache->has('foo'));
    }

    /** @test */
    public function get_existing_key()
    {
        $driver = m::mock(CI_Cache::class);
        $driver->shouldReceive('get')->twice()->andReturn('bar');

        $cache = new CodeIgniterCache($driver);
        $this->assertEquals('bar', $cache->get('foo', null));
    }

    /** @test */
    public function get_non_existing_key()
    {
        $driver = m::mock(CI_Cache::class);
        $driver->shouldReceive('get')->once()->andReturn(false);

        $cache = new CodeIgniterCache($driver);
        $this->assertNull($cache->get('foo', null));
    }

    /** @test */
    public function pull_existing_key()
    {
        $driver = m::mock(CI_Cache::class);
        $driver->shouldReceive('get')->twice()->andReturn('bar');
        $driver->shouldReceive('delete')->once()->with('foo');

        $cache = new CodeIgniterCache($driver);
        $this->assertEquals('bar', $cache->pull('foo', null));
    }

    /** @test */
    public function pull_non_existing_key()
    {
        $driver = m::mock(CI_Cache::class);
        $driver->shouldReceive('get')->once()->andReturn(false);
        $driver->shouldReceive('delete')->never();

        $cache = new CodeIgniterCache($driver);
        $this->assertNull($cache->pull('foo', null));
    }

    /** @test */
    public function pull_non_existing_key_with_default_value()
    {
        $driver = m::mock(CI_Cache::class);
        $driver->shouldReceive('get')->once()->andReturn(false);
        $driver->shouldReceive('delete')->never();

        $cache = new CodeIgniterCache($driver);
        $this->assertEquals('bar', $cache->pull('foo', 'bar'));
    }

    /** @test */
    public function put()
    {
        $driver = m::mock(CI_Cache::class);
        $driver->shouldReceive('save')->once()->withArgs([
            'foo',
            'bar',
            300,
        ]);

        $cache = new CodeIgniterCache($driver);
        $cache->put('foo', 'bar', 5);
    }

    /** @test */
    public function put_with_datetime()
    {
        $driver = m::mock(CI_Cache::class);
        $driver->shouldReceive('save')->once()->withArgs([
            'foo',
            'bar',
            300,
        ]);

        $cache = new CodeIgniterCache($driver);
        $cache->put('foo', 'bar', new \DateTime('+5 minutes'));
    }
}
