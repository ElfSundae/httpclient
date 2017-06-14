# httpclient

[![Latest Version on Packagist](https://img.shields.io/packagist/v/elfsundae/httpclient.svg?style=flat-square)](https://packagist.org/packages/elfsundae/httpclient)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/ElfSundae/httpclient/master.svg?style=flat-square)](https://travis-ci.org/ElfSundae/httpclient)
[![StyleCI](https://styleci.io/repos/94341681/shield)](https://styleci.io/repos/94341681)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/34b1d388-636b-4093-8ce6-1958fbd537e1.svg?style=flat-square)](https://insight.sensiolabs.com/projects/34b1d388-636b-4093-8ce6-1958fbd537e1)
[![Quality Score](https://img.shields.io/scrutinizer/g/ElfSundae/httpclient.svg?style=flat-square)](https://scrutinizer-ci.com/g/ElfSundae/httpclient)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/ElfSundae/httpclient/master.svg?style=flat-square)](https://scrutinizer-ci.com/g/ElfSundae/httpclient/?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/elfsundae/httpclient.svg?style=flat-square)](https://packagist.org/packages/elfsundae/httpclient)

A wrapper of [Guzzle](https://github.com/guzzle/guzzle) HTTP client.

## Installation

You can install this package via the [Composer](https://getcomposer.org) manager:

```sh
$ composer require elfsundae/httpclient
```

## Usage

```php
use ElfSundae\HttpClient;

// Create client with base uri
$client = new HttpClient('https://httpbin.org');

// Create client with any options
$client = new HttpClient([
    'base_uri' => 'https://httpbin.org',
    'timeout' => '10',
]);

// Make request
$client->request('/', 'GET', $options)

// Make request expecting JSON content response
$client->requestJson('/', 'GET', $options)

// Make request and get response content
$client->requestJson('/path')->getJson();
// Or
$client->fetchJson('/path');
$client->fetchContent('/path');

// Set request options.
// All Guzzle option keys can be used as methods for a HttpClient instance.
$client->option('cookies', new \GuzzleHttp\Cookie\CookieJar())
    ->auth(['username', 'password'])
    ->cert('/path/server.pem')
    ->debug(true)
    ->httpErrors(false)
    ->progress(function () {})
    ->verify(false)
    ->version(2)
    ->acceptJson()
    ->getOptions();

// Access response
$client->getResponse();
$client->getStatusCode();
$client->getHeader('Server');
$client->getHeaders();
$client->getBody();     // GuzzleHttp\Psr7\Stream
$client->getContent();  // string
$client->getJson($assoc = true);    // array

// Parameters
$client->query(['foo' => 'bar']);
$client->formParams(['foo' => 'bar']);
$client->multipart([
    [
        'name' => 'avatar',
        'contents' => fopen('/path/to/file', 'r'),
        'filename' => 'avatar.png'
    ],
]);
$client->json(['foo' => 'bar']);
$client->body($data);
```

## Testing

```sh
$ composer test
```

## License

This package is open-sourced software licensed under the [MIT License](LICENSE.md).
