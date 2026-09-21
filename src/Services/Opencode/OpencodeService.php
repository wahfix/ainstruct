<?php

namespace Lace\Ainstruct\Services\Opencode;

use Lace\Ainstruct\Exceptions\InvalidOperationException;
use Lace\Ainstruct\Exceptions\SessionNotFoundException;
use Lace\Ainstruct\Exceptions\ValidationException;
use Lace\Ainstruct\Support\Paths;

/**
 * Menjalankan sesi opencode untuk WebUI.
 *
 * Proses di-spawn TERDETACH: perintah shell menyalurkan stdout+stderr ke
 * output.log lalu mengembalikan kontrol ke server seketika (server `php -S`
 * single-thread: request panjang akan memblokir seluruh UI). Status dibaca
 * via polling: sesi dianggap "running" selama PID-nya hidup, lalu berubah
 * menjadi "finished" saat proses benar-benar hilang (exit code tidak
 * ditangkap — jujur melaporkan null bila tidak tersedia).
 *
 * Hanya memanggil `opencode run` dengan argumen yang di-escape penuh; tidak
 * ada lintasan perintah shell dari input pengguna. Windows belum didukung
 * untuk menjalankan sesi (spawn POSIX via `&` + `$!`).
 */
final class OpencodeService
{
    /**
     * Ukuran output yang dikembalikan ke frontend (tail terakhir).
     */
    private const MAX_OUTPUT_BYTES = 200_000;

    private const MAX_PROMPT_LENGTH = 10_000;

    private const MAX_OPTION_LENGTH = 200;

    public function __construct(private Paths $paths) {}

    /**
     * Ketersediaan binary opencode + direktori kerja default WebUI.
     *
     * @return array{
     *     available: bool,
     *     version: string|null,
     *     bin: string,
     *     defaultDirectory: string,
     *     platform: string,
     *     windowsSupported: bool,
     * }
     */
    public function status(): array
    {
        $bin = $this->resolveBin();
        $version = null;
        $available = false;

        if ($bin !== null) {
            $output = [];
            $code = 1;

            @exec($this->escape($bin).' --version 2>&1', $output, $code);

            if ($code === 0) {
                $available = true;
                $version = trim((string) ($output[0] ?? ''));
            }
        }

        return [
            'available' => $available,
            'version' => $version === '' ? null : $version,
            'bin' => $bin ?? $this->defaultBinName(),
            'defaultDirectory' => $this->defaultDirectory(),
            'platform' => PHP_OS_FAMILY,
            'windowsSupported' => PHP_OS_FAMILY !== 'Windows',
        ];
    }

