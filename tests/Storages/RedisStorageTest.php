<?php

namespace BotMan\BotMan\Tests;

use BotMan\BotMan\Storages\Drivers\RedisStorage;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Redis;
use RedisException;

/**
 * @group integration
 * @group redis-auth
 */
class RedisStorageTest extends TestCase
{
    protected function setUp(): void
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension required');
        }
    }

    protected function tearDown(): void
    {
        $script = sprintf("for i, name in ipairs(redis.call('KEYS', '%s*')) do redis.call('DEL', name); end", RedisStorage::KEY_PREFIX);

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

        $storage = new RedisStorage($this->getRedisHost(), $this->getAuthRedisPort(), 'secret');
        $key = 'key';
        $data = ['foo' => 1, 'bar' => new \DateTime()];
        $storage->save($data, $key);
        self::assertEquals(Collection::make($data), $storage->get($key));
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

        $storage = new RedisStorage($this->getRedisHost(), $this->getAuthRedisPort(), 'invalid');
        $key = 'key';
        $data = ['foo' => 1, 'bar' => new \DateTime()];
        $storage->save($data, $key);
    }

    /** @test */
    public function get()
    {
        if($this->isSecure()) {
            $this->markTestSkipped('This function needs an insecure instance');
        }

        $storage = new RedisStorage($this->getRedisHost(), $this->getAuthRedisPort());
        $key = 'key';
        $data = ['foo' => 1, 'bar' => new \DateTime()];
        $storage->save($data, $key);
        self::assertEquals(Collection::make($data), $storage->get($key));
    }

    /** @test */
    public function delete()
    {
        if($this->isSecure()) {
            $this->markTestSkipped('This function needs an insecure instance');
        }

        $storage = new RedisStorage($this->getRedisHost(), $this->getAuthRedisPort());
        $key = 'key';
        $data = ['foo' => 1, 'bar' => new \DateTime()];
        $storage->save($data, $key);

        $storage->delete($key);

        self::assertEquals(0, $storage->get($key)->count());
    }

    /** @test */
    public function get_all()
    {
        if($this->isSecure()) {
            $this->markTestSkipped('This function needs an insecure instance');
        }

        $storage = new RedisStorage($this->getRedisHost(), $this->getAuthRedisPort());
        $key1 = 'key1';
        $data1 = ['foo' => 1, 'bar' => new \DateTime()];
        $storage->save($data1, $key1);

        $key2 = 'key2';
        $data2 = ['foo' => 'alice', 'bar' => new \DateTime(), 1 => 'bob'];
        $storage->save($data2, $key2);

        $items = $storage->all();

        self::assertCount(2, $items);
        self::assertEquals(Collection::make($data1), $items[$key1]);
        self::assertEquals(Collection::make($data2), $items[$key2]);
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
