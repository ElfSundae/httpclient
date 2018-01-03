<?php

namespace ElfSundae;

use Closure;
use Exception;
use ReflectionClass;
use GuzzleHttp\Client;
use BadMethodCallException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\UriInterface;

/**
 * @method $this get(string|UriInterface $uri = '', array $options = [])
 * @method $this head(string|UriInterface $uri = '', array $options = [])
 * @method $this post(string|UriInterface $uri = '', array $options = [])
 * @method $this put(string|UriInterface $uri = '', array $options = [])
 * @method $this patch(string|UriInterface $uri = '', array $options = [])
 * @method $this delete(string|UriInterface $uri = '', array $options = [])
 * @method $this options(string|UriInterface $uri = '', array $options = [])
 * @method $this getJson(string|UriInterface $uri = '', array $options = [])
 * @method $this postJson(string|UriInterface $uri = '', array $options = [])
 * @method $this putJson(string|UriInterface $uri = '', array $options = [])
 * @method $this patchJson(string|UriInterface $uri = '', array $options = [])
 * @method $this deleteJson(string|UriInterface $uri = '', array $options = [])
 * @method int getStatusCode()
 * @method string getReasonPhrase()
 * @method string getProtocolVersion()
 * @method array getHeaders()
 * @method bool hasHeader(string $header)
 * @method array getHeader(string $header)
 * @method string getHeaderLine(string $header)
 * @method \Psr\Http\Message\StreamInterface getBody()
 * @method $this allowRedirects(bool|array $value)
 * @method $this auth(array|string|null $value)
 * @method $this body(mixed $value)
 * @method $this cert(string|array $value)
 * @method $this cookies(bool|\GuzzleHttp\Cookie\CookieJarInterface $value)
 * @method $this connectTimeout(float $value)
 * @method $this debug(bool|resource $value)
 * @method $this decodeContent(bool $value)
 * @method $this delay(int|float $value)
 * @method $this expect(bool|int $value)
 * @method $this formParams(array $value)
 * @method $this headers(array $value)
 * @method $this httpErrors(bool $value)
 * @method $this json(mixed $value)
 * @method $this multipart(array $value)
 * @method $this onHeaders(callable $value)
 * @method $this onStats(callable $value)
 * @method $this progress(callable $value)
 * @method $this proxy(string|array $value)
 * @method $this query(array|string $value)
 * @method $this sink(string|resource|\Psr\Http\Message\StreamInterface $value)
 * @method $this sslKey(array|string $value)
 * @method $this stream(bool $value)
 * @method $this verify(bool|string $value)
 * @method $this timeout(float $value)
 * @method $this readTimeout(float $value)
 * @method $this version(float|string $value)
 * @method $this forceIpResolve(string $value)
 *
 * @see http://docs.guzzlephp.org/en/stable/request-options.html Request Options
 */
class HttpClient
{
    /**
     * The default request options.
     *
     * @var array
     */
    protected static $defaultOptions = [
        'connect_timeout' => 5,
        'timeout' => 30,
    ];

    /**
     * The Guzzle client.
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * The request options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * The Guzzle response.
     *
     * @var \GuzzleHttp\Psr7\Response
     */
    protected $response;

    /**
     * Indicate whether to catch Guzzle exceptions.
     *
     * @var bool
     */
    protected $catchExceptions = true;

    /**
     * Get the default request options.
     *
     * @return array
     */
    public static function defaultOptions()
    {
        return static::$defaultOptions;
    }

    /**
     * Set the default request options.
     *
     * @param  array  $options
     * @return void
     */
    public static function setDefaultOptions(array $options)
    {
        static::$defaultOptions = $options;
    }

    /**
     * Create a http client instance.
     *
     * @param  array|string|\Psr\Http\Message\UriInterface  $options  base URI or any request options
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($options = [])
    {
        if (is_string($options) || $options instanceof UriInterface) {
            $options = ['base_uri' => $options];
        } elseif (! is_array($options)) {
            throw new InvalidArgumentException('Options must be a string, UriInterface, or an array');
        }

        $this->client = new Client(
            array_replace_recursive(static::defaultOptions(), $options)
        );

        $this->options = $this->client->getConfig();
    }

    /**
     * Get the Guzzle client instance.
     *
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Get whether to catch Guzzle exceptions or not.
     *
     * @return bool
     */
    public function areExceptionsCaught()
    {
        return $this->catchExceptions;
    }

