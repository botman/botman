<?php

namespace BotMan\BotMan;

use BotMan\BotMan\Cache\LaravelCache;
use Illuminate\Support\ServiceProvider;
use BotMan\BotMan\Container\LaravelContainer;
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

            $botman = BotManFactory::create(config('botman', []), new LaravelCache(), $app->make('request'),
                $storage);

            $botman->setContainer(new LaravelContainer($this->app));

            return $botman;
        });
    }
}
