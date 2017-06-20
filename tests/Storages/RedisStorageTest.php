<?php

namespace Mpociot\BotMan\Tests;

use Redis;
use RedisException;
use PHPUnit_Framework_TestCase;
use Illuminate\Support\Collection;
use Mpociot\BotMan\Storages\Drivers\RedisStorage;

/**
 * @group integration
 */
class RedisStorageTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension required');
        }
    }

    public function tearDown()
    {
        $script = sprintf("for i, name in ipairs(redis.call('KEYS', '%s*')) do redis.call('DEL', name); end", RedisStorage::KEY_PREFIX);

        $redis = new Redis();
        $redis->connect('127.0.0.1');
        $redis->eval($script);
        $redis->close();

        $redis = new Redis();
        $redis->connect('127.0.0.1', 6380);
        $redis->auth('secret');
        $redis->eval($script);
        $redis->close();
    }

    /** @test */
    public function valid_auth()
    {
        $storage = new RedisStorage('127.0.0.1', 6380, 'secret');
        $key = 'key';
        $data = ['foo' => 1, 'bar' => new \DateTime()];
        $storage->save($data, $key);
        self::assertEquals(Collection::make($data), $storage->get($key));
    }

    /** @test */
    public function invalid_auth()
    {
        static::setExpectedException(RedisException::class);
        $storage = new RedisStorage('127.0.0.1', 6380, 'invalid');
        $key = 'key';
        $data = ['foo' => 1, 'bar' => new \DateTime()];
        $storage->save($data, $key);
    }

    /** @test */
    public function get()
    {
        $storage = new RedisStorage();
        $key = 'key';
        $data = ['foo' => 1, 'bar' => new \DateTime()];
        $storage->save($data, $key);
        self::assertEquals(Collection::make($data), $storage->get($key));
    }

    /** @test */
    public function delete()
    {
        $storage = new RedisStorage();
        $key = 'key';
        $data = ['foo' => 1, 'bar' => new \DateTime()];
        $storage->save($data, $key);

        $storage->delete($key);

        self::assertEquals(0, $storage->get($key)->count());
    }

    /** @test */
    public function get_all()
    {
        $storage = new RedisStorage();
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
}
