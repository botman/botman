<?php

namespace Mpociot\SlackBot;

use Frlnc\Slack\Core\Commander;
use Frlnc\Slack\Http\CurlInteractor;
use Frlnc\Slack\Http\SlackResponseFactory;
use Illuminate\Support\ServiceProvider;
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
            $interactor = new CurlInteractor;
            $interactor->setResponseFactory(new SlackResponseFactory);

            return new SlackBot(new Serializer(), new Commander('', $interactor), $app->make('request'));
        });
    }
}
