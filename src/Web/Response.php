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
        private readonly ?\Closure $streamCallback = null,
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

    /**
     * Respons streaming (SSE). Callback dijalankan saat send(); di dalamnya
     * boleh menulis output bertahap (echo + flush).
     *
     * @param  \Closure(): void  $callback
     * @param  array<string, string>  $headers
     */
    public static function stream(\Closure $callback, int $status = 200, array $headers = []): self
    {
        return new self($status, array_merge([
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ], $headers), '', $callback);
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header($name.': '.$value);
        }

        if ($this->streamCallback !== null) {
            // Matikan output buffering agar chunk terkirim seketika.
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            try {
                ($this->streamCallback)();
            } catch (\Throwable $e) {
                echo "event: error\ndata: ".json_encode(['error' => $e->getMessage()])."\n\n";
                flush();
            }

            return;
        }

        echo $this->body;
    }
}
