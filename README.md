# PHP BotMan 🤖 Create messaging bots in PHP with ease

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mpociot/botman.svg?style=flat-square)](https://packagist.org/packages/mpociot/botman)
[![Build Status](https://travis-ci.org/mpociot/botman.svg?branch=master)](https://travis-ci.org/mpociot/botman)
[![codecov](https://codecov.io/gh/mpociot/botman/branch/master/graph/badge.svg)](https://codecov.io/gh/mpociot/botman)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mpociot/botman/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mpociot/botman/?branch=master)
[![Packagist](https://img.shields.io/packagist/l/mpociot/botman.svg)]()
[![StyleCI](https://styleci.io/repos/65017574/shield?branch=master)](https://styleci.io/repos/65017574)
[![Slack](https://rauchg-slackin-jtdkltstsj.now.sh/badge.svg)](https://rauchg-slackin-jtdkltstsj.now.sh)

## About BotMan

BotMan is a framework agnostic PHP library that is designed to simplify the task of developing innovative bots for multiple messaging platforms, including [Slack](http://slack.com), [Telegram](http://telegram.me), [Microsoft Bot Framework](https://dev.botframework.com/), [Nexmo](https://nexmo.com), [HipChat](http://hipchat.com), [Facebook Messenger](http://messenger.com) and [WeChat](http://web.wechat.com).

```php
$botman->hears('I want cross-platform bots with PHP!', function (BotMan $bot) {
    $bot->reply('Look no further!');
});
```

## Documentation

You can find the BotMan documentation at [http://botman.io](http://botman.io).

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

If you discover a security vulnerability within BotMan, please send an e-mail to Marcel Pociot at m.pociot@gmail.com. All security vulnerabilities will be promptly addressed.

## License

BotMan is free software distributed under the terms of the MIT license.
