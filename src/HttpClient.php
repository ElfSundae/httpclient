<?php

namespace ElfSundae;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;

class HttpClient
{
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
    protected $options = [
        'connect_timeout' => 5,
        'timeout' => 25,
    ];

    /**
     * Indicates whether throws Guzzle exceptions.
     *
     * @var bool
     */
    protected $withExceptions = false;

    /**
     * Create a http client instance.
     *
     * @param  array|string  $config  base_uri or any request options
     */
    public function __construct($config = null)
    {
        if (is_string($config)) {
            $this->options(['base_uri' => $config]);
        } elseif (is_array($config)) {
            $this->options($config);
        }

        $this->client = new Client($this->options);
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
     * Trun on/off Guzzle exceptions.
     *
     * @param  bool  $throws
     * @return $this
     */
    public function withExceptions($throws)
    {
        $this->withExceptions = (bool) $throws;

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
     * Merge request options.
     *
     * @param  array  $options
     * @return $this
     */
    public function options(array ...$options)
    {
        $this->options = array_merge_recursive($this->options, ...$options);

        return $this;
    }

    /**
     * Remove one or many options using "dot" notation.
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
     * Set a request option using "dot" notation.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function option($key, $value)
    {
        if ($key) {
            Arr::set($this->options, $key, $value);
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
     * @param  mixed  $value
     * @return $this
     */
    public function saveTo($value)
    {
        return $this->option('sink', $value);
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
        if ($body = $this->getBody()) {
            return (string) $body;
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
        if ($content = $this->getContent()) {
            return json_decode($content, $assoc);
        }
    }

    /**
     * Make request to an URL.
     *
     * @param  string  $url
     * @param  string  $method
     * @param  array  $options
     * @return $this
     */
    public function request($url, $method = 'GET', $options = [])
    {
        $options = array_merge_recursive($this->options, $options);

        try {
            $this->response = $this->client->request($method, $url, $options);
        } catch (Exception $e) {
            if ($this->withExceptions) {
                throw $e;
            }
        }

        return $this;
    }

    /**
     * Make request to an URL, expecting JSON content.
     *
     * @param  string  $url
     * @param  string  $method
     * @param  array  $options
     * @return $this
     */
    public function requestJson($url, $method = 'GET', $options = [])
    {
        Arr::set($options, 'headers.Accept', 'application/json');

        return $this->request($url, $method, $options);
    }

    /**
     * Request the URL and return the response content.
     *
     * @param  string  $url
     * @param  string  $method
     * @param  array  $options
     * @return string|null
     */
    public function fetchContent($url, $method = 'GET', $options = [])
    {
        return $this->request($url, $method, $options)->getContent();
    }

    /**
     * Request the URL and return the JSON decoded response content.
     *
     * @param  string  $url
     * @param  string  $method
     * @param  array  $options
     * @param  bool  $assoc
     * @return mixed
     */
    public function fetchJson($url, $method = 'GET', $options = [], $assoc = true)
    {
        return $this->requestJson($url, $method, $options)->getJson($assoc);
    }

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
