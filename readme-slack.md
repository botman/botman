# BotMan with Slack

**Note:**

You have two possibilities to set up Slack to connect with BotMan

#### Use an [outgoing webhook](https://api.slack.com/outgoing-webhooks)
 
**Pros:** Very easy to set up
 
**Cons:** 
  * You can not send and interact with [interactive message buttons](https://api.slack.com/docs/message-buttons)
  * Your bot will be limited to specific channels (those you set up when adding the outgoing webhook to your Slack team)
    
#### Use a bot

**Pros:** All BotMan features available

**Cons:** Pretty cumbersome to set up *Note:* Let the folks from [SlackHQ](https://twitter.com/slackhq) now this. If we make enough noise, they'll hopefully simplify the bot token creation process!


## Usage with an outgoing webhook

Add a new "Outgoing Webhook" integration to your Slack team - this URL needs to point to the controller where your BotMan bot is living in.

To let BotMan listen to all incoming messages, do **not** specify a trigger word, but define a channel instead.

> If you are using Laravel Valet, you can get an external URL for testing using the `valet share` command.

With the Webhook implementation, there is no need to add a `slack_token` configuration. 
_Yes - that is all you need to do, to use BotMan_