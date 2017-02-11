<?php

namespace Mpociot\BotMan\Tests\Storages;

use PHPUnit_Framework_TestCase;
use Mpociot\BotMan\Storages\Storage;
use Mpociot\BotMan\Storages\Drivers\FileStorage;

class StorageTest extends PHPUnit_Framework_TestCase
{
    /** @var Storage */
    protected $storage;

    /** @var FileStorage */
    protected $driver;

    public function setUp()
    {
        $this->driver = new FileStorage(__DIR__.'/../Fixtures/storage');
        $this->storage = new Storage($this->driver);
        parent::setUp();
    }

    public function tearDown()
    {
        exec('rm -rf '.__DIR__.'/../Fixtures/storage/*.json');
    }

    /** @test */
    public function it_uses_the_default_key()
    {
        $this->storage->setDefaultKey('my_key');
        $this->storage->save(['json' => 'encoded']);

        $data = $this->storage->get();
        $this->assertSame($data->toArray(), ['json' => 'encoded']);

        $this->storage->delete();
        $data = $this->storage->get();
        $this->assertSame($data->toArray(), []);
    }

    /** @test */
    public function it_uses_the_prefix()
    {
        $this->storage->setPrefix('botman_');
        $this->storage->save(['json' => 'encoded'], 'my_key');

        $data = $this->driver->get(sha1('botman_my_key'));
        $this->assertSame($data->toArray(), ['json' => 'encoded']);

        $data = $this->storage->get('my_key');
        $this->assertSame($data->toArray(), ['json' => 'encoded']);
    }

    /** @test */
    public function save_and_get()
    {
        $this->storage->save(['json' => 'encoded'], 'my_key');
        $data = $this->storage->get('my_key');
        $this->assertSame($data->toArray(), ['json' => 'encoded']);
    }

    /** @test */
    public function save_appends_data()
    {
        $this->storage->save(['key_one' => 'value_one'], 'my_key');
        $this->storage->save(['key_two' => 'value_two'], 'my_key');

        $data = $this->storage->get('my_key');

        $this->assertSame($data->toArray(), [
            'key_one' => 'value_one',
            'key_two' => 'value_two',
        ]);
    }

    /** @test */
    public function delete()
    {
        $this->storage->save(['json' => 'encoded'], 'my_key');
        $this->assertSame('encoded', $this->storage->get('my_key')->get('json'));

        $this->storage->delete('my_key');
        $this->assertNull($this->storage->get('my_key')->get('json'));
    }

    /** @test */
    public function all()
    {
        $this->storage->save(['json' => 'in_key_1'], 'my_key');
        $this->storage->save(['json' => 'in_key_2'], 'my_other_key');

        $all = $this->storage->all();

        $this->assertCount(2, $all);
        $this->assertSame('in_key_1', $all[0]->get('json'));
        $this->assertSame('in_key_2', $all[1]->get('json'));
    }
}
