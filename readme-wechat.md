# BotMan with WeChat

> Please note: WeChat currently does not support interactive messages / question buttons.

Login to your [developer sandbox account](http://admin.wechat.com/debug/cgi-bin/sandbox?t=sandbox/login) and take note of your appId and appSecret.

There is a section called "API Config" where you need to provide a public accessible endpoint for your bot.
Place the URL that points to your BotMan logic / controller in the URL field.

This URL needs to validate itself against WeChat. Choose a unique
verify token, which you can check against in your verify controller and place it in the Token field.

BotMan comes with a method to simplify the verification process. Just place this line after the initialization:

```php
$botman->verifyServices('', 'MY_SECRET_WECHAT_VERIFICATION_TOKEN');
```

> If you are using Laravel Valet, you can get an external URL for testing using the `valet share` command.

### Laravel

For Laravel, the app id and app key need to be in your `config/services.php` file

```php
    'botman' => [
        'wechat_app_id' => 'YOUR-WECHAT-APP-ID',
        'wechat_app_key' => 'YOUR-WECHAT-APP-KEY',
    ],
```

### Generic

If you don't use Laravel, you can pass the WeChat App ID and App Key to the `BotManFactory` upon initialization.


```php
$botman = BotManFactory::create([
    'wechat_app_id' => 'YOUR-WECHAT-APP-ID',
    'wechat_app_key' => 'YOUR-WECHAT-APP-KEY',
]);
```

And that's it - you can now use BotMan with WeChat to create interactive bots!