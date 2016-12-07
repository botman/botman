# BotMan with Telegram

> To connect BotMan with your Telegram Bot, you first need to follow the [official guide](https://core.telegram.org/bots#3-how-do-i-create-a-bot) to create your Telegram Bot and an access token.

Once you have obtained the access token, place it in your BotMan configuration.

### Laravel

For Laravel, the access token needs to be in your `config/services.php` file

```php
    'botman' => [
    	'telegram_token' => 'YOUR-TELEGRAM-TOKEN-HERE',
    ],
```

### Generic

If you don't use Laravel, you can pass the Telegram access token to the `BotManFactory` upon initialization.


```php
$botman = BotManFactory::create([
    'telegram_token' => 'YOUR-TELEGRAM-TOKEN-HERE',
]);
```

## Register your Webhook

To let your Telegram Bot know, how it can communicate with your BotMan bot, you have to register the URL where BotMan is running at,
with Telegram.

You can do this by sending a `POST` request to this URL:

`https://api.telegram.org/bot<YOUR-TELEGRAM-TOKEN-HERE>/setWebhook`

This POST request needs only one parameter called `url` with the URL that points to your BotMan logic / controller.

> If you are using Laravel Valet, you can get an external URL for testing using the `valet share` command.

And that's it - you can now use BotMan with your Telegram bot!