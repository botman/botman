<?php

namespace BotMan\BotMan;

use BotMan\BotMan\Cache\LaravelCache;
use Illuminate\Support\ServiceProvider;
use BotMan\BotMan\Storages\Drivers\FileStorage;

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

            return BotManFactory::create(config('botman', []), new LaravelCache(), $app->make('request'),
                $storage);
        });
    }
}