    /**
     * Mulai sesi `opencode run` di direktori proyek.
     *
     * @param  array<string, mixed>  $input  directory, prompt, model?, agent?, auto?, session?
     * @return array<string, mixed> meta sesi (id, directory, prompt, opsi, status)
     */
    public function start(array $input): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            throw new InvalidOperationException(
                'Menjalankan sesi opencode dari WebUI belum didukung di Windows (spawn POSIX). Gunakan terminal terbuka di direktori proyek.'
            );
        }

        $directory = $this->validateDirectory($input['directory'] ?? null);
        $prompt = $this->validatePrompt($input['prompt'] ?? null);

        $bin = $this->resolveOrFail();

        $model = $this->optionalText($input['model'] ?? null, 'model');
        $agent = $this->optionalText($input['agent'] ?? null, 'agent');
        $session = $this->optionalSession($input['session'] ?? null);
        $auto = (bool) ($input['auto'] ?? false);

        $id = 'webui-'.bin2hex(random_bytes(8));
        $sessionDir = $this->sessionDir($id);
        mkdir($sessionDir, 0777, true);

        $meta = [
            'id' => $id,
            'directory' => $directory,
            'prompt' => $prompt,
            'model' => $model,
            'agent' => $agent,
            'auto' => $auto,
            'session' => $session,
            'status' => 'running',
            'startedAt' => $this->now(),
            'finishedAt' => null,
            'exitCode' => null,
        ];

        $this->writeMeta($id, $meta);

        $this->spawn($bin, $directory, $prompt, $model, $agent, $session, $auto, $sessionDir);

        return $meta;
    }

    /**
     * Daftar sesi yang pernah dijalankan dari WebUI (terbaru dulu).
     *
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        $dir = $this->paths->opencodeStateDir();

        if (! is_dir($dir)) {
            return [];
        }

        $sessions = [];

        foreach (glob($dir.DIRECTORY_SEPARATOR.'*') ?: [] as $entry) {
            if (! is_dir($entry)) {
                continue;
            }

            $meta = $this->readMeta((string) basename($entry));

            if ($meta !== null) {
                $sessions[] = $this->hydrate($entry, $meta);
            }
        }

        usort($sessions, static fn (array $a, array $b): int => strcmp((string) $b['startedAt'], (string) $a['startedAt']));

        return $sessions;
    }

    /**
     * Detail satu sesi: meta + status terkini + isi output (dibatasi).
     *
     * @return array<string, mixed>
     */
    public function session(string $id): array
    {
        $meta = $this->requireSession($id);

        return [
            'meta' => $this->hydrate($this->sessionDir($id), $meta),
            'output' => $this->output($id),
        ];
    }

    /**
     * Isi log output sesi, dibatasi ke tail terakhir.
     *
     * @return array{id: string, truncated: bool, content: string}
     */
    public function output(string $id): array
    {
        $file = $this->sessionDir($id).DIRECTORY_SEPARATOR.'output.log';

        if (! is_file($file)) {
            return ['id' => $id, 'truncated' => false, 'content' => ''];
        }

        $size = (int) filesize($file);
        $truncated = $size > self::MAX_OUTPUT_BYTES;

        $handle = @fopen($file, 'rb');

        if ($handle === false) {
            return ['id' => $id, 'truncated' => $truncated, 'content' => ''];
        }

        if ($truncated) {
            fseek($handle, -self::MAX_OUTPUT_BYTES, SEEK_END);
        }

        $content = (string) stream_get_contents($handle);
        fclose($handle);

        if ($truncated) {
            $marker = "\n… (output dipangkas ke ".self::MAX_OUTPUT_BYTES." byte terakhir) …\n";

            return ['id' => $id, 'truncated' => true, 'content' => $marker.$content];
        }

        return ['id' => $id, 'truncated' => false, 'content' => $content];
    }

    /**
     * Hentikan sesi yang sedang berjalan (idempoten).
     */
    public function stop(string $id): void
    {
        $meta = $this->requireSession($id);

        if (($meta['status'] ?? '') !== 'running') {
            return;
        }

        $pid = $this->readPid($id);

        if ($pid !== null) {
            @exec('kill '.((string) $pid).' 2>/dev/null');
        }

        @unlink($this->sessionDir($id).DIRECTORY_SEPARATOR.'pid');

        $meta['status'] = 'stopped';
        $meta['finishedAt'] = $this->now();
        $this->writeMeta($id, $meta);
    }

    /**
     * Hapus catatan sesi beserta file lognya. Controller wajib meminta
     * konfirmasi force sebelum memanggil metode ini.
     */
    public function delete(string $id): void
    {
        $this->requireSession($id);

        $pid = $this->readPid($id);

        if ($pid !== null) {
            @exec('kill '.((string) $pid).' 2>/dev/null');
        }

        $this->removeDirectory($this->sessionDir($id));
    }

    /**
     * @return array<string, mixed>
     */
    private function requireSession(string $id): array
    {
        if (! preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
            throw ValidationException::for('session', 'id sesi tidak valid.');
        }

        $meta = $this->readMeta($id);

        if ($meta === null) {
            throw new SessionNotFoundException($id);
        }

        return $meta;
    }

    /**
     * Perbarui status sesi berdasarkan PID, lalu kembalikan meta lengkap.
     *
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function hydrate(string $sessionDir, array $meta): array
    {
        if (($meta['status'] ?? '') === 'running') {
            $pid = $this->readPidFromDir($sessionDir);

            if ($pid === null || ! $this->processAlive($pid)) {
                $meta['status'] = 'finished';
                $meta['finishedAt'] = $meta['finishedAt'] ?? $this->now();
                $meta['exitCode'] = null;
                $this->writeMeta((string) $meta['id'], $meta);
            }
        }

        return $meta;
    }

    private function spawn(string $bin, string $directory, string $prompt, ?string $model, ?string $agent, ?string $session, bool $auto, string $sessionDir): void
    {
        $e = fn (string $value): string => escapeshellarg($value);

        $arguments = [$e($bin), 'run'];

        if ($model !== null) {
            $arguments[] = '--model';
            $arguments[] = $e($model);
        }

        if ($agent !== null) {
            $arguments[] = '--agent';
            $arguments[] = $e($agent);
        }

        if ($auto) {
            $arguments[] = '--auto';
        }

        if ($session !== null) {
            $arguments[] = '--session';
            $arguments[] = $e($session);
        }

        $arguments[] = $e($prompt);

        $log = $sessionDir.DIRECTORY_SEPARATOR.'output.log';
        $pidFile = $sessionDir.DIRECTORY_SEPARATOR.'pid';

        $command = '( cd '.$e($directory).' >/dev/null 2>&1 && exec '.implode(' ', $arguments)
            .' > '.$e($log).' 2>&1 ) >/dev/null 2>&1 & echo $! > '.$e($pidFile);

        @exec($command);
    }

    private function resolveOrFail(): string
    {
        $bin = $this->resolveBin();

        if ($bin === null) {
            throw new InvalidOperationException(
                'Perintah opencode tidak ditemukan. Pasang opencode (lihat https://opencode.ai) atau atur AINSTRUCT_OPENCODE_BIN ke path binary.'
            );
        }

        return $bin;
    }

    private function resolveBin(): ?string
    {
        $env = getenv('AINSTRUCT_OPENCODE_BIN');

        if (is_string($env) && trim($env) !== '') {
            return trim($env);
        }

        $output = [];
        $code = 1;

        @exec('command -v '.$this->escape($this->defaultBinName()).' 2>/dev/null', $output, $code);

        if ($code === 0 && isset($output[0]) && trim((string) $output[0]) !== '') {
            return trim((string) $output[0]);
        }

        return null;
    }

    private function defaultBinName(): string
    {
        return 'opencode';
    }

    private function defaultDirectory(): string
    {
        $cwd = getenv('AINSTRUCT_WEBUI_CWD');

        if (! is_string($cwd) || trim($cwd) === '') {
            $cwd = getcwd() ?: '.';
        }

        $real = realpath($cwd);

        return $real === false ? $cwd : $real;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readMeta(string $id): ?array
    {
        $file = $this->sessionDir($id).DIRECTORY_SEPARATOR.'meta.json';

        if (! is_file($file)) {
            return null;
        }

        $raw = (string) file_get_contents($file);
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function writeMeta(string $id, array $meta): void
    {
        $file = $this->sessionDir($id).DIRECTORY_SEPARATOR.'meta.json';

        file_put_contents(
            $file,
            (string) json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
    }

    private function readPid(string $id): ?int
    {
        return $this->readPidFromDir($this->sessionDir($id));
    }

    private function readPidFromDir(string $sessionDir): ?int
    {
        $file = $sessionDir.DIRECTORY_SEPARATOR.'pid';

        if (! is_file($file)) {
            return null;
        }

        $value = (int) trim((string) file_get_contents($file));

        return $value > 0 ? $value : null;
    }

    private function processAlive(int $pid): bool
    {
        if ($pid <= 0) {
            return false;
        }

        if (function_exists('posix_kill')) {
            return @posix_kill($pid, 0);
        }

        $output = [];
        $code = 1;

        @exec('kill -0 '.((string) $pid).' 2>/dev/null', $output, $code);

        return $code === 0;
    }

    private function validateDirectory(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));

        if ($text === '') {
            throw ValidationException::for('directory', 'wajib diisi.');
        }

        $real = realpath($text);

        if ($real === false || ! is_dir($real)) {
            throw ValidationException::for('directory', "direktori tidak ditemukan: {$text}");
        }

        return $real;
    }

    private function validatePrompt(mixed $value): string
    {
        $prompt = trim((string) ($value ?? ''));

        if ($prompt === '') {
            throw ValidationException::for('prompt', 'wajib diisi.');
        }

        if (strlen($prompt) > self::MAX_PROMPT_LENGTH) {
            throw ValidationException::for('prompt', 'terlalu panjang (maks '.self::MAX_PROMPT_LENGTH.' karakter).');
        }

        return $prompt;
    }

    private function optionalText(mixed $value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        if (strlen($text) > self::MAX_OPTION_LENGTH) {
            throw ValidationException::for($field, 'terlalu panjang (maks '.self::MAX_OPTION_LENGTH.' karakter).');
        }

        return $text;
    }

    private function optionalSession(mixed $value): ?string
    {
        $session = $this->optionalText($value, 'session');

        if ($session === null) {
            return null;
        }

        if (! preg_match('/^[A-Za-z0-9_.:-]+$/', $session)) {
            throw ValidationException::for('session', 'id sesi opencode tidak valid.');
        }

        return $session;
    }

    private function sessionDir(string $id): string
    {
        return $this->paths->opencodeStateDir().DIRECTORY_SEPARATOR.$id;
    }

    private function now(): string
    {
        return gmdate('Y-m-d\TH:i:s\Z');
    }

    private function escape(string $value): string
    {
        return escapeshellarg($value);
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        @rmdir($dir);
    }
}
