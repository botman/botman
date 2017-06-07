<?php

namespace BotMan\BotMan\tests;

use Cache;
use BotMan;
use Orchestra\Testbench\TestCase;
use BotMan\BotMan\Tests\Fixtures\TestConversation;

class LaravelTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->app['config']->set('cache.default', 'file');
    }

    protected function getPackageProviders($app)
    {
        return [\BotMan\BotMan\BotManServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'BotMan' => \BotMan\BotMan\Facades\BotMan::class,
        ];
    }

    /** @test */
    public function the_facade_works()
    {
        $this->assertFalse(BotMan::isBot());
    }

    /** @test */
    public function it_can_serialize_closures_using_the_bot()
    {
        $conversation = new TestConversation();

        $bot = app('botman');
        $bot->hears('foo', function () {
        });
        BotMan::storeConversation($conversation, function ($answer) {
        });

        $cached = Cache::get('conversation-'.sha1(null).'-'.sha1(null));
        $this->assertEquals($conversation, $cached['conversation']);
        $this->assertTrue(is_string($cached['next']));
    }
}
