# BotMan with Slack

**Note:** You have three possibilities to set up Slack to connect with BotMan:

#### Use a bot with the Slack Realtime API

**Pros:** 
   * Your bot user will be a real bot and be able to join channels / talk to in direct messages
   * Very easy to set up

> **Note:** As of now, you can not yet use [interactive message buttons](https://api.slack.com/docs/message-buttons) with BotMan and Slack Realtime API.

---

#### Use an [outgoing webhook](https://api.slack.com/outgoing-webhooks)
 
**Pros:** 
  * Very easy to set up
 
**Cons:** 
  * You don't have a bot user in your channel / no direct messaging
  * You can not send and interact with [interactive message buttons](https://api.slack.com/docs/message-buttons)
  * Your bot will be limited to specific channels (those you set up when adding the outgoing webhook to your Slack team)
    
---

#### Use a bot in combination with the Slack Event API

**Pros:** 
  * All BotMan features available

**Cons:** 
  * Pretty cumbersome to set up *Note:* Let the folks from [SlackHQ](https://twitter.com/slackhq) know this. If we make enough noise, they'll hopefully simplify the bot token creation process!
  * Your bot user will appear offline

---

## Usage with the Realtime API

> **Note:** The Realtime API requires the additional compose package `mpociot/slack-client` to be installed.
> 
> Simply install it using `composer require mpociot/slack-client`.

Add a new Bot user to your Slack team and take note of the bot token slack gives you.
Use this token as your `slack_token` configuration parameter.

As the Realtime API needs a websocket, you need to create a PHP script that will hold your bot logic, as you can not use the HTTP controller way for it.

```php
<?php
require 'vendor/autoload.php';

use Mpociot\BotMan\BotManFactory;
use React\EventLoop\Factory;

$loop = Factory::create();
$botman = BotManFactory::createForRTM([
    'slack_token' => 'YOUR-SLACK-BOT-TOKEN'
], $loop);

$botman->hears('keyword', function($bot) {
    $bot->reply('I heard you! :)');
});

$botman->hears('convo', function($bot) {
    $bot->startConversation(new ExampleConversation());
});

$loop->run();
```

Then simply run this file by using `php my-bot-file.php` - your bot should connect to your Slack team and respond to the messages.

## Usage with an outgoing webhook

Add a new "Outgoing Webhook" integration to your Slack team - this URL needs to point to the controller where your BotMan bot is living in.

To let BotMan listen to all incoming messages, do **not** specify a trigger word, but define a channel instead.

> If you are using Laravel Valet, you can get an external URL for testing using the `valet share` command.

With the Webhook implementation, there is no need to add a `slack_token` configuration. 
_Yes - that is all you need to do, to use BotMan_
