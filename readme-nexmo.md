# BotMan with Nexmo

> To connect BotMan with Nexmo, you first need to create a Nexmo account [here](https://dashboard.nexmo.com/sign-up) and [buy a phone number](https://dashboard.nexmo.com/buy-numbers), which is capable of sending SMS.

Go to the Nexmo dashboard at [https://dashboard.nexmo.com/settings](https://dashboard.nexmo.com/settings) and copy your API key and API secret.

### Laravel

For Laravel, the API key and API secret need to be in your `config/services.php` file

```php
    'botman' => [
    	'nexmo_key' => 'YOUR-NEXMO-APP-KEY',
        'nexmo_secret' => 'YOUR-NEXMO-APP-SECRET',
    ],
```

### Generic

If you don't use Laravel, you can pass the Nexmo API key and API secret to the `BotManFactory` upon initialization.


```php
$botman = BotManFactory::create([
    'nexmo_key' => 'YOUR-NEXMO-APP-KEY',
    'nexmo_secret' => 'YOUR-NEXMO-APP-SECRET',
]);
```

## Register your Webhook

To let Nexmo send your bot notifications when incoming SMS arrive at your numbers, you have to register the URL where BotMan is running at,
with Nexmo.

You can do this by visiting your Nexmo dashboard at [https://dashboard.nexmo.com/settings](https://dashboard.nexmo.com/settings).

There you will find an input field called `Callback URL for Inbound Message` - place the URL that points to your BotMan logic / controller in this field.

> If you are using Laravel Valet, you can get an external URL for testing using the `valet share` command.

And that's it - you can now use BotMan with Nexmo to create an interactive SMS bot!