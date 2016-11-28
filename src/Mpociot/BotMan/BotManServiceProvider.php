<?php

namespace Mpociot\BotMan;

use Illuminate\Support\ServiceProvider;
use Mpociot\BotMan\Cache\LaravelCache;
use Mpociot\BotMan\Http\Curl;
use SuperClosure\Serializer;

class BotManServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('botman', function ($app) {
            $botman = new BotMan(
                new Serializer(),
                $app->make('request'),
                new LaravelCache(),
                new DriverManager(config('services.botman', []), new Curl())
            );

            return $botman;
        });
    }
}
