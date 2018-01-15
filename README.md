# HTTP Client

[![Latest Version on Packagist](https://img.shields.io/packagist/v/elfsundae/httpclient.svg?style=flat-square)](https://packagist.org/packages/elfsundae/httpclient)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/ElfSundae/httpclient/master.svg?style=flat-square)](https://travis-ci.org/ElfSundae/httpclient)
[![StyleCI](https://styleci.io/repos/94341681/shield)](https://styleci.io/repos/94341681)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/34b1d388-636b-4093-8ce6-1958fbd537e1.svg?style=flat-square)](https://insight.sensiolabs.com/projects/34b1d388-636b-4093-8ce6-1958fbd537e1)
[![Quality Score](https://img.shields.io/scrutinizer/g/ElfSundae/httpclient.svg?style=flat-square)](https://scrutinizer-ci.com/g/ElfSundae/httpclient)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/ElfSundae/httpclient/master.svg?style=flat-square)](https://scrutinizer-ci.com/g/ElfSundae/httpclient/?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/elfsundae/httpclient.svg?style=flat-square)](https://packagist.org/packages/elfsundae/httpclient)

HttpClient is a smart [Guzzle](https://github.com/guzzle/guzzle) wrapper provides convenient method chaining, global request options, and magic methods to customize [request options][].

## Installation

```sh
$ composer require elfsundae/httpclient
```

## Usage

```php
use ElfSundae\HttpClient;
```

### Fetching Response Content

```php
$html = (new HttpClient)->fetchContent('http://httpbin.org');

$data = (new HttpClient)->fetchJson('https://httpbin.org/ip');
```

### Making Requests

```php
$client = HttpClient::create('https://httpbin.org')
    ->catchExceptions(true)
    ->httpErrors(false)
    ->auth(['user', 'passwd']);

$query = $client->query(['foo' => 'bar'])->getJson('/get');

$form = $client->formParams(['foo' => 'bar'])->postJson('/post');

$json = $client->json(['foo' => 'bar'])->putJson('/put');

$download = $client->saveTo('image.png')->get('/image/png');

$file = fopen('image.png', 'r');
$uploadBody = $client->body($file)->postJson('/post');

$multipart = [
    'foo' => 'bar',
    'file' => $file,
    'image' => [
        'contents' => fopen('image.png', 'r'),
        'filename' => 'filename.png',
    ],
];
$formData = $client->multipart($multipart)->postJson('/post');
```

### Applying Request Options

Using the `option` method:

```php
$client
    ->option('cert', $cert)
    ->option([
        'debug' => true,
        'headers.Content-Type' => 'application/json',
    ]);
```

Or using `camelCase` of any option name as a method on the client:

```php
$client
    ->allowRedirects(false)
    ->timeout(20)
    ->cookies($cookieJar)
    ->headers([
        'X-Foo' => 'foo',
    ]);
```

In addition, you can use `header`, `accept`, `acceptJson`, `userAgent` or `contentType` to set request headers:

```php
$client
    ->header('X-Foo', 'foo');
    ->header('X-Bar', 'bar');
    ->acceptJson()
    ->contentType('text/plain')
    ->userAgent('HttpClient/2.0')
```

### Global Default Request Options

The static `setDefaultOptions` method can be used to configure default options for every new client instance:

```php
HttpClient::setDefaultOptions([
    'catch_exceptions' => true,
    'http_errors' => false,
    'connect_timeout' => 5,
    'timeout' => 20,
]);
```

### Catching Guzzle Exceptions

The `catchExceptions` method determines whether to catch Guzzle exceptions or not.

```php
$response = $client->catchExceptions(true)->get('/api/path');

try {
    $response = $client->catchExceptions(false)->get('/api/path');
} catch (Exception $e) {
    // ...
}
```

## Testing

```sh
$ composer test
```

## License

This package is open-sourced software licensed under the [MIT License](LICENSE.md).

[request options]: http://docs.guzzlephp.org/en/stable/request-options.html
