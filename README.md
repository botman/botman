# PHP SlackBot

## Example usage

```php
/** @var SlackBot $slackBot */
$slackBot = app(SlackBot::class);
$slackBot->initialize(env('SLACK_TOKEN'));

// Listen to simple commands
$slackBot->hears('Hello', function (SlackBot $bot) {
    $bot->respond('Hi there!');
});

// Include regular expression matches
$slackBot->hears('Call me (.*)', function (SlackBot $bot, $matches) {
    $bot->respond('Hi '.$matches[1].'!');
});

// Use conversations
$slackBot->hears('order pizza', function (SlackBot $bot, $matches) {
    $bot->startConversation(new OrderPizzaConversation());
});
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
        $this->ask('Which size do you want?', function($answer) {
            $this->reply('Got you - your pizza needs to be '.$answer);
            $this->size = $answer;
            
            $this->askTopping();
        });
    }
    
    public function askTopping()
    {
        $this->ask('What toppings do you want?', function($answer) {
            $this->reply('Okay, I\'ll putt some '.$answer.' on your pizza');
            $this->toppings = $answer;
            
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