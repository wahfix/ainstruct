<?php

namespace Lace\Ainstruct\Support;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Operasi filesystem netral (tanpa output); dipakai repository. Perilaku
 * mengikuti utilitas `cp`/`diff`/`mkdir` pada versi bash agar kontrak 1:1.
 */
final class Filesystem
{
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    public function isFile(string $path): bool
    {
        return is_file($path);
    }

    public function isDirectory(string $path): bool
    {
        return is_dir($path);
    }

    public function makeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    public function copyFile(string $from, string $to): void
    {
        $this->makeDirectory(dirname($to));
        copy($from, $to);
    }

    public function writeFile(string $path, string $content): void
    {
        $this->makeDirectory(dirname($path));
        file_put_contents($path, $content);
    }

    public function filesIdentical(string $a, string $b): bool
    {
        if (! is_file($a) || ! is_file($b)) {
            return false;
        }

        return md5_file($a) === md5_file($b);
    }

    /**
     * `cp -r source/. target/` : salin seluruh isi source ke target.
     */
    public function copyDirectoryContents(string $from, string $to): void
    {
        $this->makeDirectory($to);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($from, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            $relative = substr($item->getPathname(), strlen(rtrim($from, DIRECTORY_SEPARATOR)) + 1);
            $targetPath = $to.DIRECTORY_SEPARATOR.$relative;

            if ($item->isDir()) {
                $this->makeDirectory($targetPath);
            } else {
                $this->makeDirectory(dirname($targetPath));
                copy($item->getPathname(), $targetPath);
            }
        }
    }

    /**
     * Hapus seluruh isi direktori kecuali entri dengan nama $keep
     * (setara `find dir -mindepth 1 -maxdepth 1 ! -name keep -exec rm -rf`).
     */
    public function clearDirectoryKeeping(string $dir, string $keep): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === $keep) {
                continue;
            }

            $this->remove($dir.DIRECTORY_SEPARATOR.$entry);
        }
    }

    public function remove(string $path): void
    {
        if (is_dir($path) && ! is_link($path)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    rmdir($item->getPathname());
                } else {
                    unlink($item->getPathname());
                }
            }

            rmdir($path);

            return;
        }

        if (is_file($path) || is_link($path)) {
            unlink($path);
        }
    }

    /**
     * setara `diff -rq left right` dengan baris `Only in ...: master` di sisi
     * kanan diabaikan (folder master/ hasil sync_master di dalam target).
     */
    public function directoriesDifferIgnoringLeftOnlyMaster(string $left, string $right): bool
    {
        if (! is_dir($left) || ! is_dir($right)) {
            return true;
        }

        $leftFiles = $this->allFilesRelative($left);
        $rightFiles = $this->allFilesRelative($right);

        foreach ($leftFiles as $relative => $path) {
            if (! isset($rightFiles[$relative])) {
                return true;
            }

            if ($this->filesIdentical($path, $rightFiles[$relative]) === false) {
                return true;
            }
        }

        foreach ($rightFiles as $relative => $path) {
            if (! isset($leftFiles[$relative]) && ! str_starts_with($relative, 'master')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cek ketersediaan binary opencode (informasi status; bukan failure).
     */
    public function isOpenCodeCliInstalled(): bool
    {
        exec('command -v opencode 2>/dev/null', $output, $exitCode);

        return $exitCode === 0;
    }

    /**
     * @return array<string, string> rel path => absolute path
     */
    private function allFilesRelative(string $dir): array
    {
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $relative = substr($item->getPathname(), strlen(rtrim($dir, DIRECTORY_SEPARATOR)) + 1);

                if ($relative === '') {
                    continue;
                }

                $files[$relative] = $item->getPathname();
            }
        }

        ksort($files);

        return $files;
    }
}
