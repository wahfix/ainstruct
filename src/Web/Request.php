<?php

namespace Lace\Ainstruct\Web;

/**
 * Request HTTP ringan untuk WebUI. Dibangun dari superglobal hanya di
 * `fromGlobals()` (dipakai web/index.php); test membangun instance langsung.
 */
final class Request
{
    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $body
     * @param  array<string, string>  $headers  nama header lowercase => nilai
     * @param  array<string, mixed>  $server
     */
    public function __construct(
        private string $method,
        private string $path,
        private array $query,
        private array $body,
        private array $headers,
        private array $server,
    ) {}

    public static function fromGlobals(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = self::normalizePath((string) (parse_url($uri, PHP_URL_PATH) ?: '/'));

        $query = [];
        parse_str((string) ($_SERVER['QUERY_STRING'] ?? ''), $query);

        $body = [];
        $raw = (string) file_get_contents('php://input');

        if ($raw !== '') {
            $decoded = json_decode($raw, true);

            if (is_array($decoded)) {
                $body = $decoded;
            } else {
                parse_str($raw, $body);
            }
        }

        return new self($method, $path, $query, $body, self::extractHeaders($_SERVER), $_SERVER);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return $this->query;
    }

    /**
     * @return array<string, mixed>
     */
    public function body(): array
    {
        return $this->body;
    }

    public function header(string $name): ?string
    {
        $key = strtolower($name);

        return $this->headers[$key] ?? null;
    }

    public function server(string $name): ?string
    {
        $value = $this->server[$name] ?? null;

        return $value === null ? null : (string) $value;
    }

    /**
     * Normalisasi path: selalu berawalan "/" dan tanpa duplikasi slash.
     */
    public static function normalizePath(string $path): string
    {
        $path = '/'.trim($path, '/');

        return (string) preg_replace('#/{2,}#', '/', $path);
    }

    /**
     * Ekstrak header dari array server (format HTTP_*). Nama dinormalisasi
     * lowercase agar pencarian case-insensitive.
     *
     * @param  array<string, mixed>  $server
     * @return array<string, string>
     */
    public static function extractHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (! str_starts_with($key, 'HTTP_') || ! is_string($value)) {
                continue;
            }

            $name = strtolower(str_replace('_', '-', substr($key, strlen('HTTP_'))));
            $headers[$name] = $value;
        }

        return $headers;
    }
}
