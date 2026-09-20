<?php

namespace Lace\Ainstruct\Repositories;

use Lace\Ainstruct\Contracts\Repository\InstructionFileRepositoryContract;
use Lace\Ainstruct\Support\Filesystem;
use Lace\Ainstruct\Support\Paths;

final class InstructionFileRepository implements InstructionFileRepositoryContract
{
    public function __construct(
        private Paths $paths,
        private Filesystem $fs,
    ) {}

    public function fileExists(string $path): bool
    {
        return $this->fs->exists($path);
    }

    public function exists(string $path): bool
    {
        return $this->fs->exists($path);
    }

    public function isFile(string $path): bool
    {
        return $this->fs->isFile($path);
    }

    public function isDirectory(string $path): bool
    {
        return $this->fs->isDirectory($path);
    }

    public function filesIdentical(string $a, string $b): bool
    {
        return $this->fs->filesIdentical($a, $b);
    }

    public function copyFile(string $from, string $to): void
    {
        $this->fs->copyFile($from, $to);
    }

    public function writeFile(string $path, string $content): void
    {
        $this->fs->writeFile($path, $content);
    }

    public function readFile(string $path): string
    {
        return (string) file_get_contents($path);
    }

    public function copyDirectory(string $from, string $to): void
    {
        $this->fs->makeDirectory(dirname($to));
        $this->fs->copyDirectoryContents($from, $to);
    }

    public function remove(string $path): void
    {
        $this->fs->remove($path);
    }

    public function rmdirIfEmpty(string $dir): void
    {
        if ($this->fs->isDirectory($dir)) {
            @rmdir($dir);
        }
    }

    public function distributeCursorMdc(string $source, string $target, string $frameworkLower): void
    {
        $content = "---\n";
        $content .= "description: \"{$frameworkLower} AI Instructions — Directives for AI Agent\"\n";
        $content .= "globs: \"**/*\"\n";
        $content .= "alwaysApply: true\n";
        $content .= "---\n\n";
        $content .= (string) file_get_contents($source);

        $this->fs->writeFile($target, $content);
    }

    public function createAiderConfig(string $target, string $moduleSourceDir, string $frameworkName): void
    {
        $content = "# {$frameworkName} — Aider Configuration\n";
        $content .= "# File ini otomatis di-generate oleh ainstruct\n\n";
        $content .= "read:\n";

        foreach (glob($moduleSourceDir.DIRECTORY_SEPARATOR.'*.md') ?: [] as $file) {
            $content .= '  - ai-instructions/'.basename($file)."\n";
        }

        $this->fs->writeFile($target, $content);
    }

    public function createOpenCodeConfig(string $target): void
    {
        $this->fs->writeFile($target, <<<'JSON'
{
  "$schema": "https://opencode.ai/config.json",
  "instructions": ["AGENTS.md"],
  "default_agent": "build"
}
JSON
        );
    }

    public function distributeModuleDir(string $sourceDir, string $targetDir): void
    {
        $this->fs->makeDirectory($targetDir);
        $this->fs->clearDirectoryKeeping($targetDir, 'master');
        $this->fs->copyDirectoryContents($sourceDir, $targetDir);
    }

    public function distributeOpenCodeDir(string $sourceDir, string $targetDir): array
    {
        if (! $this->fs->isDirectory($sourceDir)) {
            return [];
        }

        $this->fs->makeDirectory($targetDir);

        $labels = [];

        foreach (glob($sourceDir.DIRECTORY_SEPARATOR.'*') ?: [] as $item) {
            $name = basename($item);

            if ($this->fs->isDirectory($item)) {
                $this->fs->copyDirectoryContents($item, $targetDir.DIRECTORY_SEPARATOR.$name);
            } else {
                $this->fs->copyFile($item, $targetDir.DIRECTORY_SEPARATOR.$name);
            }

            $labels[] = "opencode/{$name} → .opencode/{$name}";
        }

        return $labels;
    }

    public function mdcMatchesMaster(string $mdcPath, string $masterFile): bool
    {
        if (! $this->fs->isFile($mdcPath) || ! $this->fs->isFile($masterFile)) {
            return false;
        }

        $lines = explode("\n", (string) file_get_contents($mdcPath));
        $stripped = implode("\n", array_slice($lines, 6));

        return $stripped === (string) file_get_contents($masterFile);
    }

    public function moduleDirHasMdFiles(string $dir): bool
    {
        if (! $this->fs->isDirectory($dir)) {
            return false;
        }

        foreach (glob($dir.DIRECTORY_SEPARATOR.'*.md') ?: [] as $file) {
            if ($this->fs->isFile($file)) {
                return true;
            }
        }

        return false;
    }

    public function directoriesDifferIgnoringLeftOnlyMaster(string $left, string $right): bool
    {
        return $this->fs->directoriesDifferIgnoringLeftOnlyMaster($left, $right);
    }

    public function isOpenCodeCliInstalled(): bool
    {
        return $this->fs->isOpenCodeCliInstalled();
    }
}
