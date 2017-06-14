<?php

namespace ElfSundae\Test;

use Mockery as m;
use ElfSundae\HttpClient;
use GuzzleHttp\Client as Guzzle;

class HttpClientTest extends TestCase
{
    public function testGetClient()
    {
        $client = new TestClient;
        $client->setClientForTesting('foo');
        $this->assertSame('foo', $client->getClient());
    }

    public function testGetOptions()
    {
        $client = new TestClient;
        $this->assertEquals(['def' => 'def'], $client->getOptions());
    }

    public function testMergeOptions()
    {
        $client = new TestClient;
        $client->options(['foo' => 'bar']);
        $this->assertEquals(['def' => 'def', 'foo' => 'bar'], $client->getOptions());

        $client->options(['foo' => ['1'], 'test' => null]);
        $this->assertEquals([
            'def' => 'def',
            'foo' => [
                'bar',
                '1',
            ],
            'test' => null,
        ], $client->getOptions());
    }

    public function testRemoveOptions()
    {
        $client = new HttpClient;
        $this->assertSame($client, $client->removeOptions());
        $this->assertEquals([], $client->getOptions());

        $arr = ['foo' => ['x' => 'test', 'y' => 'abc'], 'bar' => 123];
        $this->assertEquals($arr, $client->removeOptions()->options($arr)->getOptions());

        $client->removeOptions('foo.x');
        $this->assertEquals(['foo' => ['y' => 'abc'], 'bar' => 123], $client->getOptions());

        $client->removeOptions('foo', 'bar');
        $this->assertEquals([], $client->getOptions());
    }

    public function testSetOption()
    {
        $client = (new HttpClient)->removeOptions();
        $client->option('foo', 'bar');
        $this->assertEquals(['foo' => 'bar'], $client->getOptions());

        $client->option('a.b', 'c');
        $this->assertEquals(['foo' => 'bar', 'a' => ['b' => 'c']], $client->getOptions());
    }

    public function testDynamicallySetOption()
    {
        $client = (new HttpClient)->removeOptions();
        $client->foo('bar');
        $this->assertEquals(['foo' => 'bar'], $client->getOptions());

        $client->testAbc(null);
        $this->assertEquals(['foo' => 'bar', 'test_abc' => null], $client->getOptions());
    }

    public function testCreateClientWithBaseUriString()
    {
        $client = new HttpClient('/foobar');
        $this->assertEquals('/foobar', (string) $client->getClient()->getConfig('base_uri'));
    }

    public function testCreateClientWithOptions()
    {
        $client = new TestClient(['foo' => 'bar']);
        $this->assertEquals(['def' => 'def', 'foo' => 'bar'], $client->getOptions());
    }

    public function testRequest()
    {
        $guzzle = m::mock(Guzzle::class);
        $guzzle->shouldReceive('request')->once()->with('GET', '/url/path/', ['foo' => 'bar'])->andReturn('response');

        $client = (new TestClient)->removeOptions()->setClientForTesting($guzzle);
        $client->request('/url/path/', 'GET', ['foo' => 'bar']);
        $this->assertSame('response', $client->getResponse());
    }

    public function testWithExceptionsOn()
    {
        $this->expectException(TestException::class);

        $guzzle = m::mock(Guzzle::class);
        $guzzle->shouldReceive('request')->once()->andThrow(new TestException);
        $client = (new TestClient)->setClientForTesting($guzzle);
        $client->withExceptions(true);
        $client->request('/');
    }
}

class TestClient extends HttpClient
{
    protected $options = [
        'def' => 'def',
    ];

    public function setClientForTesting($client)
    {
        $this->client = $client;

        return $this;
    }
}

class TestException extends \Exception
{
}
