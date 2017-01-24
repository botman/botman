<?php

require 'vendor/autoload.php';

use React\EventLoop\Factory;
use Mpociot\BotMan\BotManFactory;

$loop = Factory::create();
$botman = BotManFactory::createForRTM([
    'slack_token' => 'xoxb-35826718022-uDU4FWWRWH0lprnTDG7Yznvj',
], $loop);

$botman->hears('keyword', function ($bot) {
    $bot->replyInThread('I heard you! :)', [
        'attachments' => json_encode([
            [
                'color' => '#36a64f',
                'title' => 'Slack API Documentation',
            ],
        ]),
    ]);
});

$botman->hears('convo', function ($bot) {
    $bot->startConversation(new ExampleConversation());
});

$loop->run();
