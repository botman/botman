<?php

namespace Mpociot\SlackBot;

use Illuminate\Support\ServiceProvider;
use Mpociot\SlackBot\Cache\LaravelCache;
use Mpociot\SlackBot\Http\Curl;
use SuperClosure\Serializer;

class SlackBotServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('slackbot', function ($app) {
            $slackbot = new SlackBot(
                new Serializer(),
                $app->make('request'),
                new LaravelCache(),
                new DriverManager(config('services.slackbot', []), new Curl())
            );

            return $slackbot;
        });
    }
}
