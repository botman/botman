# BotMan with HipChat

> Please note: HipChat currently does not support interactive messages / question buttons.

To connect BotMan with your HipChat team, you need to create an integration in the room(s) you want your bot to be in.
After you have created the integration, take note of the URL HipChat presents you at "Send messages to this room by posting to this URL". 

This URL will be used to send the BotMan replies to your rooms.
 
 > Note: If you want your bot to live in multiple channels, you need to add the ingration multiple times. This is a HipChat limitation.
 
Next, you need to add webhooks to each channel that you have added an integration to.

The easiest way to do this is:

1. As a HipChat Administrator, go to `https://YOUR-HIPCHAT-TEAM.hipchat.com/account/api` and create an API token that has the "Administer Room" scope.
2. With this token, perform a `POST` request against the API to create a webhook:

```bash
curl -X POST -H "Authorization: Bearer YOUR-API-TOKEN" \
-H "Content-Type: application/json" \
-d '{
	"url": "https://MY-BOTMAN-CONTROLLER-URL/",
	"event": "room_message"
}' \
"https://botmancave.hipchat.com/v2/room/YOUR-ROOM-ID/webhook"
```
> If you are using Laravel Valet, you can get an external URL for testing using the `valet share` command.

> Note: If you want your bot to live in multiple channels, you need to add the webhook to each channel. This is a HipChat limitation.

Once you've set up the integration(s) and webhook(s), add them to your BotMan configuration.

### Laravel

For Laravel, the access token needs to be in your `config/services.php` file

```php
    'botman' => [
        'hipchat_urls' => [
            'YOUR-INTEGRATION-URL-1',
            'YOUR-INTEGRATION-URL-2',
        ],
    ],
```

### Generic

If you don't use Laravel, you can pass the HipChat integration URLs to the `BotManFactory` upon initialization.


```php
$botman = BotManFactory::create([
    'hipchat_urls' => [
        'YOUR-INTEGRATION-URL-1',
        'YOUR-INTEGRATION-URL-2',
    ],
]);
```

And that's it - you can now use BotMan with your HipChat team!