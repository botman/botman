<?php

namespace BotMan\BotMan\tests\Unit;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Cache\ArrayCache;
use BotMan\BotMan\Drivers\Tests\FakeDriver;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Tests\Fixtures\TestConversation;
use Illuminate\Support\Collection;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class BotManTest extends TestCase
{
    protected $cache;

    protected function tearDown(): void
    {
        m::close();
    }

    protected function setUp(): void
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
