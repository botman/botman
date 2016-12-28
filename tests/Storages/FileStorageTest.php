<?php

namespace Mpociot\BotMan\Tests\Storages;

use Mpociot\BotMan\Storages\FileStorage;
use PHPUnit_Framework_TestCase;

class FileStorageTest extends PHPUnit_Framework_TestCase
{
    /** @var FileStorage */
    protected $storage;

    public function setUp()
    {
        $this->storage = new FileStorage(__DIR__.'/../Fixtures/storage');
        parent::setUp();
    }

    public function tearDown()
    {
        exec('rm -rf '.__DIR__.'/../Fixtures/storage/*.json');
    }

    /** @test */
    public function save_and_get()
    {
        $this->storage->save('my_key', ['json' => 'encoded']);
        $data = $this->storage->get('my_key');
        $this->assertSame($data->toArray(), ['json' => 'encoded']);
    }

    /** @test */
    public function save_appends_data()
    {
        $this->storage->save('my_key', ['key_one' => 'value_one']);
        $this->storage->save('my_key', ['key_two' => 'value_two']);

        $data = $this->storage->get('my_key');

        $this->assertSame($data->toArray(), [
            'key_one' => 'value_one',
            'key_two' => 'value_two'
        ]);
    }

    /** @test */
    public function delete()
    {
        $this->storage->save('my_key', ['json' => 'encoded']);
        $this->assertSame('encoded', $this->storage->get('my_key')->get('json'));

        $this->storage->delete('my_key');
        $this->assertNull($this->storage->get('my_key')->get('json'));
    }

    /** @test */
    public function all()
    {
        $this->storage->save('my_key', ['json' => 'in_key_1']);
        $this->storage->save('my_other_key', ['json' => 'in_key_2']);
        $all = $this->storage->all();
        $this->assertCount(2, $all);
        $this->assertSame('in_key_1', $all[0]->get('json'));
        $this->assertSame('in_key_2', $all[1]->get('json'));
    }
}
