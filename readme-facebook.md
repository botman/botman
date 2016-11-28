# BotMan with Facebook Messenger

### Preparation

**Note:** Facebook Messenger requires a valid URL in order to set up webhooks. If you are using Laravel Valet, you can get an external URL for testing using the `valet share` command.

This URL needs to validate itself against Facebook. When you create the webhook on the Facebook developer website, you have to choose a unique
verify token, which you can check against in your verify controller.

This is what the Facebook verification would look like in a Laravel application. Place it in a controller method. Facebook will try to send it a `GET` request.
```php
// Facebook verification
if ($request->hub_mode === 'subscribe' && $request->hub_verify_token === 'MY_SECRET_TOKEN') {
    return $request->hub_challenge;
}
```

To connect BotMan with your Facebook Messenger Bot, you first need to follow the [official quick start guide](https://developers.facebook.com/docs/messenger-platform/guides/quick-start) to create your Messenger Bot and retrieve an access token.

Once you have obtained the page access token, place it in your BotMan configuration.

### Laravel

For Laravel, the page access token needs to be in your `config/services.php` file

```php
    'botman' => [
    	'facebook_token' => 'YOUR-FACEBOOK-PAGE-TOKEN-HERE',
    ],
```

### Generic

If you don't use Laravel, you can pass the page access token to the `DriverManager` class upon initialization.


```php
$botman = new BotMan(
    new Serializer(),
    $request,
    new CustomCache(),
    new DriverManager([
    	'facebook_token' => 'YOUR-FACEBOOK-PAGE-TOKEN-HERE',
    ]), new Curl())
);
```

And that's it - you can now use BotMan with your Telegram bot!