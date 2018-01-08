<?php

use ElfSundae\HttpClient;
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function test_http_client()
    {
        $this->assertInstanceOf(HttpClient::class, http_client());

        $client = http_client('http://example.com');
        $this->assertEquals('http://example.com', $client->getClient()->getConfig('base_uri'));

        $client = http_client(['foo' => 'bar']);
        $this->assertArraySubset(['foo' => 'bar'], $client->getClient()->getConfig());
    }
}