    /**
     * Set whether to catch Guzzle exceptions or not.
     *
     * @param  bool  $catch
     * @return $this
     */
    public function catchExceptions($catch)
    {
        $this->catchExceptions = (bool) $catch;

        return $this;
    }

    /**
     * Get the request options using "dot" notation.
     *
     * @param  string|null  $key
     * @return mixed
     */
    public function getOption($key = null)
    {
        return Arr::get($this->options, $key);
    }

    /**
     * Set the request options using "dot" notation.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return $this
     */
    public function option($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            Arr::set($this->options, $key, $value);
        }

        return $this;
    }

    /**
     * Merge the given options to the request options.
     *
     * @param  array  ...$options
     * @return $this
     */
    public function mergeOptions(array ...$options)
    {
        $this->options = array_replace_recursive($this->options, ...$options);

        return $this;
    }

    /**
     * Remove one or many request options using "dot" notation.
     *
     * @param  array|string  $keys
     * @return $this
     */
    public function removeOption($keys)
    {
        Arr::forget($this->options, is_array($keys) ? $keys : func_get_args());

        return $this;
    }

    /**
     * Set a request header.
     *
     * @param  string  $name
     * @param  mixed  $value
     * @return $this
     */
    public function header($name, $value)
    {
        return $this->option('headers.'.$name, $value);
    }

    /**
     * Set the request content type.
     *
     * @param  string  $type
     * @return $this
     */
    public function contentType($type)
    {
        return $this->header('Content-Type', $type);
    }

    /**
     * Set the request accept type.
     *
     * @param  string  $type
     * @return $this
     */
    public function accept($type)
    {
        return $this->header('Accept', $type);
    }

    /**
     * Set the request accept type to "application/json".
     *
     * @return $this
     */
    public function acceptJson()
    {
        return $this->accept('application/json');
    }

    /**
     * Specify where the body of the response will be saved.
     * Set the "sink" option.
     *
     * @param  string|resource|\Psr\Http\Message\StreamInterface  $dest
     * @return $this
     */
    public function saveTo($dest)
    {
        return $this->removeOption('save_to')->option('sink', $dest);
    }

    /**
     * Get the Guzzle response instance.
     *
     * @return \GuzzleHttp\Psr7\Response|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get data from the response.
     *
     * @param  string|\Closure  $callback
     * @param  array  $parameters
     * @param  mixed  $default
     * @return mixed
     */
    protected function getResponseData($callback, array $parameters = [], $default = null)
    {
        if ($this->response) {
            return $callback instanceof Closure
                ? $callback($this->response, ...$parameters)
                : $this->response->$callback(...$parameters);
        }

        return $default;
    }

    /**
     * Get the response content.
     *
     * @return string
     */
    public function getContent()
    {
        return (string) $this->getBody();
    }

    /**
     * Get the JSON-decoded response content.
     *
     * @param  bool  $assoc
     * @return mixed
     */
    public function getJsonContent($assoc = true)
    {
        return json_decode($this->getContent(), $assoc);
    }

    /**
     * Make request to a URI.
     *
     * @param  string|\Psr\Http\Message\UriInterface  $uri
     * @param  string  $method
     * @param  array  $options
     * @return $this
     */
    public function request($uri = '', $method = 'GET', array $options = [])
    {
        $this->response = null;

        $method = strtoupper($method);
        $options = array_replace_recursive($this->options, $options);

        try {
            $this->response = $this->client->request($method, $uri, $options);
        } catch (Exception $e) {
            if (! $this->catchExceptions) {
                throw $e;
            }
        }

        return $this;
    }

    /**
     * Make request to a URI, expecting JSON content.
     *
     * @param  string|\Psr\Http\Message\UriInterface  $uri
     * @param  string  $method
     * @param  array  $options
     * @return $this
     */
    public function requestJson($uri = '', $method = 'GET', array $options = [])
    {
        $options = $this->addAcceptableJsonType(
            array_replace_recursive($this->options, $options)
        );

        return $this->request($uri, $method, $options);
    }

    /**
     * Add JSON type to the "Accept" header for the request options.
     *
     * @param  array  $options
     * @return array
     */
    protected function addAcceptableJsonType(array $options)
    {
        $accept = Arr::get($options, 'headers.Accept', '');

        if (! Str::contains($accept, ['/json', '+json'])) {
            $accept = rtrim('application/json,'.$accept, ',');
            Arr::set($options, 'headers.Accept', $accept);
        }

        return $options;
    }

    /**
     * Request the URI and return the response content.
     *
     * @param  string|\Psr\Http\Message\UriInterface  $uri
     * @param  string  $method
     * @param  array  $options
     * @return string
     */
    public function fetchContent($uri = '', $method = 'GET', array $options = [])
    {
        return $this->request($uri, $method, $options)->getContent();
    }

    /**
     * Request the URI and return the JSON-decoded response content.
     *
     * @param  string|\Psr\Http\Message\UriInterface  $uri
     * @param  string  $method
     * @param  array  $options
     * @return mixed
     */
    public function fetchJson($uri = '', $method = 'GET', array $options = [])
    {
        return $this->requestJson($uri, $method, $options)->getJsonContent();
    }

    /**
     * Get all allowed magic request methods.
     *
     * @return array
     */
    protected function getMagicRequestMethods()
    {
        return [
            'get', 'head', 'post', 'put', 'patch', 'delete', 'options',
        ];
    }

    /**
     * Determine if the given method is a magic request method.
     *
     * @param  string  $method
     * @param  string  &$requestMethod
     * @param  string  &$httpMethod
     * @return bool
     */
    protected function isMagicRequestMethod($method, &$requestMethod, &$httpMethod)
    {
        if (strlen($method) > 4 && $pos = strrpos($method, 'Json', -4)) {
            $httpMethod = substr($method, 0, $pos);
            $requestMethod = 'requestJson';
        } else {
            $httpMethod = $method;
            $requestMethod = 'request';
        }

        if (in_array($httpMethod, $this->getMagicRequestMethods())) {
            return true;
        }

        $httpMethod = $requestMethod = null;

        return false;
    }

    /**
     * Get parameters for $this->request() from the magic request methods.
     *
     * @param  string  $httpMethod
     * @param  array  $parameters
     * @return array
     */
    protected function getRequestParameters($httpMethod, array $parameters)
    {
        if (empty($parameters)) {
            $parameters = ['', $httpMethod];
        } else {
            array_splice($parameters, 1, 0, $httpMethod);
        }

        return $parameters;
    }

    /**
     * Get all allowed magic response methods.
     *
     * @return array
     */
    protected function getMagicResponseMethods()
    {
        return [
            'getStatusCode', 'getReasonPhrase', 'getProtocolVersion',
            'getHeaders', 'hasHeader', 'getHeader', 'getHeaderLine', 'getBody',
        ];
    }

    /**
     * Get all allowed magic option methods.
     *
     * @return array
     */
    protected function getMagicOptionMethods()
    {
        static $optionMethods = null;

        if (is_null($optionMethods)) {
            $reflector = new ReflectionClass(RequestOptions::class);
            $options = array_values(array_diff($reflector->getConstants(), [
                'synchronous',
            ]));
            $optionMethods = array_map([Str::class, 'camel'], $options);
        }

        return $optionMethods;
    }

    /**
     * Get the option key for the given magic option method.
     *
     * @param  string  $method
     * @return string|null
     */
    protected function getOptionKeyForMethod($method)
    {
        if (in_array($method, $this->getMagicOptionMethods())) {
            return Str::snake($method);
        }
    }

    /**
     * Handle magic method to send request, get response data, or set
     * request options.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if ($this->isMagicRequestMethod($method, $request, $httpMethod)) {
            return $this->{$request}(
                ...$this->getRequestParameters($httpMethod, $parameters)
            );
        }

        if (in_array($method, $this->getMagicResponseMethods())) {
            return $this->getResponseData($method, $parameters);
        }

        if ($option = $this->getOptionKeyForMethod($method)) {
            if (empty($parameters)) {
                throw new InvalidArgumentException("Method [$method] needs one argument.");
            }

            return $this->option($option, $parameters[0]);
        }

        throw new BadMethodCallException("Method [$method] does not exist.");
    }
}
