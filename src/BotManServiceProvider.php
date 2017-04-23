<?php

namespace Mpociot\BotMan;

use Mpociot\BotMan\Cache\LaravelCache;
use Illuminate\Support\ServiceProvider;
use Mpociot\BotMan\Storages\Drivers\FileStorage;

class BotManServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('botman', function ($app) {
            $storage = new FileStorage(storage_path('botman'));

            return BotManFactory::create(config('services.botman', []), new LaravelCache(), $app->make('request'), $storage);
        });
    }
}
