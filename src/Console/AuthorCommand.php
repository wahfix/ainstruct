<?php

namespace Lace\Ainstruct\Console;

use Lace\Ainstruct\Abstractions\Commands\Command;

/**
 * Authoring set instruksi via sesi agent.
 *
 * Fase 1: skeleton — parsing, validasi, dan dry-run. Eksekusi sesi agent
 * (spawn opencode) hadir di fase berikutnya. Command ini TIDAK pernah
 * menebar artefak distribusi; output authoring hanya ke templates/ atau
 * --output yang ditentukan.
 */
final class AuthorCommand extends Command
{
    /** @var list<string> */
    private const MODES = ['repo', 'docs', 'multi'];

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

        if ($mode !== 'repo') {
            $this->style()->error('Mode `'.$mode.'` belum tersedia (hadir di fase berikutnya).');
            $this->style()->bullet('Untuk sekarang pakai: '.$this->style()->cyan('--mode repo'));
            $this->style()->blank();

            return 1;
        }

        if ($name === null || $name === '') {
            $this->style()->error('Framework wajib diisi: '.$this->style()->cyan('--name <framework>'));
            $this->style()->blank();

            return 1;
        }

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

        $outputDir = $output ?? (getcwd() ?: '.').'/templates/'.$name;

        if ($dryRun) {
            $this->renderPlan($mode, $name, $inputPath, $spec, $outputDir);

            return 0;
        }

        $this->style()->error('Eksekusi authoring belum tersedia (hadir di fase berikutnya — agent runner).');
        $this->style()->bullet('Jalankan dulu dengan '.$this->style()->cyan('--dry-run').' untuk melihat rencana.');
        $this->style()->blank();

        return 1;
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
}
