<?php

namespace Mpociot\BotMan;

use Illuminate\Support\ServiceProvider;
use Mpociot\BotMan\Cache\LaravelCache;

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
            return BotManFactory::create(config('services.botman', []), $app->make('request'), new LaravelCache());
        });
    }
}
