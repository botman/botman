# PHP BotMan 🤖 Create messaging bots in PHP with ease

[![Build Status](https://travis-ci.org/mpociot/botman.svg?branch=master)](https://travis-ci.org/mpociot/botman)
[![codecov](https://codecov.io/gh/mpociot/botman/branch/master/graph/badge.svg)](https://codecov.io/gh/mpociot/botman)
[![Packagist](https://img.shields.io/packagist/l/mpociot/botman.svg)]()

BotMan is a framework agnostic PHP library that is designed to simplify the task of developing innovative bots for multiple messaging platforms, including [Slack](http://slack.com), [Telegram](http://telegram.me), [Microsoft Bot Framework](https://dev.botframework.com/), [Nexmo](https://nexmo.com) and [Facebook Messenger](http://messenger.com). 

```php
BotMan::hears('I want cross-platform bots with PHP!', function($bot) {
    $bot->reply('Look no further!');
});

BotMan::listen();
```

## Getting Started

1. Open the Laravel/Symfony/PHP project your new Bot will live in
2. [Install BotMan with composer](#installation-using-composer)
3. Configure your messaging platform
4. Implement your bot logic
 
## Installation using Composer

Require this package with composer using the following command:

```sh
$ composer require mpociot/botman
```

### Using BotMan within a Laravel app

BotMan comes with a Service Provider to make using this library in your [Laravel](http://laravel.com) application as simple as possible.

Go to your `config/app.php` and add the service provider:

```php
Mpociot\BotMan\BotManServiceProvider::class,
```

Also add the alias / facade:

```php
'BotMan' => Mpociot\BotMan\Facades\BotMan::class
```

Add your Facebook access token / Slack token to your `config/services.php`:

```php
'botman' => [
    'nexmo_key' => 'YOUR-NEXMO-APP-KEY',
    'nexmo_secret' => 'YOUR-NEXMO-APP-SECRET',
    'microsoft_app_id' => 'YOUR-MICROSOFT-APP-ID',
    'microsoft_app_key' => 'YOUR-MICROSOFT-APP-KEY',
    'slack_token' => 'YOUR-SLACK-TOKEN-HERE',
    'telegram_token' => 'YOUR-TELEGRAM-TOKEN-HERE',
    'facebook_token' => 'YOUR-FACEBOOK-TOKEN-HERE'
],
```

That's it.

## Connect with your messaging service

After you've installed BotMan, the first thing you'll need to do is register your bot with a messaging platform, and get a few configuration options set. This will allow your bot to connect, send and receive messages.

You can support all messaging platforms using the exact same Bot-API.

- [Setup and connect Telegram](readme-telegram.md)
- [Setup and connect Facebook Messenger](readme-facebook.md)
- Setup and connect Microsoft Bot framework
- Setup and connect Nexmo
- Setup and connect Slack

## Core Concepts

Bots built with BotMan have a few key capabilities, which can be used to create clever, conversational applications. 
These capabilities map to the way real human people talk to each other.

Bots can [hear things](#receiving-messages), [say things and reply](#sending-messages) to what they hear.

With these two building blocks, almost any type of conversation can be created.

## Basic Usage

This sample bot listens for the word "hello" - either in a direct message (a private message inside Slack between the user and the bot) or in a message the bot user is invited to.

```php
// Usage without Laravel
$config = [
    'nexmo_key' => 'YOUR-NEXMO-APP-KEY',
    'nexmo_secret' => 'YOUR-NEXMO-APP-SECRET',
    'microsoft_app_id' => 'YOUR-MICROSOFT-APP-ID',
    'microsoft_app_key' => 'YOUR-MICROSOFT-APP-KEY',
    'slack_token' => 'YOUR-SLACK-TOKEN-HERE',
    'telegram_token' => 'YOUR-TELEGRAM-TOKEN-HERE',
    'facebook_token' => 'YOUR-FACEBOOK-TOKEN-HERE'
];

$botman = BotManFactory::create($config, $request, new LaravelCache());

// Usage with Laravel
$botman = app('botman');

// Or use the facade, by using BotMan:: instead of $botman-> in the examples

// give the bot something to listen for.
$botman->hears('hello', function (BotMan $bot, $message) {
    $bot->reply('Hello yourself.');
});
```

# Developing with BotMan

Table of Contents

* [Receiving Messages](#receiving-messages)
    * [Middleware](#middleware)
* [Sending Messages](#sending-messages)

## Receiving Messages

### Matching Patterns and Keywords with `hears()`

BotMan provides a `hears()` function, which will listen to specific patterns in public and/or private channels.

| Argument | Description
|--- |---
| pattern | A string with a regular expressions to match
| callback | Callback function that receives a BotMan object, as well as additional matching regular expression parameters
| in | Defines where the Bot should listen for this message. Can be either `BotMan::DIRECT_MESSAGE` or `BotMan::PUBLIC_CHANNEL`

```php
$botman->hears('keyword', function(BotMan $bot, $message) {
    // do something to respond to message
    $bot->reply('You used a keyword!');
});
```

When using the built in regular expression matching, the results of the expression will be passed to the callback function. For example:

```php
$botman->hears('open the {doorType} doors', function(BotMan $bot, $doorType) {
    if ($doorType === 'pod bay') {
        return $bot->reply('I\'m sorry, Dave. I\'m afraid I can\'t do that.');
    }

    return $bot->reply('Okay');
});
```

### Middleware

The usage of custom middleware allows you to enrich the messages your bot received with additional information from third party services such as [wit.ai](http://wit.ai) or [api.ai](http://api.ai).

To let your BotMan instance make use of a middleware, simply add it to the list of middlewares:

```php
$botman->middleware(Wit::create('MY-WIT-ACCESS-TOKEN'));
$botman->hears('emotion', function($bot) {
    $extras = $bot->getMessage()->getExras();
    // Access extra information
    $entities = $extras['entities'];
});
```

The current Wit.ai middleware will send all incoming text messages to wit.ai and adds the `entities` received from wit.ai back to the message.
You can then access the information using `$bot->getMessage()->getExtras()`. This method returns an array containing all wit.ai entities.

In addition to that, it will check against a custom trait entity called `intent` instead of using the built-in matching pattern.

## Sending Messages

Bots have to send messages to deliver information and present an interface for their
functionality.  BotMan bots can send messages in several different ways, depending
on the type and number of messages that will be sent.

Single message replies to incoming commands can be sent using the `$bot->reply()` function.

Multi-message replies, particularly those that present questions for the end user to respond to,
can be sent using the `$bot->startConversation()` function and the related conversation sub-functions. 


### Single Message Replies to Incoming Messages

Once a bot has received a message using `hears()`, a response
can be sent using `$bot->reply()`.

Messages sent using `$bot->reply()` are sent immediately. If multiple messages are sent via
`$bot->reply()` in a single event handler, they will arrive in the  client very quickly
and may be difficult for the user to process. We recommend using `$bot->startConversation()`
if more than one message needs to be sent.

You may pass either a string, or a Question object to the function.

As a second parameter, you may also send any additional fields supported by Slack, Facebook Messenger or Telegram.

#### $bot->reply()

| Argument | Description
|--- |---
| reply | _String_ or _Question_ Outgoing response
| additionalParameters | _Optional_ Array containing additional parameters

Simple reply example:

```php
$botman->hears('keyword', function (BotMan $bot, $message) {
    // do something to respond to message
    // ...

    $bot->reply("Tell me more!");
});
```


Slack-specific fields and attachments:

```php
$botman->hears('keyword', function (BotMan $bot, $message) {
    // do something...

    // then respond with a message object
    $bot->reply("A more complex response",[
        'username' => "ReplyBot",
        'icon_emoji' => ":dash:",
    ]);
})
```

### Multi-message Replies to Incoming Messages

For more complex commands, multiple messages may be necessary to send a response,
particularly if the bot needs to collect additional information from the user.

BotMan provides a `Conversation` object that is used to string together several
messages, including questions for the user, into a cohesive unit. BotMan conversations
provide useful methods that enable developers to craft complex conversational
user interfaces that may span a several minutes of dialog with a user, without having to manage
the complexity of connecting multiple incoming and outgoing messages across
multiple API calls into a single function.

### Start a Conversation

#### $bot->startConversation()
| Argument | Description
|---  |---
| conversation  | A `Conversation` object

`startConversation()` is a function that creates conversation in response to an incoming message. You can control where the bot should start the conversation by calling `startConversation` in the `hears()` method of your bot.

Simple conversation example:

```php
$botman->hears('start conversation', function (BotMan $bot, $message) {
    $bot->startConversation(new PizzaConversation);
});
```

### Creating Conversations

When starting a new conversation using the `startConversation()` method, you need to pass the method the conversation that you want to start gathering information with.
Each conversation object needs to extend from the BotMan `Conversation` object and must implement a simple `run()` method.

This is the very first method that gets executed, when the conversation starts.

Example conversation object:

```php
class PizzaConversation extends Conversation
{
    protected $size;

    public function askSize()
    {
        $this->ask('What pizza size do you want?', function(Answer $answer) {
            // Save size for next question
            $this->size = $answer->getText();

            $this->say('Got it. Your pizza will be '.$answer->getText());
        });
    }

    public function run()
    {
        // This will be called immediately
        $this->askSize();
    }
}
``` 

### Control Conversation Flow

#### $conversation->say()
| Argument | Description
|---  |---
| message   | String or `Question` object

Call $conversation->say() several times in a row to queue messages inside the conversation. Only one message will be sent at a time, in the order they are queued.

#### $conversation->ask()
| Argument | Description
|---  |---
| message   | String or `Question` object
| callback _or_ array of callbacks   | callback function in the form function($answer), or array of arrays in the form ``[ 'pattern' => regular_expression, 'callback' => function($answer) { ... } ]``

When passed a callback function, $conversation->ask will execute the callback function for any response.
This allows the bot to respond to open ended questions, collect the responses, and handle them in whatever
manner it needs to.

When passed an array, the bot will look first for a matching pattern, and execute only the callback whose
pattern is matched. This allows the bot to present multiple choice options, or to proceed
only when a valid response has been received. 
The patterns can have the same placeholders as the `$bot->reply()` method has. All matching parameters will be passed to the callback function.

Callback functions passed to `ask()` receive (at least) two parameters - the first is an `Answer` object containing
the user's response to the question. 
If the conversation continues because of a matching pattern, all matching pattern parameters will be passed to the callback function too.
The last parameter is always a reference to the conversation itself.

##### Using $conversation->ask with a callback:

```php
// ...inside the conversation object...
public function askMood()
{
    $this->ask('How are you?', function (Answer $response) {
        $this->say('Cool - you said ' . $response->getText());
    });
}
```

##### Using $conversation->ask with an array of callbacks:


```php
// ...inside the conversation object...
public function askNextStep()
{
    $this->ask('Shall we proceed? Say YES or NO', [
        [
            'pattern' => 'yes|yep',
            'callback' => function () {
                $this->say('Okay - we\'ll keep going');
            }
        ],
        [
            'pattern' => 'nah|no|nope',
            'callback' => function () {
                $this->say('PANIC!! Stop the engines NOW!');
            }
        ]
    ]);
}

```
#### Using $conversation->ask with a Question object

Instead of passing a string to the `ask()` method, it is also possible to create a `Question` object.
The Question objects make use of the interactive messages from Facebook, Telegram and Slack to present the user buttons to interact with.

When passing question objects to the `ask()` method, the returned `Answer` object has a method called `isInteractiveMessageReply` to detect, if 
the user interacted with the message and clicked on a button.

Creating a simple Question object:

```php
// ...inside the conversation object...
public function askForDatabase()
{
    $question = Question::create('Do you need a database?')
        ->fallback('Unable to create a new database')
        ->callbackId('create_database')
        ->addButtons([
            Button::create('Of course')->value('yes'),
            Button::create('Hell no!')->value('no'),
        ]);

    $this->ask($question, function (Answer $answer) {
        // Detect if button was clicked:
        if ($answer->isInteractiveMessageReply()) {
            $selectedValue = $answer->getValue(); // will be either 'yes' or 'no'
            $selectedText = $answer->getText(); // will be either 'Of course' or 'Hell no!'
        }
    });
}
```


### Long running tasks

BotMan uses the Webhook APIs to get information from the messaging system. When the messaging system of your choice sends the information to your app, you have **3 seconds** to return a HTTP 2xx status.
Otherwise the delivery attempt will be considered as a failure and the messaging system will attempt to deliver the message up to three more times.

This means that you should push long running tasks into an asynchronous queue.

Queue example using Laravel:

```php
// ...inside the conversation object...
public function askDomainName()
{
    $this->ask('What should be the domain name?', function (Answer $answer) {
        // Push long running task onto the queue.
        $this->reply('Okay, creating subdomain ' . $answer->getText());
        dispatch(new CreateSubdomain($this, $answer->getText()));
    });
}
```

## License

BotMan is free software distributed under the terms of the MIT license.
