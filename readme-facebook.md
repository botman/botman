# BotMan with Facebook Messenger

### Preparation

**Note:** Facebook Messenger requires a valid URL in order to set up webhooks. If you are using Laravel Valet, you can get an external URL for testing using the `valet share` command.

This URL needs to validate itself against Facebook. When you create the webhook on the Facebook developer website, you have to choose a unique
verify token, which you can check against in your verify controller.

BotMan comes with a method to simplify the verification process. Just place this line after the initialization:

```php
$botman->verifyServices('MY_SECRET_VERIFICATION_TOKEN');
```

To connect BotMan with your Facebook Messenger Bot, you first need to follow the [official quick start guide](https://developers.facebook.com/docs/messenger-platform/guides/quick-start) to create your Messenger Bot and retrieve an access token.

Once you have obtained the page access token, place it in your BotMan configuration.

If you want BotMan to automatically verify each incoming Facebook webhook, you can optionally place your app secret in the BotMan configuration array.

### Laravel

For Laravel, the page access token needs to be in your `config/services.php` file

```php
    'botman' => [
    	'facebook_token' => 'YOUR-FACEBOOK-PAGE-TOKEN-HERE',
    	'facebook_app_secret' => 'YOUR-FACEBOOK-APP-SECRET-HERE', // Optional - this is used to verify incoming API calls
    ],
```

### Generic

If you don't use Laravel, you can pass the page access token to the `BotManFactory` upon initialization.


```php
$botman = BotManFactory::create([
    'facebook_token' => 'YOUR-FACEBOOK-PAGE-TOKEN-HERE',
    'facebook_app_secret' => 'YOUR-FACEBOOK-APP-SECRET-HERE',
]);
```

And that's it - you can now use BotMan with your Facebook bot!
