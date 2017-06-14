<?php

use ElfSundae\HttpClient;

require __DIR__.'/../vendor/autoload.php';

$client = new HttpClient('https://httpbin.org');

dump(__LINE__, $client->request('http://icanhazip.com')->getContent());
dump(__LINE__, (new HttpClient)->fetchContent('http://icanhazip.com'));

dump(__LINE__, $client->request('/ip')->getJson());
dump(__LINE__, $client->fetchJson('/ip'));

dump(__LINE__, $client->header('X-FOO', 'bar')->fetchJson('/headers'));

try {
    $client->withExceptions(true)->fetchContent('/status/418');
} catch (\Exception $e) {
    dump(__LINE__, $e->getCode(), $e->getMessage());
}

dump(__LINE__,
    (new HttpClient)
    ->formParams(['user' => 'Elf Sundae'])
    ->fetchJson('https://httpbin.org/post', 'POST')
);

dump(__LINE__, $client->saveTo(__DIR__.'/image.png')->request('/image/png')->getStatusCode());

// Options
$client->option('cookies', new \GuzzleHttp\Cookie\CookieJar())
    ->auth(['username', 'password'])
    ->cert('/path/server.pem')
    ->debug(true)
    ->httpErrors(false)
    ->progress(function () {
    })
    ->verify(false)
    ->version(2)
    ->acceptJson();
dump(__LINE__, $client->getOptions());

function dump($line, ...$data)
{
    usleep(300000);
    echo "====================  $line  ===========================".PHP_EOL;
    var_dump(...$data);
}
