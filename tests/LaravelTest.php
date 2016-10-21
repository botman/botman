<?php

class LaravelTest extends \Orchestra\Testbench\TestCase
{

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

}