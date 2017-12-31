<?php

namespace ElfSundae;

use Closure;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

class HttpClient
{
    /**
     * The default request options.
     *
     * @var array
     */
    protected static $defaultOptions = [
        'connect_timeout' => 5,
        'timeout' => 25,
    ];

    /**
     * The Guzzle client.
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * The Guzzle response.
     *
     * @var \GuzzleHttp\Psr7\Response
     */
    protected $response;

    /**
     * The request options.
     *
     * @var array
     */
    protected $options = [];

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
            throw new InvalidArgumentException('config must be a string, UriInterface, or an array');
        }

        $this->client = new Client(
            $this->options = $options + static::defaultOptions()
        );
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
     * Get the request options.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set the request options.
     *
     * @param  array  $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

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
        return $this->setOptions($options + $this->options);
    }

    /**
     * Remove request options using "dot" notation.
     *
     * @param  string|array|null $key
     * @return $this
     */
    public function removeOptions($key = null)
    {
        if (is_null($key)) {
            $this->options = [];
        } else {
            Arr::forget($this->options, is_array($key) ? $key : func_get_args());
        }

        return $this;
    }

    /**
     * Get a request option using "dot" notation.
     *
     * @param  string $key
     * @return mixed
     */
    public function getOption($key)
    {
        return Arr::get($this->options, $key);
    }

    /**
     * Set a request option using "dot" notation.
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
     * Set the request header.
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
     * Set the request accept type to JSON.
     *
     * @return $this
     */
    public function acceptJson()
    {
        return $this->accept('application/json');
    }

    /**
     * Specify where the body of a response will be saved.
     * Set the "sink" option.
     *
     * @param  mixed  $dest
     * @return $this
     */
    public function saveTo($dest)
    {
        return $this->removeOptions('save_to')->option('sink', $dest);
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
     * Get the decoded JSON response.
     *
     * @param  bool  $assoc
     * @return mixed
     */
    public function json($assoc = true)
    {
        if ($this->response) {
            return json_decode($this->getContent(), $assoc);
        }
    }

    /**
     * Make request to a URI.
     *
     * @param  string  $uri
     * @param  string  $method
     * @param  array  $options
     * @return $this
     */
    public function request($uri, $method = 'GET', array $options = [])
    {
        $options += $this->options;

        $this->response = null;

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
     * @param  string  $uri
     * @param  string  $method
     * @param  array  $options
     * @return $this
     */
    public function requestJson($uri, $method = 'GET', array $options = [])
    {
        Arr::set($options, 'headers.Accept', 'application/json');

        return $this->request($uri, $method, $options);
    }

    /**
     * Request the URI and return the response content.
     *
     * @param  string  $uri
     * @param  string  $method
     * @param  array  $options
     * @return string|null
     */
    public function fetchContent($uri, $method = 'GET', array $options = [])
    {
        return $this->request($uri, $method, $options)->getContent();
    }

    /**
     * Request the URI and return the JSON decoded response content.
     *
     * @param  string  $uri
     * @param  string  $method
     * @param  array  $options
     * @param  bool  $assoc
     * @return mixed
     */
    public function fetchJson($uri, $method = 'GET', array $options = [], $assoc = true)
    {
        return $this->requestJson($uri, $method, $options)->getJson($assoc);
    }

    /**
     * Get the dynamic response methods.
     *
     * @return array
     */
    protected function getDynamicResponseMethods()
    {
        return [
            'getStatusCode', 'getReasonPhrase', 'getProtocolVersion',
            'getHeaders', 'hasHeader', 'getHeader', 'getHeaderLine', 'getBody',
        ];
    }

    /**
     * Dynamically methods to set request option, send request, or get
     * response properties.
     *
     * @param  string  $method
     * @param  array  $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        // Handle magic request methods
        if (in_array($method, ['get', 'head', 'put', 'post', 'patch', 'delete'])) {
            if (count($args) < 1) {
                throw new InvalidArgumentException('Magic request methods require an URI and optional options array');
            }

            $url = $args[0];
            $options = isset($args[1]) ? $args[1] : [];

            return $this->request($url, $method, $options);
        }

        if (in_array($method, $this->getDynamicResponseMethods())) {
            return $this->getResponseData($method, $args);
        }

        // Handle setting request options
        return $this->option(Str::snake($method), $args[0]);
    }
}
