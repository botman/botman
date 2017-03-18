# Change Log
All notable changes to this project will be documented in this file.

## [Unreleased]

### Added
- Added `sendRequest` method to perform low-level driver API requests.
- Allow regular expressions in API.ai middleware
- Added fake driver for testing

### Changed
- Cache API.ai calls

## [1.4.1]
### Added
- Added on-the-fly mini-conversations using `$botman->ask($question, Closure $next, $additionalParameters = [])`.
- Added ability to either temporarily skip conversations or completely stop them using the `skipConversation` and `stopConversation` methods on the conversation object.
- Added a `ShouldQueue` interface that your Conversation classes should use if you want to store / serialize them in queues.
- Added `filePath()` method to the Message class. (SlackRTM support only)

### Changed
- FacebookDriver now returns user first + lastname
- Fixed a bug with Windows + cash file names (#200)
- Fixed a bug with fluent middleware syntax (#203)
- Fixed a bug with multiple middlewares (#209)

## [1.4.0]
### Added
- Added methods to set typing indicators `$botman->types()` and `$botman->typesAndWaits(2);`.
- Added api.ai middleware.
- Added additional parameters to `$botman->say` method.
- Added ability to load command-specific middleware: 
```php
$bot->hears('foo', function($bot){})->middleware(new TestMiddleware());
```
- Added ability to listen only on specific drivers or channels.
- Added `repeat()` method to conversation objects to repeat the last asked question.
- [SlackDriver, SlackRTMDriver] added `replyInThread` method for Slacks new [threaded messaging](https://api.slack.com/docs/message-threading#threads_party) feature.
- Added video message to Facebook, BotFramework and Telegram drivers.
- Added Facebook [template support](https://developers.facebook.com/docs/messenger-platform/send-api-reference/generic-template).
- Added `$botman->getUser()` method to retrieve general user information.

### Changed
- Fixed an error that occured when responding to the Facebook driver with thumbs up.
- Fixed SlackRTM driver to respond using the RTM API (Fixes issues #99 and #67).
- Moved listening to Facebook Postback payloads into a separate driver (FacebookPostbackDriver) so it does not interfere with normal user text
- Correctly handle Skype group chats #128
- Telegram - Fixed empty button callback payload #138
- Telegram - Fixed questions not working when the message type is an entity (url, email, etc) #139
- The MiddlewareInterface now uses the DriverInterface instead of the abstract Driver class

### Removed
- Removed ability to only listen to direct messages / public channels as this was a relic of the old `slackbot` package.

## [1.3.0]
### Added
- Added WeChat messaging driver.
- Added BotMan state methods to store user, channel or driver related data.
    - `$botman->userStorage()`
    - `$botman->channelStorage()`
    - `$botman->driverStorage()`
    
### Changed
- Forced opis/closure `$this` scope serialization.

## [1.2.2]
### Added
- Added support for Slack slash commands. Just hear for the complete slash command `$bot->hears('/command foo', ...`.

### Changed
- Fixed an error when trying to originate a message using a specific driver name (Issue #70).

## [1.2.1]
### Added
- Added support for Microsoft Bot Framework Web Chat

## [1.2.0]
### Added
- Added the `SlackRTMDriver` to make use of the Slack Realtime API.
- Added a new `Message` class to compose messages with images.
- Image support is available for these drivers:
    - Facebook
    - Telegram
    - Slack
    - Microsoft Bot Framework

### Changed
- Middleware classes now receive a third parameter `$regexMatched` inside the `isMessageMatching` method. You can use
this method to determine if the regular expression was also matched, in case you do not want to replace the complete
`hears` logic, but only add custom logic to it. **Note:** This will require you to modify your custom middleware classes.

## [1.1.1]
### Changed
- Fixed a bug where middleware `isMessageMatching` of `false` would still match the message.

## [1.1.0]
### Added
- The `hears` method can now handle `ClassName@method` syntax.

### Changed
- Fixed a bug in combination with middleware classes and regular expression matches

## [1.0.1]
### Added
- Added `send` method to BotMan, to allow originating messages

### Changed
- The `hears` regular expression now checks for the start of the string https://github.com/mpociot/botman/issues/52

### Changed

## [1.0.0] - 2016-12-08
- Initial release
