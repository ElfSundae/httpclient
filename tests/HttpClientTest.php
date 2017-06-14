<?php

namespace ElfSundae\Test;

use Mockery as M;
use ElfSundae\HttpClient;

class HttpClientTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(HttpClient::class, M::mock(HttpClient::class));
    }
}
