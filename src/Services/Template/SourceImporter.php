<?php

namespace Lace\Ainstruct\Services\Template;

use Lace\Ainstruct\Contracts\Repository\InstructionFileRepositoryContract;
use Lace\Ainstruct\Exceptions\InvalidOperationException;

/**
 * Impor template dari sumber git (URL https/ssh atau jalur git repo lokal)
 * lalu catat sumber ke `ainstruct.source` di dalam template agar
 * `template update` bisa menarik ulang tanpa mengetik ulang URL.
 */
final class SourceImporter
{
    public const SOURCE_FILE = 'ainstruct.source';

    public function __construct(private InstructionFileRepositoryContract $files) {}

    /**
     * Apakah nilai sumber berbentuk git (URL/jalur repo), bukan nama template?
     */
    public function isGitSource(string $source): bool
    {
        if ($source === '') {
            return false;
        }

        // Skema eksplisit: https://, http://, ssh://, git://, file://
        if (str_contains($source, '://')) {
            return true;
        }

        // Bentuk scp-like: git@github.com:org/repo.git
        if (str_starts_with($source, 'git@')) {
            return true;
        }

        // Jalur lokal (absolut atau relatif) — dipakai repo lokal untuk test/CI.
        if ($source[0] === '/' || str_starts_with($source, './') || str_starts_with($source, '../') || str_starts_with($source, '~/')) {
            return true;
        }

        // Bare suffix .git tanpa skema: github.com/org/repo.git
        if (str_ends_with($source, '.git')) {
            return true;
        }

        return false;
    }

    /**
     * Normalisasi jalur sumber: perluas tilde (~ dan ~/...) ke direktori home.
     * `git clone` dipanggil lewat escapeshellarg sehingga tilde tidak pernah
     * diekspansi shell; ekspansi dilakukan di sini agar sumber `~/repo` bisa
     * diimpor dan tersimpan sebagai jalur absolut di ainstruct.source.
     */
    public function expandSource(string $source): string
    {
        if ($source !== '~' && ! str_starts_with($source, '~/')) {
            return $source;
        }

        $home = getenv('HOME') ?: getenv('USERPROFILE');

        if ($home === false || $home === '') {
            return $source;
        }

        return $source === '~' ? $home : $home.substr($source, 1);
    }

    public function sourceFileFor(string $templateDir): string
    {
        return $templateDir.DIRECTORY_SEPARATOR.self::SOURCE_FILE;
    }

    /**
     * Impor sumber git ke direktori target; isi target lama diganti.
     * Sumber valid hanya bila memuat ai-instructions.md (kontrak template).
     */
    public function import(string $source, string $targetDir, ?string $ref = null): void
    {
        $source = $this->expandSource($source);
        $this->assertGitAvailable();

        $tmp = sys_get_temp_dir().'/ainstruct-import-'.bin2hex(random_bytes(6));

        try {
            $this->gitClone($source, $tmp, $ref);

            if (! $this->files->isFile($tmp.'/ai-instructions.md')) {
                throw new InvalidOperationException(
                    "Sumber bukan template ainstruct (tidak ada ai-instructions.md): {$source}"
                );
            }

            if ($this->files->exists($targetDir)) {
                $this->files->remove($targetDir);
            }

            // Buang git history bawaan clone — template konsumen adalah salinan, bukan repo kerja.
            $this->files->remove($tmp.'/.git');
            $this->files->copyDirectory($tmp, $targetDir);
        } finally {
            if ($this->files->isDirectory($tmp)) {
                $this->files->remove($tmp);
            }
        }
    }

    /**
     * Tulis metadata sumber (dibaca `template update` untuk menarik ulang).
     */
    public function writeSource(string $templateDir, string $source, ?string $ref = null): void
    {
        $lines = ['source='.$source];

        if ($ref !== null && $ref !== '') {
            $lines[] = 'ref='.$ref;
        }

        $this->files->writeFile($this->sourceFileFor($templateDir), implode("\n", $lines)."\n");
    }

    /**
     * Baca metadata sumber yang tersimpan.
     *
     * @return array{source: string, ref: ?string}|null null bila template tidak punya metadata.
     */
    public function readSource(string $templateDir): ?array
    {
        $file = $this->sourceFileFor($templateDir);

        if (! $this->files->isFile($file)) {
            return null;
        }

        $source = null;
        $ref = null;

        foreach (explode("\n", $this->files->readFile($file)) as $line) {
            if (str_starts_with($line, 'source=')) {
                $source = substr($line, strlen('source='));
            } elseif (str_starts_with($line, 'ref=')) {
                $ref = substr($line, strlen('ref='));
            }
        }

        if ($source === null || $source === '') {
            return null;
        }

        return ['source' => $source, 'ref' => $ref];
    }

    private function assertGitAvailable(): void
    {
        $output = [];
        $exit = 0;
        exec('command -v git 2>/dev/null', $output, $exit);

        if ($exit !== 0) {
            throw new InvalidOperationException(
                'git tidak ditemukan di PATH — impor template dari sumber git butuh perintah git (git clone).'
            );
        }
    }

    private function gitClone(string $source, string $target, ?string $ref): void
    {
        $args = ['clone', '--depth', '1', '--quiet'];

        if ($ref !== null && $ref !== '') {
            $args[] = '--branch';
            $args[] = $ref;
        }

        $args[] = $source;
        $args[] = $target;

        $command = 'git '.implode(' ', array_map(
            static fn (string $arg): string => escapeshellarg($arg),
            $args
        )).' 2>&1';

        $output = [];
        $exit = 0;
        exec($command, $output, $exit);

        if ($exit !== 0) {
            throw new InvalidOperationException(
                "Gagal mengimpor template dari '{$source}':\n".implode("\n", $output)
            );
        }
    }
}
