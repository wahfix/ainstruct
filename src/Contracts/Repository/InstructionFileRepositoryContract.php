<?php

namespace Lace\Ainstruct\Contracts\Repository;

interface InstructionFileRepositoryContract
{
    public function fileExists(string $path): bool;

    public function exists(string $path): bool;

    public function isFile(string $path): bool;

    public function isDirectory(string $path): bool;

    public function filesIdentical(string $a, string $b): bool;

    public function copyFile(string $from, string $to): void;

    public function writeFile(string $path, string $content): void;

    public function readFile(string $path): string;

    /**
     * `cp -r from to` — target menjadi salinan direktori (bukan isi dalam to).
     */
    public function copyDirectory(string $from, string $to): void;

    public function remove(string $path): void;

    public function rmdirIfEmpty(string $dir): void;

    /**
     * Tulis .cursor/rules/<framework>-directives.mdc: frontmatter YAML 6 baris
     * lalu isi master (kontrak bash setup-ai-rules.sh).
     */
    public function distributeCursorMdc(string $source, string $target, string $frameworkLower): void;

    /**
     * Tulis .aider.conf.yml: header + daftar modul *.md di moduleSourceDir.
     */
    public function createAiderConfig(string $target, string $moduleSourceDir, string $frameworkName): void;

    /**
     * Tulis opencode.json dengan konten tetap (schema, instructions, default_agent).
     */
    public function createOpenCodeConfig(string $target): void;

    /**
     * Salin module dir ke target; isi target lama dihapus kecuali folder master/.
     */
    public function distributeModuleDir(string $sourceDir, string $targetDir): void;

    /**
     * Salin folder opencode/ template ke .opencode/ konsumen tanpa menghapus
     * isi yang sudah ada. Kembalikan label item yang disalin.
     *
     * @return list<string>
     */
    public function distributeOpenCodeDir(string $sourceDir, string $targetDir): array;

    /**
     * Bandingkan file .mdc dengan master setelah 6 baris frontmatter dibuang.
     */
    public function mdcMatchesMaster(string $mdcPath, string $masterFile): bool;

    public function moduleDirHasMdFiles(string $dir): bool;

    public function directoriesDifferIgnoringLeftOnlyMaster(string $left, string $right): bool;

    public function isOpenCodeCliInstalled(): bool;
}
