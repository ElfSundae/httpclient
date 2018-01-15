<?php

namespace ElfSundae;

use Exception;
use ReflectionClass;
use GuzzleHttp\Client;
use BadMethodCallException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use GuzzleHttp\RequestOptions;

/**
 * @method \Psr\Http\Message\ResponseInterface|null get(string|\Psr\Http\Message\UriInterface $uri = '', array $options = [])
 * @method \Psr\Http\Message\ResponseInterface|null head(string|\Psr\Http\Message\UriInterface $uri = '', array $options = [])
 * @method \Psr\Http\Message\ResponseInterface|null post(string|\Psr\Http\Message\UriInterface $uri = '', array $options = [])
 * @method \Psr\Http\Message\ResponseInterface|null put(string|\Psr\Http\Message\UriInterface $uri = '', array $options = [])
 * @method \Psr\Http\Message\ResponseInterface|null patch(string|\Psr\Http\Message\UriInterface $uri = '', array $options = [])
 * @method \Psr\Http\Message\ResponseInterface|null delete(string|\Psr\Http\Message\UriInterface $uri = '', array $options = [])
 * @method \Psr\Http\Message\ResponseInterface|null options(string|\Psr\Http\Message\UriInterface $uri = '', array $options = [])
 * @method mixed getJson(string|\Psr\Http\Message\UriInterface $uri = '', array $options = [])
 * @method mixed postJson(string|\Psr\Http\Message\UriInterface $uri = '', array $options = [])
 * @method mixed putJson(string|\Psr\Http\Message\UriInterface $uri = '', array $options = [])
 * @method mixed patchJson(string|\Psr\Http\Message\UriInterface $uri = '', array $options = [])
 * @method mixed deleteJson(string|\Psr\Http\Message\UriInterface $uri = '', array $options = [])
 * @method \GuzzleHttp\Promise\PromiseInterface getAsync(string|\Psr\Http\Message\UriInterface $uri = '', array $options = [])
 * @method \GuzzleHttp\Promise\PromiseInterface headAsync(string|\Psr\Http\Message\UriInterface $uri = '', array $options = [])
 * @method \GuzzleHttp\Promise\PromiseInterface postAsync(string|\Psr\Http\Message\UriInterface $uri = '', array $options = [])
 * @method \GuzzleHttp\Promise\PromiseInterface putAsync(string|\Psr\Http\Message\UriInterface $uri = '', array $options = [])
 * @method \GuzzleHttp\Promise\PromiseInterface patchAsync(string|\Psr\Http\Message\UriInterface $uri = '', array $options = [])
 * @method \GuzzleHttp\Promise\PromiseInterface deleteAsync(string|\Psr\Http\Message\UriInterface $uri = '', array $options = [])
 * @method \GuzzleHttp\Promise\PromiseInterface optionsAsync(string|\Psr\Http\Message\UriInterface $uri = '', array $options = [])
 * @method $this allowRedirects(bool|array $value)
 * @method $this auth(array|string|null $value)
 * @method $this body(string|resource|\Psr\Http\Message\StreamInterface $value)
 * @method $this cert(string|array $value)
 * @method $this cookies(bool|\GuzzleHttp\Cookie\CookieJarInterface $value)
 * @method $this connectTimeout(float $value)
 * @method $this debug(bool|resource $value)
 * @method $this decodeContent(string|bool $value)
 * @method $this delay(int|float $value)
 * @method $this expect(bool|int $value)
 * @method $this forceIpResolve(string $value)
 * @method $this formParams(array $value)
 * @method $this headers(array $value)
 * @method $this httpErrors(bool $value)
 * @method $this json(mixed $value)
 * @method $this onHeaders(callable $value)
 * @method $this onStats(callable $value)
 * @method $this progress(callable $value)
 * @method $this proxy(string|array $value)
 * @method $this query(array|string $value)
 * @method $this readTimeout(float $value)
 * @method $this sink(string|resource|\Psr\Http\Message\StreamInterface $value)
 * @method $this sslKey(string|array $value)
 * @method $this stream(bool $value)
 * @method $this verify(bool|string $value)
 * @method $this timeout(float $value)
 * @method $this version(float|string $value)
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
        'catch_exceptions' => true,
        'http_errors' => false,
        'connect_timeout' => 5,
        'timeout' => 20,
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
     * All allowed magic request methods (verbs).
     *
     * @var array
     */
    protected $magicRequestMethods = [
        'get', 'head', 'post', 'put', 'patch', 'delete', 'options',
    ];

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
     * Create a new HTTP client instance.
     *
     * @param  array|string|\Psr\Http\Message\UriInterface  $options  base URI or any request options
     * @return static
     */
    public static function create($options = [])
    {
        return new static($options);
    }

    /**
     * Create a new HTTP client instance.
     *
     * @param  array|string|\Psr\Http\Message\UriInterface  $options  base URI or any request options
     */
    public function __construct($options = [])
    {
        if (! is_array($options)) {
            $options = ['base_uri' => $options];
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
     * Get the request options using "dot" notation.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getOption($key = null, $default = null)
    {
        return Arr::get($this->options, $key, $default);
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
     * @param  array  $options
     * @return $this
     */
    public function mergeOptions(array $options)
    {
        $this->options = array_replace_recursive($this->options, $options);

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
     * Remove one or many request headers.
     *
     * @param  array|string  $names
     * @return $this
     */
    public function removeHeader($names)
    {
        if (is_array($headers = $this->getOption('headers'))) {
            $names = is_array($names) ? $names : func_get_args();

            $this->option('headers', Arr::except($headers, $names));
        }

        return $this;
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
     * Set user agent for the request.
     *
     * @param  string  $value
     * @return $this
     */
    public function userAgent($value)
    {
        return $this->header('User-Agent', $value);
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
     * Specify where the body of the response will be saved.
     * Set the "sink" option.
     *
     * @param  string|resource|\Psr\Http\Message\StreamInterface  $dest
     * @return $this
     */
    public function saveTo($dest)
    {
        return $this->option('sink', $dest);
    }

    /**
     * Set the body of the request to a multipart/form-data form.
     *
     * @param  array  $data
     * @return $this
     */
    public function multipart(array $data)
    {
        $multipart = [];

        foreach ($data as $key => $value) {
            if (! is_array($value)) {
                $value = ['contents' => $value];
            }

            if (! is_int($key)) {
                $value['name'] = $key;
            }

            $multipart[] = $value;
        }

        return $this->option('multipart', $multipart);
    }

    /**
     * Determine whether to catch Guzzle exceptions.
     *
     * @return bool
     */
    public function areExceptionsCaught()
    {
        return $this->getOption('catch_exceptions', false);
    }

    /**
     * Set whether to catch Guzzle exceptions or not.
     *
     * @param  bool  $catch
     * @return $this
     */
    public function catchExceptions($catch)
    {
        return $this->option('catch_exceptions', (bool) $catch);
    }

    /**
     * Send request to a URI.
     *
     * @param  string|\Psr\Http\Message\UriInterface  $uri
     * @param  string  $method
     * @param  array  $options
     * @return \Psr\Http\Message\ResponseInterface|null
     */
    public function request($uri = '', $method = 'GET', array $options = [])
    {
        try {
            return $this->client->request(
                $method, $uri, $this->getRequestOptions($options)
            );
        } catch (Exception $e) {
            if (! $this->areExceptionsCaught()) {
                throw $e;
            }
        }
    }

    /**
     * Send request to a URI, expecting JSON content.
     *
     * @param  string|\Psr\Http\Message\UriInterface  $uri
     * @param  string  $method
     * @param  array  $options
     * @return \Psr\Http\Message\ResponseInterface|null
     */
    public function requestJson($uri = '', $method = 'GET', array $options = [])
    {
        Arr::set($options, 'headers.Accept', 'application/json');

        return $this->request($uri, $method, $options);
    }

    /**
     * Send asynchronous request to a URI.
     *
     * @param  string|\Psr\Http\Message\UriInterface  $uri
     * @param  string  $method
     * @param  array  $options
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function requestAsync($uri = '', $method = 'GET', array $options = [])
    {
        return $this->client->requestAsync(
            $method, $uri, $this->getRequestOptions($options)
        );
    }

    /**
     * Get options for the Guzzle request method.
     *
     * @param  array  $options
     * @return array
     */
    protected function getRequestOptions(array $options = [])
    {
        $options = array_replace_recursive($this->options, $options);

        $this->removeOption([
            'body', 'form_params', 'multipart', 'json', 'query',
            'sink', 'save_to', 'stream',
            'on_headers', 'on_stats', 'progress',
            'headers.Content-Type',
        ]);

        return $options;
    }

    /**
     * Request the URI and return the response content.
     *
     * @param  string|\Psr\Http\Message\UriInterface  $uri
     * @param  string  $method
     * @param  array  $options
     * @return string|null
     */
    public function fetchContent($uri = '', $method = 'GET', array $options = [])
    {
        if ($response = $this->request($uri, $method, $options)) {
            return (string) $response->getBody();
        }
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
        if ($response = $this->requestJson($uri, $method, $options)) {
            return json_decode($response->getBody(), true);
        }
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
        $requestMethod = $httpMethod = null;

        foreach ($this->magicRequestMethods as $verb) {
            if ($method == $verb) {
                $requestMethod = 'request';
            } elseif ($method == $verb.'Async') {
                $requestMethod = 'requestAsync';
            } elseif ($method == $verb.'Json') {
                $requestMethod = 'fetchJson';
            }

            if ($requestMethod) {
                return (bool) $httpMethod = $verb;
            }
        }

        return false;
    }

    /**
     * Get parameters for the request() method from the magic request call.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return array
     */
    protected function getRequestParameters($method, array $parameters)
    {
        if (empty($parameters)) {
            $parameters = ['', $method];
        } else {
            array_splice($parameters, 1, 0, $method);
        }

        return $parameters;
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
            $optionMethods = array_map(
                [Str::class, 'camel'],
                array_values($reflector->getConstants())
            );
        }

        return $optionMethods;
    }

    /**
     * Determine if the given method is a magic option method.
     *
     * @param  string  $method
     * @return bool
     */
    protected function isMagicOptionMethod($method, &$option)
    {
        $option = in_array($method, $this->getMagicOptionMethods())
            ? Str::snake($method) : null;

        return (bool) $option;
    }

    /**
     * Handle magic option/request methods.
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
        if ($this->isMagicRequestMethod($method, $requestMethod, $httpMethod)) {
            return $this->$requestMethod(...$this->getRequestParameters($httpMethod, $parameters));
        }

        if ($this->isMagicOptionMethod($method, $option)) {
            if (empty($parameters)) {
                throw new InvalidArgumentException("Method [$method] needs one argument.");
            }

            return $this->option($option, $parameters[0]);
        }

        throw new BadMethodCallException("Method [$method] does not exist.");
    }
}
