# Sliding Time Window Rate Limiter for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/beyondcode/laravel-sliding-window-limiter.svg?style=flat-square)](https://packagist.org/packages/beyondcode/laravel-sliding-window-limiter)
[![Build Status](https://img.shields.io/travis/beyondcode/laravel-sliding-window-limiter/master.svg?style=flat-square)](https://travis-ci.org/beyondcode/laravel-sliding-window-limiter)
[![Quality Score](https://img.shields.io/scrutinizer/g/beyondcode/laravel-sliding-window-limiter.svg?style=flat-square)](https://scrutinizer-ci.com/g/beyondcode/laravel-sliding-window-limiter)
[![Total Downloads](https://img.shields.io/packagist/dt/beyondcode/laravel-sliding-window-limiter.svg?style=flat-square)](https://packagist.org/packages/beyondcode/laravel-sliding-window-limiter)

This package allows you to easily create and validate rate limiting using the sliding window algorithm (in a memory efficient way).

This package makes use of  Redis' atomic requests.

## Installation

You can install the package via composer:

```bash
composer require beyondcode/laravel-sliding-window-limiter
```

## Usage

You can create a new limiter instance by calling the `create` method and pass it a CarbonInterval that represents the window of time, that will be used for the limiter.
The second argument is the maximum number of requests/attempts that your limiter will accept in that given time frame. 

``` php
$limiter = SlidingWindowLimiter::create(CarbonInterval::hour(1), 100);
```

### Specifying custom intervals

By default, all attempts to the limiter will be grouped down to the nearest minute. If you need a more fine-grained control over the interval, you can specify it as a third parameter:

``` php
$limiter = SlidingWindowLimiter::create(CarbonInterval::minute(1), 100, CarbonInterval::second());
```

### Running attempts against your limiter

Once your limiter is created, you can perform attempts against it to see if the call is within the usage limits that you specified.
Since your limiter can be used for multiple resources, you need to pass the resource that you want to attempt the call for in the `attempt` method call.

```php
$limiter->attempt('user_1');
```

### Getting the usage count

When you want to read the number of attempts that were made for a given resource, you may call the `getUsage` method.

**Note:** This method will not return the number of attempts, but only the number of successful attempts.

```php
$count = $limiter->getUsage('user_1');
```

### Reset the limiter

If you want to reset the attempts for a specific resource, you may call the `reset` method on the limiter instance. This will reset the TTL to the given time frame and re-allow new attempts.

```php
$limiter->reset('user_1');
```

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email marcel@beyondco.de instead of using the issue tracker.

## Credits

- [Marcel Pociot](https://github.com/mpociot)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
