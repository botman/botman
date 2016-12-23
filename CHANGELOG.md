# Change Log
All notable changes to this project will be documented in this file.

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