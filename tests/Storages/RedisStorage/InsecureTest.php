<?php

namespace BotMan\BotMan\Tests\RedisStorage;

use BotMan\BotMan\Storages\Drivers\RedisStorage;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Redis;
use RedisException;

/**
 * @group integration
 * @group redis-insecure
 */
class InsecureTest extends TestCase
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

        $redis = new Redis();
        $redis->connect($this->getRedisHost(), $this->getRedisPort());
        $redis->eval($script);
        $redis->close();
    }

    /** @test */
    public function get()
    {
        $storage = new RedisStorage($this->getRedisHost(), $this->getRedisPort());
        $key = 'key';
        $data = ['foo' => 1, 'bar' => new \DateTime()];
        $storage->save($data, $key);
        self::assertEquals(Collection::make($data), $storage->get($key));
    }

    /** @test */
    public function delete()
    {
        $storage = new RedisStorage($this->getRedisHost(), $this->getRedisPort());
        $key = 'key';
        $data = ['foo' => 1, 'bar' => new \DateTime()];
        $storage->save($data, $key);

        $storage->delete($key);

        self::assertEquals(0, $storage->get($key)->count());
    }

    /** @test */
    public function get_all()
    {
        $storage = new RedisStorage($this->getRedisHost(), $this->getRedisPort());
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
    protected function getRedisPort()
    {
        return (int) ($_ENV['REDIS_PORT'] ?? 6379);
    }
}
