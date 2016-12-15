# Change Log
All notable changes to this project will be documented in this file.

## [Unreleased]
### Added
- Added a new `Message` class to compose messages with images.
- Image support is available for these drivers:
    - Facebook
    - Telegram
    - Slack
    - Microsoft Bot Framework

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