# PHP SlackBot - Create Slack bots in PHP with ease

[![Build Status](https://travis-ci.org/mpociot/slackbot.svg?branch=master)](https://travis-ci.org/mpociot/slackbot)
[![codecov](https://codecov.io/gh/mpociot/slackbot/branch/master/graph/badge.svg)](https://codecov.io/gh/mpociot/slackbot)

SlackBot is a framework agnostic PHP library that is designed to simplify the task of developing innovative bots for [Slack](http://slack.com). 

## Getting Started

1) Open the Laravel/Symfony/PHP project your new Bot will live in
2) [Install SlackBot with composer](#installation-using-composer)
3) Obtain a Bot Token on Slack
4) Make your application respond to Slack Event requests
5) Implement your bot logic
 
## Installation using Composer

Require this package with composer using the following command:

```sh
$ composer require mpociot/slackbot
```

### Using SlackBot within a Laravel app

SlackBot comes with a Service Provider to make using this library in your [Laravel](http://laravel.com) application as simple as possible.

Go to your `config/app.php` and add the service provider:

```php
Mpociot\SlackBot\SlackBotServiceProvider::class,
```

Also add the alias / facade:

```php
'SlackBot' => Mpociot\SlackBot\Facades\SlackBot::class
```

That's it.

## Core Concepts

Bots built with SlackBot have a few key capabilities, which can be used to create clever, conversational applications. 
These capabilities map to the way real human people talk to each other.

Bots can [hear things](#receiving-messages), [say things and reply](#sending-messages) to what they hear.

With these two building blocks, almost any type of conversation can be created.

## Basic Usage

Here's an example of using SlackBot with Slack's Event API.

This sample bot listens for the word "hello" - either in a direct message (a private message inside Slack between the user and the bot) or in a message the bot user is invited to.

```php

$slackbot = new SlackBot();
$slackbot->initialize(<my_slack_bot_token>);

// give the bot something to listen for.
$slackbot->hears('hello', function (SlackBot $bot, $message) {
  $bot->reply('Hello yourself.');
});
```

# Developing with SlackBot

Table of Contents

* [Receiving Messages](#receiving-messages)
* [Sending Messages](#sending-messages)

## Receiving Messages

### Matching Patterns and Keywords with `hears()`

SlackBot provides a `hears()` function, which will listen to specific patterns in public and/or private channels.

| Argument | Description
|--- |---
| pattern | A string with a regular expressions to match
| callback | Callback function that receives a SlackBot object, as well as additional matching regular expression parameters
| in | Defines where the Bot should listen for this message. Can be either `SlackBot::DIRECT_MESSAGE` or `SlackBot::PUBLIC_CHANNEL`

```php
$slackbot->hears('keyword', function(SlackBot $bot, $message) {

  // do something to respond to message
  $bot->reply('You used a keyword!');

});
```

When using the built in regular expression matching, the results of the expression will be passed to the callback function. For example:

```php
$slackbot->hears('open the {doorType} doors', function(SlackBot $bot, $doorType) {
  if ($doorType === 'pod bay') {
    return $bot->reply('I\'m sorry, Dave. I\'m afraid I can\'t do that.');
  }
  return $bot->reply('Okay');
});
```

## Example usage

```php
// Listen to simple commands
SlackBot::hears('Hello', function (SlackBot $bot) {
    $bot->reply('Hi there!');
});

// Include regular expression matches
SlackBot::hears('Call me {name} the {attribute}', function (SlackBot $bot, $name, $attribute) {
    $bot->reply('Hi '.$name.'! You truly are '.$attribute);
});

// Use conversations
SlackBot::hears('order pizza', function (SlackBot $bot, $matches) {
    $bot->startConversation(new OrderPizzaConversation());
});

// Only listen in direct messages
SlackBot::hears('order pizza', function (SlackBot $bot, $matches) {
    $bot->startConversation(new OrderPizzaConversation());
}, SlackBot::DIRECT_MESSAGE);

// Default reply if nothing else matches
SlackBot::fallback(function(SlackBot $bot) {
    $bot->reply("I don't understand a word you just said.");
});

// Start listening
SlackBot::listen();
```

## Conversation Syntax

```php
use Mpociot\SlackBot\Conversation;

class OrderPizzaConversation extends Conversation
{

    protected $size;
    
    protected $toppings;
    
    public function askSize()
    {
    
        $question = Question::create('Which pizza do you want?')
                    ->addButton(
                        Button::create('Extra Large')->value('xl')
                    )
                    ->addButton(
                        Button::create('Mega Large')->value('xxl')
                    );
                    
        $this->ask($question, function($answer) {
            $this->say('Got you - your pizza needs to be '.$answer->getText());
            $this->size = $answer->getValue();
            
            $this->askTopping();
        });
    }
    
    public function askTopping()
    {
        $this->ask('What toppings do you want?', function($answer) {
            $this->say('Okay, I\'ll put some '.$answer->getText().' on your pizza');
            $this->toppings = $answer->getText();
            
        });
    }
    
    public function run()
    {
        $this->askSize();
    }
}
```

## License

SlackBot is free software distributed under the terms of the MIT license.
