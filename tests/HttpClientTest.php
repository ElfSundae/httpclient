<?php

namespace ElfSundae\Test;

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

    public function testDefaultOptions()
    {
        HttpClient::setDefaultOptions(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], HttpClient::defaultOptions());
    }

    public function testInstantiation()
    {
        $client = new HttpClient;
        $this->assertInstanceOf(HttpClient::class, $client);
        $this->assertInstanceOf(Guzzle::class, $client->getClient());

        $this->assertInstanceOf(HttpClient::class, HttpClient::new());
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

    public function testSetCatchExceptions()
    {
        $client = new HttpClient;
        $this->assertSame(true, $client->areExceptionsCaught());

        $client->catchExceptions(false);
        $this->assertSame(false, $client->areExceptionsCaught());
        $client->catchExceptions(true);
        $this->assertSame(true, $client->areExceptionsCaught());
        $client->catchExceptions(null);
        $this->assertSame(false, $client->areExceptionsCaught());
    }

    public function testGetOption()
    {
        $client = new HttpClient(['foo' => 'bar']);
        $this->assertArraySubset($client->getClient()->getConfig(), $client->getOption());
        $this->assertArraySubset(['foo' => 'bar'], $client->getOption());
        $this->assertSame('bar', $client->getOption('foo'));
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
        $client = new HttpClient([
            'save_to' => 'save_to_path',
        ]);
        $client->saveTo('sink_path');
        $this->assertSame('sink_path', $client->getOption('sink'));
        $this->assertNull($client->getOption('save_to'));
    }

    public function testGetResponse()
    {
        $response = new Response;
        $handler = MockHandler::createWithMiddleware([$response]);
        $client = new HttpClient(compact('handler'));
        $client->request();
        $this->assertSame($response, $client->getResponse());
    }

    public function testGetResponseData()
    {
        $response = new Response(201, ['foo' => 'bar'], 'response body');
        $handler = MockHandler::createWithMiddleware([$response]);
        $client = new HttpClient(compact('handler'));
        $client->request();

        $this->assertSame(201, $client->getResponseData('getStatusCode'));
        $this->assertSame('bar', $client->getResponseData('getHeaderLine', 'foo'));

        $clone = $client->getResponseData('withHeader', ['X-Foo', 'Bar']);
        $this->assertInstanceOf(Response::class, $clone);
        $this->assertSame('Bar', $clone->getHeaderLine('X-Foo'));

        $closure = $client->getResponseData(function ($response, $foo, $bar) {
            $this->assertSame('foo', $foo);
            $this->assertSame('bar', $bar);

            return 'closure';
        }, ['foo', 'bar']);
        $this->assertSame('closure', $closure);

        $client = new HttpClient;
        $default = $client->getResponseData('getStatusCode', [], 'default');
        $this->assertSame('default', $default);
    }

    public function testGetContent()
    {
        $response = new Response(200, [], 'foobar');
        $handler = MockHandler::createWithMiddleware([$response]);
        $client = new HttpClient(compact('handler'));
        $client->request();
        $this->assertSame('foobar', $client->getContent());
    }

    public function testGetJsonContent()
    {
        $response = new Response(200, [], json_encode(['foo' => 'bar']));
        $handler = MockHandler::createWithMiddleware([$response]);
        $client = new HttpClient(compact('handler'));
        $client->request();
        $this->assertSame(['foo' => 'bar'], $client->getJsonContent());
    }

    public function testRequest()
    {
        $client = new TestClient(['a' => 'A']);

        $guzzle = m::mock(Guzzle::class);
        $guzzle->shouldReceive('request')
            ->with('GET', 'path', m::subset(['a' => 'A', 'b' => 'B']))
            ->once()
            ->andReturn('response');
        $client->setGuzzle($guzzle);
        $client->request('path', 'get', ['b' => 'B']);
        $this->assertSame('response', $client->getResponse());

        $guzzle = m::mock(Guzzle::class);
        $guzzle->shouldReceive('request')
            ->with('POST', 'path1', m::subset(['a' => 'A']))
            ->twice()
            ->andThrow(new TestException);
        $client->setGuzzle($guzzle);
        $client->request('path1', 'post');
        $this->assertNull($client->getResponse());

        $this->expectException(TestException::class);
        $client->catchExceptions(false);
        $client->request('path1', 'post');
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

        $client->accept('foo/bar');
        $this->assertSame('foo/bar', $client->getOption('headers.Accept'));
        $guzzle = m::mock(Guzzle::class);
        $guzzle->shouldReceive('request')
            ->with('PUT', 'path', m::subset(['headers' => ['Accept' => 'application/json,foo/bar']]))
            ->once()
            ->andReturn('response');
        $client->setGuzzle($guzzle);
        $client->requestJson('path', 'PUT');

        $client->accept('application/json');
        $this->assertSame('application/json', $client->getOption('headers.Accept'));
        $guzzle = m::mock(Guzzle::class);
        $guzzle->shouldReceive('request')
            ->with('GET', 'path', m::subset(['headers' => ['Accept' => 'application/json']]))
            ->once()
            ->andReturn('response');
        $client->setGuzzle($guzzle);
        $client->requestJson('path', 'GET');
    }

    public function testFetchContent()
    {
        $response = new Response(200, [], 'foobar');
        $handler = MockHandler::createWithMiddleware([$response]);
        $client = new HttpClient(compact('handler'));
        $this->assertSame('foobar', $client->fetchContent());
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
                ->with(strtoupper($method), $method.'-path', m::subset(['_key_' => $method]))
                ->once()
                ->andReturn($method);
            $client->setGuzzle($guzzle);
            $client->$method($method.'-path', ['_key_' => $method]);
            $this->assertSame($method, $client->getResponse());
        }
    }

    public function testMagicRequestParameters()
    {
        $client = new TestClient(['foo' => 'bar']);

        $guzzle = m::mock(Guzzle::class);
        $guzzle->shouldReceive('request')
            ->with('GET', '', m::subset(['foo' => 'bar']))
            ->once()
            ->andReturn('response');
        $client->setGuzzle($guzzle);
        $client->get();

        $guzzle = m::mock(Guzzle::class);
        $guzzle->shouldReceive('request')
            ->with('POST', 'path', m::subset(['foo' => 'bar']))
            ->once()
            ->andReturn('response');
        $client->setGuzzle($guzzle);
        $client->post('path');

        $guzzle = m::mock(Guzzle::class);
        $guzzle->shouldReceive('request')
            ->with('PUT', 'path', m::subset(['foo' => 'bar', 'a' => 'A']))
            ->once()
            ->andReturn('response');
        $client->setGuzzle($guzzle);
        $client->put('path', ['a' => 'A']);
    }

    public function testMagicResponseMethods()
    {
        $response = new Response(202, ['foo' => 'bar'], 'response body', '2');
        $handler = MockHandler::createWithMiddleware([$response]);
        $client = new HttpClient(compact('handler'));
        $client->request();
        $this->assertSame(202, $client->getStatusCode());
        $this->assertSame('2', $client->getProtocolVersion());
        $this->assertTrue($client->hasHeader('foo'));
        $this->assertSame('bar', $client->getHeaderLine('foo'));
        $this->assertSame('response body', (string) $client->getBody());
    }

    public function testMagicOptionMethods()
    {
        $client = new HttpClient;

        // Test all options to ensure we did not define the same name method
        // in HttpClient
        foreach ((new TestClient)->_getMagicOptionMethods() as $method) {
            $client->$method('foo');
            $this->assertSame('foo', $client->getOption(snake_case($method)));
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Method [body] needs one argument.');
        $client->body();
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
