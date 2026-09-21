<?php

namespace Lace\Ainstruct\Web;

final class Response
{
    /**
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public readonly int $status,
        public readonly array $headers,
        public readonly string $body,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    public static function json(int $status, array $payload, array $headers = []): self
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            $json = '{"ok":false,"error":"Gagal menyusun respons JSON."}';
        }

        return new self($status, array_merge([
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], $headers), $json);
    }

    /**
     * @param  array<string, string>  $headers
     */
    public static function html(string $content, int $status = 200, array $headers = []): self
    {
        return new self($status, array_merge([
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-store',
        ], $headers), $content);
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header($name.': '.$value);
        }

        echo $this->body;
    }
}
