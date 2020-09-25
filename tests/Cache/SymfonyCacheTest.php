<?php

namespace BotMan\BotMan\Tests\Cache;

use BotMan\BotMan\Cache\SymfonyCache;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class SymfonyCacheTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function tearDown(): void
    {
        m::close();
    }

    /** @test */
    public function has()
    {
        $driver = m::mock(AbstractAdapter::class);
        $driver->shouldReceive('hasItem')->once()->andReturn(true);

        $cache = new SymfonyCache($driver);
        $this->assertTrue($cache->has('foo'));
    }

    /** @test */
    public function has_not()
    {
        $driver = m::mock(AbstractAdapter::class);
        $driver->shouldReceive('hasItem')->once()->andReturn(false);

        $cache = new SymfonyCache($driver);
        $this->assertFalse($cache->has('foo'));
    }

    /** @test */
    public function get_existing_key()
    {
        $item = m::mock(CacheItemInterface::class);
        $item->shouldReceive('isHit')->once()->andReturn(true);
        $item->shouldReceive('get')->once()->andReturn('bar');

        $driver = m::mock(AbstractAdapter::class);
        $driver->shouldReceive('getItem')->once()->andReturn($item);

        $cache = new SymfonyCache($driver);
        $this->assertEquals('bar', $cache->get('foo', null));
    }

    /** @test */
    public function get_non_existing_key()
    {
        $item = m::mock(CacheItemInterface::class);
        $item->shouldReceive('isHit')->once()->andReturn(false);

        $driver = m::mock(AbstractAdapter::class);
        $driver->shouldReceive('getItem')->once()->andReturn($item);

        $cache = new SymfonyCache($driver);
        $this->assertNull($cache->get('foo', null));
    }

    /** @test */
    public function pull_existing_key()
    {
        $item = m::mock(CacheItemInterface::class);
        $item->shouldReceive('isHit')->once()->andReturn(true);
        $item->shouldReceive('get')->once()->andReturn('bar');

        $driver = m::mock(AdapterInterface::class);
        $driver->shouldReceive('getItem')->once()->andReturn($item);
        $driver->shouldReceive('deleteItem')->once();

        $cache = new SymfonyCache($driver);
        $this->assertEquals('bar', $cache->pull('foo', null));
    }

    /** @test */
    public function pull_non_existing_key()
    {
        $item = m::mock(CacheItemInterface::class);
        $item->shouldReceive('isHit')->once()->andReturn(false);

        $driver = m::mock(AdapterInterface::class);
        $driver->shouldReceive('getItem')->once()->andReturn($item);

        $cache = new SymfonyCache($driver);
        $this->assertNull($cache->pull('foo', null));
    }

    /** @test */
    public function pull_non_existing_key_with_default_value()
    {
        $item = m::mock(CacheItemInterface::class);
        $item->shouldReceive('isHit')->once()->andReturn(false);

        $driver = m::mock(AdapterInterface::class);
        $driver->shouldReceive('getItem')->once()->andReturn($item);

        $cache = new SymfonyCache($driver);
        $this->assertEquals('bar', $cache->pull('foo', 'bar'));
    }

    /** @test */
    public function put()
    {
        $item = m::mock(CacheItemInterface::class);
        $item->shouldReceive('set')->once()->withArgs(['bar']);
        $item->shouldReceive('expiresAfter')->once();

        $driver = m::mock(AdapterInterface::class);
        $driver->shouldReceive('getItem')->once()->withArgs(['foo'])->andReturn($item);
        $driver->shouldReceive('save')->once();

        $cache = new SymfonyCache($driver);
        $cache->put('foo', 'bar', 5);
    }

    /** @test */
    public function put_with_datetime()
    {
        $item = m::mock(CacheItemInterface::class);
        $item->shouldReceive('set')->once()->withArgs(['bar']);
        $item->shouldReceive('expiresAt')->once();

        $driver = m::mock(AdapterInterface::class);
        $driver->shouldReceive('getItem')->once()->withArgs(['foo'])->andReturn($item);
        $driver->shouldReceive('save')->once();

        $cache = new SymfonyCache($driver);
        $cache->put('foo', 'bar', new \DateTime('+5 minutes'));
    }
}
