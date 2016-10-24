<?php
namespace Mpociot\SlackBot\Tests;

use Mpociot\SlackBot\Tests\Fixtures\TestConversation;
use Orchestra\Testbench\TestCase;
use SlackBot;
use Cache;

class LaravelTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->app['config']->set('cache.default', 'file');
    }

    protected function getPackageProviders($app)
    {
        return [\Mpociot\SlackBot\SlackBotServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'SlackBot' => \Mpociot\SlackBot\Facades\SlackBot::class
        ];
    }

    /** @test */
    public function the_facade_works()
    {
        $this->assertFalse(SlackBot::isBot());
    }

    /** @test */
    public function the_service_provider_registers_the_token()
    {
        $this->app['config']->set('services.slack.bot_token', 'this_is_a_bot_token');
        $this->assertSame('this_is_a_bot_token', SlackBot::getToken());
    }

    /** @test */
    public function it_can_serialize_closures_using_the_bot()
    {
        $conversation = new TestConversation();

        $bot = app('slackbot');
        SlackBot::storeConversation($conversation, function($answer) use ($bot) {});

        $cached = Cache::get('conversation:-');
        $this->assertEquals($conversation, $cached['conversation']);
        $this->assertTrue(is_string($cached['next']));
    }

}