# PHP SlackBot

[![Build Status](https://travis-ci.org/mpociot/slackbot.svg?branch=master)](https://travis-ci.org/mpociot/slackbot)
[![codecov](https://codecov.io/gh/mpociot/slackbot/branch/master/graph/badge.svg)](https://codecov.io/gh/mpociot/slackbot)

## Installation

### Using Laravel
Require this package with composer using the following command:

```sh
$ composer require mpociot/slackbot
```

Go to your `config/app.php` and add the service provider:

```php
Mpociot\SlackBot\SlackBotServiceProvider::class,
```

Also add the alias / facade:

```php
'SlackBot' => Mpociot\SlackBot\Facades\SlackBot::class
```

## Example usage

```php
// Listen to simple commands
SlackBot::hears('Hello', function (SlackBot $bot) {
    $bot->respond('Hi there!');
});

// Include regular expression matches
SlackBot::hears('Call me {name} the {attribute}', function (SlackBot $bot, $name, $attribute) {
    $bot->respond('Hi '.$name.'! You truly are '.$attribute);
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
    $bot->respond("I don't understand a word you just said.");
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
            $this->reply('Got you - your pizza needs to be '.$answer->getText());
            $this->size = $answer->getValue();
            
            $this->askTopping();
        });
    }
    
    public function askTopping()
    {
        $this->ask('What toppings do you want?', function($answer) {
            $this->reply('Okay, I\'ll put some '.$answer->getText().' on your pizza');
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
