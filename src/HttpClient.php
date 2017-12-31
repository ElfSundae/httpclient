<?php

namespace ElfSundae;

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
     * Remove options using "dot" notation.
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
     * Get the status code of response.
     *
     * @return int
     */
    public function getStatusCode()
    {
        if ($this->response) {
            return $this->response->getStatusCode();
        }
    }

    /**
     * Get the response header value.
     *
     * @param  string  $name
     * @return mixed
     */
    public function getHeader($name)
    {
        if ($this->response) {
            return $this->response->getHeaderLine($name);
        }
    }

    /**
     * Get all response headers values.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->response ? $this->response->getHeaders() : [];
    }

    /**
     * Get response body.
     *
     * @return \GuzzleHttp\Psr7\Stream|null
     */
    public function getBody()
    {
        if ($this->response) {
            return $this->response->getBody();
        }
    }

    /**
     * Get response content.
     *
     * @return string|null
     */
    public function getContent()
    {
        if ($this->response) {
            return (string) $this->getBody();
        }
    }

    /**
     * Get JSON decoded response content.
     *
     * @param  bool  $assoc
     * @return mixed
     */
    public function getJson($assoc = true)
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
        try {
            $this->response = $this->client->request(
                $method, $uri, $options += $this->options
            );
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

        // Handle setting request options
        return $this->option(Str::snake($method), $args[0]);
    }
}
