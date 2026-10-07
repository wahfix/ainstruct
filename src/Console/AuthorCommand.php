<?php

namespace Lace\Ainstruct\Console;

use Lace\Ainstruct\Abstractions\Commands\Command;
use Lace\Ainstruct\Exceptions\InvalidOperationException;
use Lace\Ainstruct\Services\Author\AuthorRunnerService;

/**
 * Authoring set instruksi via sesi agent.
 *
 * Fase 2: mode `repo` sudah berjalan — menyusun prompt (playbook + strategi
 * sumber), spawn sesi opencode via AuthorRunnerService, stream output, lalu
 * memverifikasi kerangka hasil di direktori output. Mode `docs` dan `multi`
 * hadir di fase berikutnya. Command ini TIDAK pernah menebar artefak
 * distribusi; output authoring hanya ke templates/ atau --output.
 */
final class AuthorCommand extends Command
{
    /** @var list<string> */
    private const MODES = ['repo', 'docs', 'multi'];

    public function __construct(private AuthorRunnerService $runner) {}

    public function handle(Input $input): int
    {
        $this->header('Authoring set instruksi (via sesi agent)');

        $mode = $input->flagValue('--mode') ?? 'repo';
        $name = $input->flagValue('--name');
        $inputPath = $input->flagValue('--input');
        $spec = $input->flagValue('--spec');
        $output = $input->flagValue('--output');
        $dryRun = $input->hasFlag('--dry-run');

        if (! in_array($mode, self::MODES, true)) {
            $this->style()->error('Mode tidak dikenal: '.$mode);
            $this->style()->bullet('Mode yang tersedia: '.implode(', ', self::MODES));
            $this->style()->blank();

            return 1;
        }

        if ($name === null || $name === '') {
            $this->style()->error('Framework wajib diisi: '.$this->style()->cyan('--name <framework>'));
            $this->style()->blank();

            return 1;
        }

        if ($mode === 'multi') {
            $this->style()->error('Mode `'.$mode.'` belum tersedia (hadir di fase berikutnya).');
            $this->style()->bullet('Untuk sekarang pakai: '.$this->style()->cyan('--mode repo').' atau '.$this->style()->cyan('--mode docs'));
            $this->style()->blank();

            return 1;
        }

        if ($mode === 'repo') {
            if ($inputPath === null || $inputPath === '') {
                $this->style()->error('Input wajib diisi: '.$this->style()->cyan('--input <path-repo-contoh>'));
                $this->style()->blank();

                return 1;
            }

            if (! is_dir($inputPath)) {
                $this->style()->error('Input bukan direktori yang ada: '.$inputPath);
                $this->style()->blank();

                return 1;
            }
        } else { // docs
            if ($inputPath === null || $inputPath === '') {
                $this->style()->error('Input wajib diisi untuk mode docs: '.$this->style()->cyan('--input <url-dokumen-atau-daftar-url>'));
                $this->style()->blank();

                return 1;
            }
        }

        $outputDir = $output ?? (getcwd() ?: '.').'/templates/'.$name;
        if ($mode === 'repo') {
            $prompt = $this->runner->composeRepoPrompt($name, $inputPath, $outputDir);
        } else {
            $prompt = $this->runner->composeDocsPrompt($name, $inputPath, $outputDir, $spec);
        }

        if ($dryRun) {
            $this->renderPlan($mode, $name, $inputPath, $spec, $outputDir);
            $this->renderPrompt($prompt);

            return 0;
        }

        try {
            $meta = $this->runner->start($prompt);
        } catch (InvalidOperationException $e) {
            $this->style()->error($e->getMessage());
            $this->style()->blank();

            return 1;
        }

        $this->style()->section('Sesi agent dimulai');
        $this->style()->keyValue('ID', (string) $meta['id']);
        $this->style()->keyValue('Direktori kerja', (string) $meta['directory']);
        $this->style()->keyValue('Output', $outputDir);
        $this->style()->blank();

        $timedOut = false;

        $this->runner->stream((string) $meta['id'], function (string $event, array $data) use (&$timedOut): void {
            if ($event === 'output') {
                $content = rtrim((string) ($data['content'] ?? ''), "\n");

                if ($content !== '') {
                    $this->style()->dim($content);
                }
            } elseif ($event === 'done') {
                $this->style()->success('Sesi selesai.');
            } elseif ($event === 'timeout') {
                $timedOut = true;
                $this->style()->warn((string) ($data['message'] ?? 'Sesi timeout.'));
            }
        });
        $this->style()->blank();

        if ($timedOut) {
            $this->style()->bullet('Sesi masih berjalan. Pantau via '.$this->style()->cyan('ainstruct webui').',');
            $this->style()->bullet('lalu jalankan ulang author saat sesi selesai.');
            $this->style()->blank();

            return 1;
        }

        $this->style()->section('Verifikasi kerangka');
        $verification = $this->runner->verify($outputDir);

        foreach ($verification['checks'] as $check) {
            if ($check['passed']) {
                $this->style()->check($check['label']);
            } else {
                $this->style()->cross($check['label']);
            }
        }

        $this->style()->blank();

        if (! $verification['ok']) {
            $this->style()->error('Kerangka set instruksi belum lengkap — periksa output sesi lalu ulangi.');
            $this->style()->blank();

            return 1;
        }

        $this->style()->success('Set instruksi siap: '.$outputDir);
        $this->style()->blank();

        return 0;
    }

    private function renderPlan(string $mode, string $name, string $inputPath, ?string $spec, string $outputDir): void
    {
        $this->style()->notice('Dry-run: tidak ada sesi agent dijalankan, tidak ada file ditulis.');
        $this->style()->blank();
        $this->style()->section('Rencana authoring');
        $this->style()->keyValue('Mode', $mode);
        $this->style()->keyValue('Nama template', $name);
        $this->style()->keyValue('Input', $inputPath);

        if ($spec !== null && $spec !== '') {
            $this->style()->keyValue('Spec', $spec.' (greenfield — berlaku untuk mode docs, Fase 4)');
        }

        $this->style()->keyValue('Output', $outputDir);
        $this->style()->blank();
        $this->style()->bullet('Guard: authoring TIDAK pernah menebar artefak distribusi (AGENTS.md/CLAUDE.md/dll).');
        $this->style()->bullet('Alur: sesi agent (opencode) membaca playbook ARCHITECT-GUIDE dengan strategi sumber mode '.$mode.'.');
        $this->style()->blank();
    }

    private function renderPrompt(string $prompt): void
    {
        $this->style()->section('Prompt sesi agent');
        $this->style()->bullet($this->style()->dim('Dry-run: prompt ditampilkan, tidak dikirim ke sesi agent.'));

        foreach (explode("\n", $prompt) as $line) {
            $this->style()->line('   '.$line);
        }

        $this->style()->blank();
    }
}
