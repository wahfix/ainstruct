<?php

namespace Lace\Ainstruct\Services\Template;

use Lace\Ainstruct\Exceptions\InvalidOperationException;
use Lace\Ainstruct\Exceptions\ValidationException;
use Lace\Ainstruct\Support\Filesystem;

/**
 * Penjelajah dan editor file template untuk WebUI. Seluruh operasi dibatasi
 * di dalam direktori template: path traversal, lewat symlink, file biner, dan
 * ukuran berlebih ditolak.
 */
final class TemplateFileService
{
    private const MAX_BYTES = 2 * 1024 * 1024;

    public function __construct(private Filesystem $filesystem) {}

    /**
     * Daftar entri (dir/file) di dalam direktori atau subdirektori template.
     * Entri symlink dilewati agar eksplorasi tidak keluar dari root template.
     *
     * @return list<array{name: string, path: string, type: string, size: int}>
     */
    public function tree(string $templateDir, string $relative = ''): array
    {
        $dir = $this->resolveReadDirectory($templateDir, $relative);

        $entries = [];

        foreach (scandir($dir) ?: [] as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }

            $full = $dir.DIRECTORY_SEPARATOR.$name;

            if (is_link($full)) {
                continue;
            }

            $path = $relative === '' ? $name : $relative.'/'.$name;

            if (is_dir($full)) {
                $entries[] = ['name' => $name, 'path' => $path, 'type' => 'dir', 'size' => 0];
            } elseif (is_file($full)) {
                $entries[] = ['name' => $name, 'path' => $path, 'type' => 'file', 'size' => (int) filesize($full)];
            }
        }

        usort($entries, static function (array $a, array $b): int {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'dir' ? -1 : 1;
            }

            return strcasecmp($a['name'], $b['name']);
        });

        return $entries;
    }

    /**
     * @return array{path: string, size: int, content: string}
     */
    public function read(string $templateDir, string $relative): array
    {
        $target = $this->resolveReadFile($templateDir, $relative);

        if (filesize($target) > self::MAX_BYTES) {
            throw ValidationException::for('path', 'ukuran file melebihi 2 MB');
        }

        $content = $this->filesystem->readFile($target);

        if (str_contains($content, "\0")) {
            throw ValidationException::for('path', 'file biner tidak bisa ditampilkan sebagai teks');
        }

        return [
            'path' => $relative,
            'size' => strlen($content),
            'content' => $content,
        ];
    }

    public function write(string $templateDir, string $relative, string $content): void
    {
        if (strlen($content) > self::MAX_BYTES) {
            throw ValidationException::for('content', 'ukuran file melebihi 2 MB');
        }

        if (str_contains($content, "\0")) {
            throw ValidationException::for('content', 'konten biner tidak bisa disimpan lewat editor teks');
        }

        $target = $this->resolveWriteFile($templateDir, $relative);

        if (is_file($target)) {
            $head = $this->filesystem->readFile($target);

            if (str_contains($head, "\0")) {
                throw ValidationException::for('path', 'file biner tidak bisa ditimpa lewat editor teks');
            }
        }

        $this->filesystem->writeFile($target, $content);
    }

    private function resolveBase(string $templateDir): string
    {
        $real = realpath($templateDir);

        if ($real === false || ! is_dir($real)) {
            throw new InvalidOperationException("Direktori template tidak ditemukan: {$templateDir}");
        }

        return rtrim($real, DIRECTORY_SEPARATOR);
    }

    private function validateRelative(string $relative): void
    {
        if ($relative === '') {
            return;
        }

        if (str_starts_with($relative, '/') || str_contains($relative, '\\') || str_contains($relative, '..')) {
            throw ValidationException::for('path', 'path tidak valid di dalam template');
        }
    }

    private function resolveReadDirectory(string $templateDir, string $relative): string
    {
        $base = $this->resolveBase($templateDir);
        $this->validateRelative($relative);

        if ($relative === '') {
            return $base;
        }

        $target = $this->join($base, $relative);
        $real = realpath($target);

        if ($real === false || ! is_dir($real) || ! $this->isInside($base, $real)) {
            throw ValidationException::for('path', "direktori tidak ditemukan: {$relative}");
        }

        return $real;
    }

    private function resolveReadFile(string $templateDir, string $relative): string
    {
        $base = $this->resolveBase($templateDir);
        $this->validateRelative($relative);

        if ($relative === '') {
            throw ValidationException::for('path', 'wajib memilih file');
        }

        $target = $this->join($base, $relative);
        $real = realpath($target);

        if ($real === false || ! is_file($real) || ! $this->isInside($base, $real)) {
            throw ValidationException::for('path', "file tidak ditemukan: {$relative}");
        }

        return $real;
    }

    private function resolveWriteFile(string $templateDir, string $relative): string
    {
        $base = $this->resolveBase($templateDir);
        $this->validateRelative($relative);

        if ($relative === '') {
            throw ValidationException::for('path', 'wajib memilih file');
        }

        $target = $this->join($base, $relative);

        // Induk terdekat yang sudah ada harus nyata dan masih di dalam
        // template; realpath menembus symlink sehingga rute yang lewat
        // symlink keluar ikut ditolak. Direktori antara boleh belum ada,
        // nanti dibuat oleh Filesystem::writeFile.
        $existing = dirname($target);

        while (! is_dir($existing) && $existing !== dirname($existing)) {
            $existing = dirname($existing);
        }

        $realExisting = realpath($existing);

        if ($realExisting === false || ! $this->isInside($base, $realExisting)) {
            throw ValidationException::for('path', 'path keluar dari direktori template');
        }

        if (is_link($target)) {
            throw ValidationException::for('path', 'target adalah symlink; tidak akan ditimpa');
        }

        if (is_dir($target)) {
            throw ValidationException::for('path', 'target adalah direktori');
        }

        return $target;
    }

    private function join(string $base, string $relative): string
    {
        if ($relative === '') {
            return $base;
        }

        return $base.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    private function isInside(string $base, string $candidate): bool
    {
        return $candidate === $base || str_starts_with($candidate, $base.DIRECTORY_SEPARATOR);
    }
}
