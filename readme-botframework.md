# BotMan with Microsoft Bot Framework / Skype

Register a developer account with the [Bot Framework Developer Portal](https://dev.botframework.com/) and follow [this guide](https://docs.botframework.com/en-us/csharp/builder/sdkreference/gettingstarted.html#registering) to register your first bot with the Bot Framework.

When you set up your bot, you will be asked to provide a public accessible endpoint for your bot.
Place the URL that points to your BotMan logic / controller in this field.

> If you are using Laravel Valet, you can get an external URL for testing using the `valet share` command.

Take note of the App ID and App Key assigned to your new bot.

> By default your bot will be configured to support the Skype channel but you'll need to add it as a contact on Skype in order to test it. You can do that from the developer portal by clicking the "Add to Skype" button in your bots profile page.

### Laravel

For Laravel, the app id and app key need to be in your `config/services.php` file

```php
    'botman' => [
        'microsoft_app_id' => 'YOUR-MICROSOFT-APP-ID',
        'microsoft_app_key' => 'YOUR-MICROSOFT-APP-KEY',
    ],
```

### Generic

If you don't use Laravel, you can pass the Microsoft Bot Framework App ID and App Key to the `BotManFactory` upon initialization.


```php
$botman = BotManFactory::create([
    'microsoft_app_id' => 'YOUR-MICROSOFT-APP-ID',
    'microsoft_app_key' => 'YOUR-MICROSOFT-APP-KEY',
]);
```

And that's it - you can now use BotMan with the Microsoft Bot Framework to create an interactive bots!