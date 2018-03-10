# Change Log
All notable changes to this project will be documented in this file.

## [Unreleased]

### Added
- Delegate `typesAndWaits` to the driver, instead of having a static `sleep` call in the core.

## [2.1.5]
### Added
- Added the ability to pass arrays as command callables

## [2.1.4]
### Added
- Group commands can now be chained - #599
- Skip and Stop conversation can be applied at groups - #600

### Fixed
- Fixed API.ai session management - #610
- Fixed React PHP version constraints

## [2.1.3]
### Fixed
- Fixed an issue with the driver verification (this time for real).

## [2.1.2]
### Fixed
- Fixed an issue with the driver verification.

## [2.1.1]
### Added
- Added incoming message setText method
### Fixed
- BotMan `say` and `ask` methods now return Response objects.

## [2.1.0]
### Added
- Added ability to cache message user information (#542)
- Added macro functionality to the Conversation method
- Added `getStoredConversationQuestion` method

### Fixed
- Fix incorrect 'conversation_cache_time' config path (#557)

## [2.0.4]
### Fixed
- Fixed an issue where non-HTTP drivers were validated

## [2.0.3]
### Fixed
- Use available drivers instead of configured ones for verification because of Slack events verification

## [2.0.2]
### Added
- Drivers can have a method called `additionalDrivers` to simplify manual driver loading, when not using BotMan studio.

### Fixed
- Fixed matching middleware inside of conversations not receiving the manipulated `$message` object.

## [2.0.0]
### Added
- Added ability to originate inline conversations.
- Moved each driver into their own repository.
- Facebook - Added support to send file and audio attachments.
- Telegram - Added support to send file, audio and location attachments.
- Added Kik driver.
- Added custom Attachment classes.
- Added support to listen for message service events.
- Changed the way middleware works in BotMan.
- Added support for Slack interactive menu messages.
- Added Facebook Referral driver.
- Allow replying to an existing thread for Slack drivers (#327).
- Added `loadDriver` method to BotMan.
- Added ability to use BotMan with a local socket.

### Changed
- Switched from plain text to JSON responses for Slack slash commands, to allow richer message formatting.
- Moved message matching into a separate `Matcher` class.

### Removed
- Removed `FacebookPostbackDriver`, `FacebookOptinDriver` and `FacebookReferralDriver` in favor of the new event API.

## [1.5.6]
### Fixed
Custom drivers now get loaded first.

## [1.5.5]
### Fixed
Fix botframework not using shorthand closing tags (#345)

## [1.5.4]
### Fixed
Fix error when originating MS Bot Framework messages - fixes (#324)

## [1.5.3]
### Fixed
Fixed an issue with the SlackRTM driver in combination with regular file uploads (#323)

## [1.5.2]
### Changed
- Added unicode support
- Added support for Telegram voice messages

## [1.5.1]
### Changed
- Additional parameters for `say`, `reply` and `ask` methods now recursively merge the parameters.

## [1.5.0]

### Added
- Added `askForImages`, `askForVideos`, `askForAudio`, `askForLocation`.
- Added support for receiving images, videos, audio files and locations.
- Added `sendRequest` method to perform low-level driver API requests.
- Allow regular expressions in API.ai middleware
- Added fake driver for testing
- Allow typing indicators for Slack RTM driver

### Changed
- Cache API.ai calls
- Cache Wit.AI calls

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
