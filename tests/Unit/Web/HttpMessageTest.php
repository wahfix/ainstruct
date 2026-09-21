<?php

namespace Lace\Ainstruct\Tests\Unit\Web;

use Lace\Ainstruct\Tests\TestCase;
use Lace\Ainstruct\Web\Request;
use Lace\Ainstruct\Web\Response;

final class HttpMessageTest extends TestCase
{
    public function test_normalize_path_adds_leading_slash_and_removes_duplicates(): void
    {
        $this->assertSame('/api/templates', Request::normalizePath('api/templates'));
        $this->assertSame('/api/templates', Request::normalizePath('/api/templates'));
        $this->assertSame('/api/templates', Request::normalizePath('//api//templates'));
    }

    public function test_extract_headers_maps_http_prefix_to_lowercase_names(): void
    {
        $server = [
            'HTTP_ORIGIN' => 'http://127.0.0.1:8787',
            'HTTP_X_CUSTOM' => 'yes',
            'PATH_INFO' => '/api',
        ];

        $headers = Request::extractHeaders($server);

        $this->assertSame('http://127.0.0.1:8787', $headers['origin']);
        $this->assertSame('yes', $headers['x-custom']);
        $this->assertArrayNotHasKey('path-info', $headers);
    }

    public function test_request_header_lookup_is_case_insensitive(): void
    {
        $request = new Request('GET', '/api/templates', [], [], ['origin' => 'http://localhost:8787'], []);

        $this->assertSame('http://localhost:8787', $request->header('origin'));
        $this->assertSame('http://localhost:8787', $request->header('Origin'));
        $this->assertSame('http://localhost:8787', $request->header('ORIGIN'));
    }

    public function test_request_body_and_query_passthrough(): void
    {
        $request = new Request(
            'PUT',
            '/api/templates/x',
            ['page' => '2'],
            ['path' => 'ai-instructions.md', 'content' => 'teks'],
            [],
            []
        );

        $this->assertSame('2', $request->query()['page']);
        $this->assertSame('ai-instructions.md', $request->body()['path']);
        $this->assertSame('teks', $request->body()['content']);
    }

    public function test_response_json_shape(): void
    {
        $response = Response::json(200, ['ok' => true, 'data' => ['a' => 'teks']]);

        $this->assertSame(200, $response->status);
        $this->assertStringContainsString('application/json', $response->headers['Content-Type']);
        $this->assertSame('nosniff', $response->headers['X-Content-Type-Options']);

        $payload = json_decode($response->body, true);

        $this->assertTrue($payload['ok']);
        $this->assertSame('teks', $payload['data']['a']);
    }

    public function test_response_json_escapes_unicode_readably(): void
    {
        $response = Response::json(200, ['ok' => true, 'error' => 'Direktori tidak ditemukan']);

        $this->assertStringNotContainsString('\\u', $response->body);
        $this->assertStringContainsString('Direktori tidak ditemukan', $response->body);
    }
}
