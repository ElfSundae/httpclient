<?php

use ElfSundae\HttpClient;

if (! function_exists('http_client')) {
    /**
     * Create a new HTTP client instance.
     *
     * @param  array|string|\Psr\Http\Message\UriInterface  $options  base URI or any request options
     * @return \ElfSundae\HttpClient
     */
    function http_client($options = [])
    {
        return new HttpClient($options);
    }
}
