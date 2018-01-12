<?php

use Mockery as m;
use GuzzleHttp\Psr7\Uri;
use ElfSundae\HttpClient;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Handler\MockHandler;

class HttpClientTest extends TestCase
{
    protected function tearDown()
    {
        HttpClient::setDefaultOptions([]);
        m::close();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(HttpClient::class, new HttpClient);
        $this->assertInstanceOf(HttpClient::class, HttpClient::create());
        $this->assertInstanceOf(Guzzle::class, HttpClient::create()->getClient());
    }

    public function testSetDefaultOptions()
    {
        HttpClient::setDefaultOptions(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], HttpClient::defaultOptions());

        $config = (new HttpClient)->getClient()->getConfig();
        $this->assertArraySubset(['foo' => 'bar'], $config);

        $this->assertEquals($config, HttpClient::create()->getClient()->getConfig());
    }

    public function testCreateWithOptions()
    {
        $client = new HttpClient(['foo' => 'bar']);
        $this->assertSame('bar', $client->getClient()->getConfig('foo'));
    }

    public function testCreateWithBaseUri()
    {
        $uri = new Uri('http://example.com');
        $client = new HttpClient($uri);
        $this->assertSame($uri, $client->getClient()->getConfig('base_uri'));

        $client = new HttpClient('http://example.com');
        $this->assertSame('http://example.com', (string) $client->getClient()->getConfig('base_uri'));
    }

    public function testExceptionWhenInvalidAruguments()
    {
        $this->expectException(\InvalidArgumentException::class);
        $client = new HttpClient(new \stdClass);
    }

    public function testGetOption()
    {
        $client = new HttpClient(['foo' => 'bar']);
        $this->assertArraySubset($client->getClient()->getConfig(), $client->getOption());
        $this->assertArraySubset(['foo' => 'bar'], $client->getOption());
        $this->assertSame('bar', $client->getOption('foo'));
        $this->assertNull($client->getOption('bar'));
        $this->assertSame('Bar', $client->getOption('bar', 'Bar'));
    }

    public function testSetOption()
    {
        $client = new HttpClient;
        $client->option('foo', 'bar');
        $this->assertSame('bar', $client->getOption('foo'));
        $client->option(['a' => 'A', 'b' => 'B']);
        $this->assertArraySubset(['a' => 'A', 'b' => 'B'], $client->getOption());
    }

    public function testMergeOptions()
    {
        $client = new HttpClient([
            'a' => 'A',
            'b' => [
                'b1' => 'B1',
                'b2' => 'B2',
            ],
        ]);
        $client->mergeOptions([
            'a' => 'AA',
            'b' => [
                'b2' => 'BB2',
                'b3' => 'BB3',
            ],
            'c' => 'CC',
        ]);
        $this->assertArraySubset([
            'a' => 'AA',
            'b' => [
                'b1' => 'B1',
                'b2' => 'BB2',
                'b3' => 'BB3',
            ],
            'c' => 'CC',
        ], $client->getOption());
    }

    public function testRemoveOption()
    {
        $client = new HttpClient([
            'a' => 'A',
            'b' => 'B',
            'c' => 'C',
            'd' => 'D',
        ]);
        $client->removeOption('a');
        $this->assertNull($client->getOption('a'));
        $client->removeOption('b', 'c');
        $this->assertNull($client->getOption('b'));
        $this->assertNull($client->getOption('c'));
        $client->removeOption('d');
        $this->assertNull($client->getOption('d'));
    }

    public function testSetHeader()
    {
        $client = new HttpClient;
        $client->header('foo', 'bar');
        $this->assertSame('bar', $client->getOption('headers.foo'));
    }

    public function testRemoveHeader()
    {
        $client = new HttpClient(['headers' => [
            'A' => 'a',
            'B' => 'b',
            'C' => 'c',
            'D' => 'd',
        ]]);

        $client->removeHeader('A', 'B');
        $this->assertArrayNotHasKey('A', $client->getOption('headers'));
        $this->assertArrayNotHasKey('B', $client->getOption('headers'));

        $client->removeHeader(['C']);
        $this->assertArrayNotHasKey('C', $client->getOption('headers'));

        $this->assertArraySubset(['D' => 'd'], $client->getOption('headers'));
    }

    public function testSetAccept()
    {
        $client = new HttpClient;
        $client->accept('foo/bar');
        $this->assertSame('foo/bar', $client->getOption('headers.Accept'));
    }

    public function testSetAcceptJson()
    {
        $client = new HttpClient;
        $client->acceptJson();
        $this->assertSame('application/json', $client->getOption('headers.Accept'));
    }

    public function testSetUserAgent()
    {
        $client = new HttpClient;
        $client->userAgent('foo/1.0');
        $this->assertSame('foo/1.0', $client->getOption('headers.User-Agent'));
    }

    public function testSetContentType()
    {
        $client = new HttpClient;
        $client->contentType('foo/bar');
        $this->assertSame('foo/bar', $client->getOption('headers.Content-Type'));
    }

    public function testSetSaveTo()
    {
        $client = new HttpClient;
        $client->saveTo('path');
        $this->assertSame('path', $client->getOption('sink'));
    }

    public function testSetMultipart()
    {
        $client = new HttpClient;
        $client->multipart([
            'a' => 'A',
            'b' => [
                'filename' => 'foo.txt',
            ],
            [
                'name' => 'c',
            ],
            'd',
        ]);
        $this->assertEquals([
            [
                'name' => 'a',
                'contents' => 'A',
            ],
            [
                'name' => 'b',
                'filename' => 'foo.txt',
            ],
            [
                'name' => 'c',
            ],
            [
                'contents' => 'd',
            ],
        ], $client->getOption('multipart'));
    }

    public function testSetCatchExceptions()
    {
        $client = new HttpClient;
        $client->catchExceptions(false);
        $this->assertSame(false, $client->areExceptionsCaught());
        $client->catchExceptions(true);
        $this->assertSame(true, $client->areExceptionsCaught());
        $client->catchExceptions(null);
        $this->assertSame(false, $client->areExceptionsCaught());
    }

    public function testRequest()
    {
        $client = new TestClient(['a' => 'A']);

        $guzzle = m::mock(Guzzle::class);
        $guzzle->shouldReceive('request')
            ->with('GET', 'path', m::subset(['a' => 'A', 'b' => 'B']))
            ->once()
            ->andReturn($response = new Response);
        $client->setGuzzle($guzzle);
        $this->assertSame($response, $client->request('path', 'GET', ['b' => 'B']));

        $guzzle = m::mock(Guzzle::class);
        $guzzle->shouldReceive('request')
            ->with('POST', 'path1', m::subset(['a' => 'A']))
            ->twice()
            ->andThrow(new TestException);
        $client->setGuzzle($guzzle);
        $client->catchExceptions(true);
        $this->assertNull($client->request('path1', 'POST'));

        $this->expectException(TestException::class);
        $client->catchExceptions(false);
        $client->request('path1', 'POST');
    }

    public function testResetBodyOptions()
    {
        $guzzle = m::mock(Guzzle::class);
        $guzzle->shouldReceive('request')
            ->with('GET', 'path', m::subset(['body' => 'request body']))
            ->once()
            ->andReturn('response');
        $client = new TestClient;
        $client->setGuzzle($guzzle);
        $client->option('body', 'request body');
        $client->request('path', 'GET');
        $this->assertNull($client->getOption('body'));
    }

    public function testRequestJson()
    {
        $client = new TestClient;
        $guzzle = m::mock(Guzzle::class);
        $guzzle->shouldReceive('request')
            ->with('POST', 'path', m::subset(['headers' => ['Accept' => 'application/json']]))
            ->once()
            ->andReturn('response');
        $client->setGuzzle($guzzle);
        $client->requestJson('path', 'POST');
        $this->assertNull($client->getOption('headers.Accept'));
    }

    public function testRequestAsync()
    {
        $client = new TestClient(['a' => 'A']);
        $guzzle = m::mock(Guzzle::class);
        $guzzle->shouldReceive('requestAsync')
            ->with('POST', 'path', m::subset(['a' => 'A', 'b' => 'B']))
            ->once()
            ->andReturn('promise');
        $client->setGuzzle($guzzle);
        $this->assertSame('promise', $client->requestAsync('path', 'POST', ['b' => 'B']));
    }

    public function testFetchContent()
    {
        $handler = MockHandler::createWithMiddleware([
            new Response(200, [], 'foobar'),
            new TestException,
            new TestException,
        ]);
        $client = new HttpClient(compact('handler'));

        $this->assertSame('foobar', $client->fetchContent());

        $client->catchExceptions(true);
        $this->assertNull($client->fetchContent());

        $client->catchExceptions(false);
        $this->expectException(TestException::class);
        $client->fetchContent();
    }

    public function testFetchJson()
    {
        $response = new Response(200, [], json_encode(['foo' => 'bar']));
        $handler = MockHandler::createWithMiddleware([$response]);
        $client = new HttpClient(compact('handler'));
        $this->assertSame(['foo' => 'bar'], $client->fetchJson());
    }

    public function testMagicRequestMethods()
    {
        $client = new TestClient;

        foreach (['get', 'head', 'put', 'post', 'patch', 'delete', 'options'] as $method) {
            $guzzle = m::mock(Guzzle::class);
            $guzzle->shouldReceive('request')
                ->with($method, 'path', m::subset(['foo' => $method]))
                ->once()
                ->andReturn($response = new Response);
            $client->setGuzzle($guzzle);
            $this->assertSame($response, $client->$method('path', ['foo' => $method]));

            $guzzle = m::mock(Guzzle::class);
            $guzzle->shouldReceive('requestAsync')
                ->with($method, 'path', m::subset(['foo' => $method]))
                ->once()
                ->andReturn($method);
            $client->setGuzzle($guzzle);
            $response = $client->{$method.'Async'}('path', ['foo' => $method]);
            $this->assertSame($method, $response);
        }
    }

    public function testMagicRequestParameters()
    {
        $client = new TestClient(['foo' => 'bar']);

        $guzzle = m::mock(Guzzle::class);
        $guzzle->shouldReceive('request')
            ->with('get', '', m::subset(['foo' => 'bar']))
            ->once()
            ->andReturn($response = new Response);
        $client->setGuzzle($guzzle);
        $this->assertSame($response, $client->get());

        $guzzle = m::mock(Guzzle::class);
        $guzzle->shouldReceive('request')
            ->with('post', 'path', m::subset(['foo' => 'bar']))
            ->once()
            ->andReturn($response = new Response);
        $client->setGuzzle($guzzle);
        $this->assertSame($response, $client->post('path'));

        $guzzle = m::mock(Guzzle::class);
        $guzzle->shouldReceive('request')
            ->with('put', 'path', m::subset(['foo' => 'bar', 'a' => 'A']))
            ->once()
            ->andReturn($response = new Response);
        $client->setGuzzle($guzzle);
        $this->assertSame($response, $client->put('path', ['a' => 'A']));
    }

    public function testMagicOptionMethods()
    {
        $client = new HttpClient;

        $client->debug(true);
        $this->assertTrue($client->getOption('debug'));

        $client->decodeContent(false);
        $this->assertFalse($client->getOption('decode_content'));

        $client->json(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $client->getOption('json'));

        $client->formParams(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $client->getOption('form_params'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Method [readTimeout] needs one argument.');
        $client->readTimeout();
    }

    public function testBadMethodCall()
    {
        $client = new HttpClient;
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Method [fooBar] does not exist.');
        $client->fooBar();
    }
}

class TestClient extends HttpClient
{
    public function setGuzzle($client)
    {
        $this->client = $client;

        return $this;
    }

    public function _getMagicOptionMethods()
    {
        return $this->getMagicOptionMethods();
    }
}

class TestException extends \Exception
{
}
