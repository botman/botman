<p align="center"><img height="188" width="198" src="https://botman.io/img/botman.png"></p>
<h1 align="center">BotMan</h1>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/botman/botman.svg?style=flat-square)](https://packagist.org/packages/botman/botman)
[![Build Status](https://travis-ci.org/botman/botman.svg?branch=2.0)](https://travis-ci.org/botman/botman)
[![codecov](https://codecov.io/gh/botman/botman/branch/master/graph/badge.svg)](https://codecov.io/gh/botman/botman)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/botman/botman/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/botman/botman/?branch=master)
[![Packagist](https://img.shields.io/packagist/l/botman/botman.svg)]()
[![StyleCI](https://styleci.io/repos/65017574/shield?branch=master)](https://styleci.io/repos/65017574)
[![Slack](https://rauchg-slackin-jtdkltstsj.now.sh/badge.svg)](https://rauchg-slackin-jtdkltstsj.now.sh)
[![Monthly Downloads](https://img.shields.io/packagist/dm/botman/botman.svg?style=flat-square)](https://packagist.org/packages/botman/botman)

## About BotMan

BotMan is a framework agnostic PHP library that is designed to simplify the task of developing innovative bots for multiple messaging platforms, including [Slack](https://slack.com), [Telegram](https://telegram.org), [Microsoft Bot Framework](https://dev.botframework.com), [Nexmo](https://www.nexmo.com), [HipChat](https://www.hipchat.com), [Facebook Messenger](https://www.messenger.com) and [WeChat](https://web.wechat.com).

```php
$botman->hears('I want cross-platform bots with PHP!', function (BotMan $bot) {
    $bot->reply('Look no further!');
});
```

## Documentation

You can find the BotMan documentation at [https://botman.io](https://botman.io).

## Support the development
**Do you like this project? Support it by donating**

- PayPal: [Donate](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=m%2epociot%40googlemail%2ecom&lc=CY&item_name=BotMan&no_note=0&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest)
- Open Collective: [Become A Backer](https://opencollective.com/botman)
- Patreon: [Become A Backer](https://www.patreon.com/botman)

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

If you discover a security vulnerability within BotMan, please send an e-mail to Marcel Pociot at m.pociot@gmail.com. All security vulnerabilities will be promptly addressed.

## License

BotMan is free software distributed under the terms of the MIT license.
 
