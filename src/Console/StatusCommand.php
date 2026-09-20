<?php

declare(strict_types=1);

namespace Lace\Ainstruct\Console;

use Lace\Ainstruct\Abstractions\Commands\Command;
use Lace\Ainstruct\Actions\Distribution\GetStatusAction;
use Lace\Ainstruct\Values\InstructionStatus;

final class StatusCommand extends Command
{
    public function __construct(private GetStatusAction $getStatusAction) {}

    public function handle(Input $input): int
    {
        if ($input->hasFlag('-h') || $input->hasFlag('--help') || in_array('help', $input->all(), true)) {
            $this->usage();

            return 0;
        }

        if ($input->hasFlag('--json')) {
            $status = $this->getStatusAction->handle(['target_dir' => getcwd() ?: '.']);

            echo json_encode($status->toJsonArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;

            return $status->issues === 0 ? 0 : 1;
        }

        $this->header();

        $status = $this->getStatusAction->handle(['target_dir' => getcwd() ?: '.']);

        $this->line($this->blue('📋 Status Instruksi AI — '.$status->targetDir));

        $this->line('  '.$this->yellow('Template aktif   :').' '.$this->formatActiveTemplate($status));

        $this->line('  '.$this->yellow('Master           :').' '.$this->formatMaster($status));

        $this->line('  '.$this->yellow('opencode CLI     :').' '.($status->opencodeInstalled ? 'terpasang' : 'tidak terpasang (opsional)'));
        $this->line();
        $this->line('  '.$this->yellow('Artefak          :').' '.$status->artifactsPresent.'/'.$status->artifactsTotal.' hadir');

        if ($status->missing !== []) {
            $this->line('  '.$this->red('❌ Hilang:'));

            foreach ($status->missing as $file) {
                $this->line('     - '.$file);
            }
        }

        if ($status->outOfSync !== []) {
            $this->line('  '.$this->yellow('⚠️  Berbeda dari master (diedit manual / belum di-redistribute):'));

            foreach ($status->outOfSync as $file) {
                $this->line('     - '.$file);
            }
        }

        if ($status->moduleOutOfSync) {
            $this->line('  '.$this->yellow('⚠️  Modul ai-instructions/ belum sinkron dengan master module.'));
        }

        $this->line();

        if ($status->issues === 0) {
            $this->line($this->green('✅ Sehat — semua artefak hadir & sinkron dengan master.'));
            $this->line();

            return 0;
        }

        $this->line($this->red('⚠️  '.$status->issues.' masalah ditemukan.'));

        if (! $status->masterExists) {
            $this->line($this->yellow('   Instruksi belum pernah didistribusikan di sini. Jalankan:'));
            $this->line('     ainstruct <framework>   # mis. ainstruct laravel');
        } else {
            $this->line($this->yellow('   Sinkronkan ulang dari master (custom dipertahankan):'));
            $this->line('     ainstruct '.($status->activeTemplate ?? '<framework>'));
        }

        $this->line();
        $this->line($this->yellow('   DILARANG mengedit file hasil distribusi langsung; edit ai-instructions/master/ lalu jalankan ulang.'));
        $this->line();

        return 1;
    }

    private function formatActiveTemplate(InstructionStatus $status): string
    {
        if ($status->activeTemplate !== null) {
            return $this->green($status->activeTemplate).' ('.$status->templateSource.')';
        }

        if ($status->masterExists) {
            return $this->yellow('master custom — tidak cocok template mana pun');
        }

        return '— (belum didistribusikan)';
    }

    private function formatMaster(InstructionStatus $status): string
    {
        if (! $status->masterExists) {
            return $this->red('TIDAK ADA');
        }

        return $status->masterCustomized
            ? 'ada · custom (edit aman, dipertahankan)'
            : 'ada · default';
    }

    private function usage(): void
    {
        $this->line($this->yellow('status — periksa kesehatan state instruksi AI di direktori saat ini (pwd).'));
        $this->line('Tidak mengubah apa pun; laporkan template aktif, artefak yang hilang atau');
        $this->line('berbeda dari master, dan langkah perbaikan.');
        $this->line();
        $this->line('Usage:');
        $this->line('  ainstruct status             Laporan untuk manusia (berwarna)');
        $this->line('  ainstruct status --json      Laporan JSON (untuk automation/CI)');
        $this->line();
        $this->line('Exit code: 0 = sehat (artefak lengkap & sinkron); 1 = ada masalah.');
        $this->line();
    }
}
