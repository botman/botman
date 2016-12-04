<?php

namespace Mpociot\BotMan;

use Mpociot\BotMan\Cache\LaravelCache;
use Illuminate\Support\ServiceProvider;

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
