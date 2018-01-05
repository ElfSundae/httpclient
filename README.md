# HTTP Client

[![Latest Version on Packagist](https://img.shields.io/packagist/v/elfsundae/httpclient.svg?style=flat-square)](https://packagist.org/packages/elfsundae/httpclient)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/ElfSundae/httpclient/master.svg?style=flat-square)](https://travis-ci.org/ElfSundae/httpclient)
[![StyleCI](https://styleci.io/repos/94341681/shield)](https://styleci.io/repos/94341681)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/34b1d388-636b-4093-8ce6-1958fbd537e1.svg?style=flat-square)](https://insight.sensiolabs.com/projects/34b1d388-636b-4093-8ce6-1958fbd537e1)
[![Quality Score](https://img.shields.io/scrutinizer/g/ElfSundae/httpclient.svg?style=flat-square)](https://scrutinizer-ci.com/g/ElfSundae/httpclient)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/ElfSundae/httpclient/master.svg?style=flat-square)](https://scrutinizer-ci.com/g/ElfSundae/httpclient/?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/elfsundae/httpclient.svg?style=flat-square)](https://packagist.org/packages/elfsundae/httpclient)

HttpClient is a smart [Guzzle](https://github.com/guzzle/guzzle) wrapper provides convenient method chaining, global request options, and magic methods to customize any request options.

## Installation

```sh
$ composer require elfsundae/httpclient
```

## Usage

### Create HTTP Client Instance

You can create a HTTP client instance with a base URI or an array of [request options][]:

```php
use ElfSundae\HttpClient;

$httpbin = new HttpClient('http://httpbin.org');

$github = new HttpClient([
    'base_uri' => 'https://api.github.com',
    'timeout' => 20,
    'headers' => [
        'User-Agent' => 'HttpClient/2.0',
    ],
]);
```

### Configure Request Options

You can use the `camelCase` key of any [request option][request options] as a method of the client:

```php
$client = HttpClient::create('http://example.com')
    ->connectTimeout(5)
    ->timeout(20)
    ->httpErrors(false)
    ->version(2)
    ->auth(['username', 'password'])
    ->cookies(true)
    ->headers([
        'X-Foo' => 'Bar',
    ]);
```

You can also use the `option` method on the client to set request options using "dot" notation:

```php
$client
    ->option('headers.Accept', 'application/json')
    ->option([
        'cookies' => true,
        'headers.Content-Type' => 'application/json',
    ]);
```

In addition, you may want to use `header`, `accept`, `acceptJson`, `userAgent` and `contentType` methods to set request headers:

```php
$client
    ->contentType('text/plain')
    ->acceptJson()
    ->userAgent('HttpClient/2.0')
    ->header('X-Foo', 'bar');
```

### Global Request Options

### Send Requests

### Response

## Testing

```sh
$ composer test
```

## License

This package is open-sourced software licensed under the [MIT License](LICENSE.md).

[request options]: http://docs.guzzlephp.org/en/stable/request-options.html
