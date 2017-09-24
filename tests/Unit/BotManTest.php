<?php

namespace BotMan\BotMan\tests\Unit;

use Mockery as m;
use BotMan\BotMan\BotMan;
use PHPUnit_Framework_TestCase;
use BotMan\BotMan\BotManFactory;
use Illuminate\Support\Collection;
use BotMan\BotMan\Cache\ArrayCache;
use BotMan\BotMan\Drivers\Tests\FakeDriver;
use BotMan\BotMan\Tests\Fixtures\TestConversation;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

class BotManTest extends PHPUnit_Framework_TestCase
{
    protected $cache;

    public function tearDown()
    {
        m::close();
    }

    public function setUp()
    {
        parent::setUp();
        $this->cache = new ArrayCache();
    }

    /**
     * @param $data
     * @return BotMan
     */
    protected function getBot($data)
    {
        $botman = BotManFactory::create([], $this->cache);

        $data = Collection::make($data);
        /** @var FakeDriver $driver */
        $driver = m::mock(FakeDriver::class)->makePartial();

        $driver->isBot = $data->get('is_from_bot', false);
        $driver->messages = [new IncomingMessage($data->get('message'), $data->get('sender'), $data->get('recipient'))];

        $botman->setDriver($driver);

        return $botman;
    }

    /** @test */
    public function it_can_return_stored_questions()
    {
        $botman = $this->getBot([]);

        $botman->storeConversation(new TestConversation(), function () {
        }, 'This is my question');

        $this->assertSame('This is my question', $botman->getStoredConversationQuestion());
    }
}
