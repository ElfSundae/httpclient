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
     * @param  array  $options,...
     * @return $this
     */
    public function mergeOption(array ...$options)
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
     * @param  mixed  $dest
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
    public function json($assoc = true)
    {
        return json_decode($this->getContent(), $assoc);
    }

    /**
     * Make request to a URI.
     *
     * @param  string  $uri
     * @param  string  $method
     * @param  array  $options
     * @return $this
     */
    public function request($uri = '', $method = 'GET', array $options = [])
    {
        $options = array_replace_recursive($this->options, $options);

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
     * @param  string  $uri
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
     * @param  string  $uri
     * @param  string  $method
     * @param  array  $options
     * @return mixed
     */
    public function fetchJson($uri = '', $method = 'GET', array $options = [])
    {
        return $this->requestJson($uri, $method, $options)->json();
    }

    /**
     * Get the dynamic request methods.
     *
     * @return array
     */
    protected function getDynamicRequestMethods()
    {
        return [
            'get', 'head', 'put', 'post', 'patch', 'delete', 'options',
        ];
    }

    /**
     * Get the dynamic requestJson methods.
     *
     * @return array
     */
    protected function getDynamicRequestJsonMethods()
    {
        return [
            'get', 'put', 'post', 'patch', 'delete',
        ];
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
     * Insert HTTP method to the parameters.
     *
     * @param  array  $parameters
     * @return array
     */
    protected function insertHttpMethodToParameters($method, array $parameters)
    {
        $method = strtoupper($method);

        if (empty($parameters)) {
            $parameters = ['', $method];
        } else {
            array_splice($parameters, 1, 0, $method);
        }

        return $parameters;
    }

    /**
     * Dynamically send request, get response data, or set request option.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, $this->getDynamicRequestMethods())) {
            return $this->request(
                ...$this->insertHttpMethodToParameters($method, $parameters)
            );
        }

        if (in_array($method, $this->getDynamicRequestJsonMethods())) {
            return $this->requestJson(
                ...$this->insertHttpMethodToParameters($method, $parameters)
            );
        }

        if (in_array($method, $this->getDynamicResponseMethods())) {
            return $this->getResponseData($method, $parameters);
        }

        return $this->option(Str::snake($method), ...$parameters);
    }
}
