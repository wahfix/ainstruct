<?php

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

        $this->header('Periksa kesehatan instruksi AI');
        $this->style()->section('Status di '.(getcwd() ?: '.'));

        $status = $this->getStatusAction->handle(['target_dir' => getcwd() ?: '.']);

        $this->style()->keyValue('Template aktif', $this->formatActiveTemplate($status));
        $this->style()->keyValue('Master', $this->formatMaster($status));
        $this->style()->keyValue('opencode CLI', $status->opencodeInstalled ? 'terpasang' : 'tidak terpasang (opsional)', $status->opencodeInstalled ? 'green' : 'dim');
        $this->style()->blank();
        $this->style()->keyValue('Artefak', $status->artifactsPresent.'/'.$status->artifactsTotal.' hadir', $status->issues === 0 ? 'green' : 'yellow');

        if ($status->missing !== []) {
            $this->style()->blank();
            $this->style()->cross('Hilang:');

            foreach ($status->missing as $file) {
                $this->style()->bullet($this->style()->red($file));
            }
        }

        if ($status->outOfSync !== []) {
            $this->style()->blank();
            $this->style()->notice('Berbeda dari master (diedit manual / belum di-redistribute):');

            foreach ($status->outOfSync as $file) {
                $this->style()->bullet($file);
            }
        }

        if ($status->moduleOutOfSync) {
            $this->style()->blank();
            $this->style()->notice('Modul ai-instructions/ belum sinkron dengan master module.');
        }

        $this->style()->blank();

        if ($status->issues === 0) {
            $this->style()->success('Sehat: semua artefak hadir dan sinkron dengan master.');
            $this->style()->blank();

            return 0;
        }

        $this->style()->error($status->issues.' masalah ditemukan.');

        if (! $status->masterExists) {
            $this->style()->bullet('Instruksi belum pernah didistribusikan di sini. Jalankan:');
            $this->style()->bullet($this->style()->cyan('ainstruct <framework>').'   # mis. ainstruct laravel');
        } else {
            $this->style()->bullet('Sinkronkan ulang dari master (custom dipertahankan):');
            $this->style()->bullet($this->style()->cyan('ainstruct '.($status->activeTemplate ?? '<framework>')));
        }

        $this->style()->blank();
        $this->style()->bullet($this->style()->dim('DILARANG mengedit file hasil distribusi langsung; edit ai-instructions/master/ lalu jalankan ulang.'));
        $this->style()->blank();

        return 1;
    }

    private function formatActiveTemplate(InstructionStatus $status): string
    {
        if ($status->activeTemplate !== null) {
            return $this->style()->green($status->activeTemplate).' ('.$status->templateSource.')';
        }

        if ($status->masterExists) {
            return $this->style()->yellow('master custom: tidak cocok template mana pun');
        }

        return $this->style()->dim('(belum didistribusikan)');
    }

    private function formatMaster(InstructionStatus $status): string
    {
        if (! $status->masterExists) {
            return $this->style()->red('TIDAK ADA');
        }

        return $status->masterCustomized
            ? $this->style()->yellow('ada, custom (edit aman, dipertahankan)')
            : $this->style()->green('ada, default');
    }

    private function usage(): void
    {
        $this->header('Periksa kesehatan instruksi AI');
        $this->style()->section('Deskripsi');
        $this->style()->bullet('Periksa state instruksi AI di direktori saat ini (pwd).');
        $this->style()->bullet('Tidak mengubah apa pun; laporkan template aktif, artefak hilang,');
        $this->style()->bullet('berbeda dari master, dan langkah perbaikan.');
        $this->style()->blank();
        $this->style()->section('Usage');
        $this->style()->bullet($this->style()->cyan('ainstruct status').'             Laporan untuk manusia (berwarna)');
        $this->style()->bullet($this->style()->cyan('ainstruct status --json').'      Laporan JSON (untuk automation/CI)');
        $this->style()->blank();
        $this->style()->section('Exit code');
        $this->style()->bullet('0 = sehat (artefak lengkap & sinkron); 1 = ada masalah.');
        $this->style()->blank();
    }
}
