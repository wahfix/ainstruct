<?php

namespace Lace\Ainstruct\Services\Author;

use Lace\Ainstruct\Services\Opencode\OpencodeService;
use Lace\Ainstruct\Support\Paths;

/**
 * Runner sesi authoring (`ainstruct author`) untuk mode repo.
 *
 * Menyusun prompt dari playbook (ARCHITECT-GUIDE bagian 12 + strategi sumber
 * mode repo), spawn sesi agent opencode via OpencodeService (berbagi
 * infrastruktur dengan WebUI), lalu memverifikasi kerangka hasil di direktori
 * output. Service ini TIDAK pernah menebar artefak distribusi — output
 * authoring hanya ke templates/<Framework>/ atau --output yang ditentukan.
 */
final class AuthorRunnerService
{
    /**
     * Agent opencode default untuk sesi authoring.
     */
    private const DEFAULT_AGENT = 'plenger';

    public function __construct(
        private OpencodeService $opencode,
        private Paths $paths,
    ) {}

    /**
     * Susun prompt mode repo: playbook bagian 12 (alur 9 langkah), strategi
     * sumber repo contoh, target output, dan guard Klausa 1.
     */
    public function composeRepoPrompt(string $name, string $inputPath, string $outputDir): string
    {
        $playbook = $this->paths->packageRoot().'/ARCHITECT-GUIDE.md';

        return implode("\n", [
            'Anda adalah tim authoring set instruksi AI-Instructions (mode repo).',
            '',
            'Tugas: buat set instruksi untuk framework "'.$name.'" dari repository contoh.',
            '',
            '1. Baca PENUH playbook authoring: '.$playbook,
            '   Ikuti bagian 12 (alur 9 langkah), bagian 3 (Protocol Eksplorasi termasuk',
            '   Phase 7 koleksi snippet & signature kanonik), bagian 4 (analisis), dan',
            '   bagian 9 (verifikasi diri) sebelum menulis output.',
            '2. Pelajari set templates/laravel sebagai standar presisi & REFERENCE BAR',
            '   (bagian 6D): '.$this->paths->packageRoot().'/templates/laravel/',
            '3. Eksplorasi repository contoh: '.$inputPath,
            '4. Tulis hasil ke direktori output: '.$outputDir,
            '   Struktur WAJIB mengikuti bagian 6: ai-instructions.md (konstitusi) +',
            '   folder ai-instructions/ berisi modul bernomor + canonical-snippets.md',
            '   di 12-project-specific.',
            '',
            'Guard (pelanggaran = hasil gagal):',
            '- JANGAN menyebut/membahas repository contoh di hasil set instruksi (Klausa 1).',
            '- JANGAN menebar artefak distribusi (AGENTS.md/CLAUDE.md/.cursorrules/dll).',
            '- Setiap klaim arsitektur WAJIB anchor bukti nyata (path:line, snippet verbatim).',
            '- Selesaikan semua klausa wajib 1-5 dan penuhi REFERENCE BAR sebelum selesai.',
            '',
            'Laporkan ringkasan singkat: struktur yang dibuat + bagaimana setiap klaim diverifikasi.',
        ]);
    }

    /**
     * Spawn sesi authoring di direktori kerja saat ini (cwd).
     *
     * @return array<string, mixed> meta sesi dari OpencodeService
     */
    public function start(string $prompt, ?string $agent = null): array
    {
        return $this->opencode->start([
            'directory' => getcwd() ?: '.',
            'prompt' => $prompt,
            'agent' => $agent ?? self::DEFAULT_AGENT,
            'auto' => true,
        ]);
    }

    /**
     * Stream output sesi sampai selesai/timeout; delegasi ke OpencodeService.
     *
     * @param  callable(string, array<string, mixed>): void  $onChunk
     */
    public function stream(string $id, callable $onChunk): void
    {
        $this->opencode->streamOutput($id, $onChunk);
    }

    /**
     * Verifikasi kerangka set instruksi hasil authoring di direktori output.
     *
     * @return array{ok: bool, checks: list<array{label: string, passed: bool}>}
     */
    public function verify(string $outputDir): array
    {
        $modules = $outputDir.'/ai-instructions';
        $moduleFiles = is_dir($modules)
            ? array_values(array_filter(glob($modules.'/*.md') ?: [], 'is_file'))
            : [];

        $checks = [
            ['label' => 'Konstitusi ai-instructions.md', 'passed' => is_file($outputDir.'/ai-instructions.md')],
            ['label' => 'Folder modul ai-instructions/', 'passed' => is_dir($modules)],
            ['label' => 'Minimal satu modul *.md', 'passed' => $moduleFiles !== []],
        ];

        return [
            'ok' => ! in_array(false, array_column($checks, 'passed'), true),
            'checks' => $checks,
        ];
    }
}
