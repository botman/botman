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
$slackBot->hears('Call me {name} the {attribute}', function (SlackBot $bot, $name, $attribute) {
    $bot->respond('Hi '.$name.'! You truly are '.$attribute);
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
    
        $question = Question::create('How are you doing?')
                    ->addButton(
                        Button::create('Extra Large')->value('xl')
                    )
                    ->addButton(
                        Button::create('Mega Large')->value('xxl')
                    );
                    
        $this->ask($question, function($answer) {
            $this->reply('Got you - your pizza needs to be '.$answer);
            $this->size = $answer;
            
            $this->askTopping();
        });
    }
    
    public function askTopping()
    {
        $this->ask('What toppings do you want?', function($answer) {
            $this->reply('Okay, I\'ll put some '.$answer.' on your pizza');
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
