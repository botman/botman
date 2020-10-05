<?php

namespace BotMan\BotMan\Tests\Cache;

use BotMan\BotMan\Cache\DoctrineCache;
use Doctrine\Common\Cache\CacheProvider;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DoctrineCacheTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function tearDown(): void
    {
        m::close();
    }

    /** @test */
    public function has()
    {
        $driver = m::mock(CacheProvider::class);
        $driver->shouldReceive('contains')->once()->andReturn(true);

        $cache = new DoctrineCache($driver);
        $this->assertTrue($cache->has('foo'));
    }

    /** @test */
    public function has_not()
    {
        $driver = m::mock(CacheProvider::class);
        $driver->shouldReceive('contains')->once()->andReturn(false);

        $cache = new DoctrineCache($driver);
        $this->assertFalse($cache->has('foo'));
    }

    /** @test */
    public function get_existing_key()
    {
        $driver = m::mock(CacheProvider::class);
        $driver->shouldReceive('fetch')->once()->andReturn('bar');

        $cache = new DoctrineCache($driver);
        $this->assertEquals('bar', $cache->get('foo', null));
    }

    /** @test */
    public function get_non_existing_key()
    {
        $driver = m::mock(CacheProvider::class);
        $driver->shouldReceive('fetch')->once()->andReturn(false);

        $cache = new DoctrineCache($driver);
        $this->assertNull($cache->get('foo', null));
    }

    /** @test */
    public function pull_existing_key()
    {
        $driver = m::mock(CacheProvider::class);
        $driver->shouldReceive('contains')->once()->andReturn(true);
        $driver->shouldReceive('fetch')->once()->andReturn('bar');
        $driver->shouldReceive('delete')->once();

        $cache = new DoctrineCache($driver);
        $this->assertEquals('bar', $cache->pull('foo', null));
    }

    /** @test */
    public function pull_non_existing_key()
    {
        $driver = m::mock(CacheProvider::class);
        $driver->shouldReceive('contains')->once()->andReturn(false);

        $cache = new DoctrineCache($driver);
        $this->assertNull($cache->pull('foo', null));
    }

    /** @test */
    public function put()
    {
        $driver = m::mock(CacheProvider::class);
        $driver->shouldReceive('save')->once()->withArgs(['foo', 'bar', 300]);

        $cache = new DoctrineCache($driver);
        $cache->put('foo', 'bar', 5);
    }

    /** @test */
    public function put_with_datetime()
    {
        $driver = m::mock(CacheProvider::class);
        $driver->shouldReceive('save')->once()->withArgs(['foo', 'bar', 300]);

        $cache = new DoctrineCache($driver);
        $cache->put('foo', 'bar', new \DateTime('+5 minutes'));
    }

    /** @test */
    public function pull_non_existing_key_with_default_value()
    {
        $driver = m::mock(CacheProvider::class);
        $driver->shouldReceive('contains')->once()->andReturn(false);

        $cache = new DoctrineCache($driver);
        $this->assertEquals('bar', $cache->pull('foo', 'bar'));
    }
}
