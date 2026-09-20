<?php

namespace Lace\Ainstruct\Console;

use Lace\Ainstruct\Abstractions\Commands\Command;

/**
 * `help` / tanpa argumen — daftar command, dan `help <command>` untuk
 * penjelasan fungsi tiap command beserta subcommand (template).
 */
final class HelpCommand extends Command
{
    public function handle(Input $input): int
    {
        $topic = strtolower((string) ($input->argument(0) ?? ''));

        if ($topic === '' || $topic === 'help') {
            return $this->overview();
        }

        return match ($topic) {
            'distribute' => $this->distributeDetail(),
            'init' => $this->initDetail(),
            'status' => $this->statusDetail(),
            'reset' => $this->resetDetail(),
            'wipe' => $this->wipeDetail(),
            'template' => $this->templateDetail(),
            default => $this->unknown($topic),
        };
    }

    private function overview(): int
    {
        $this->header('AI Instructions Distribution CLI');
        $this->style()->section('Usage');
        $this->style()->bullet($this->style()->cyan('ainstruct <command> [options]'));
        $this->style()->bullet($this->style()->cyan('ainstruct help <command>').'   # detail fungsi command/subcommand');
        $this->style()->blank();
        $this->style()->section('Available commands');
        $this->tableTwoColumns('Command', 'Deskripsi', $this->commands());

        $this->style()->blank();
        $this->style()->bullet($this->style()->dim('Kontrak lama: ainstruct <framework> = distribute (mis. ainstruct laravel).'));

        return 0;
    }

    private function distributeDetail(): int
    {
        $this->header('Distribute');
        $this->style()->section('Fungsi');
        $this->style()->bullet('Distribusikan set instruksi AI (template) ke proyek konsumen di direktori saat ini.');
        $this->style()->bullet('Membuat artefak: AGENTS.md, CLAUDE.md, GEMINI.md, .cursorrules, .windsurfrules,');
        $this->style()->bullet('.continuerules, .aider.conf.yml, opencode.json, .github/copilot-instructions.md,');
        $this->style()->bullet('.cursor/rules/, .clinerules/, dan ai-instructions/ (master + modul).');
        $this->style()->bullet('Ini adalah perintah utama — token pertama yang bukan command dianggap framework.');
        $this->style()->blank();
        $this->style()->section('Usage');
        $this->style()->bullet($this->style()->cyan('ainstruct distribute <framework>'));
        $this->style()->bullet($this->style()->cyan('ainstruct <framework>').'   # bentuk pendek');
        $this->style()->blank();
        $this->style()->section('Argumen');
        $this->style()->table(
            ['Argumen', 'Penjelasan'],
            [
                ['<framework>', 'Nama template; custom konsumen menang atas built-in bila nama sama.'],
            ]
        );
        $this->style()->blank();
        $this->style()->section('Contoh');
        $this->style()->bullet($this->style()->cyan('ainstruct laravel'));
        $this->style()->bullet($this->style()->cyan('ainstruct distribute laravel'));
        $this->style()->blank();

        return 0;
    }

    private function initDetail(): int
    {
        $this->header('Init');
        $this->style()->section('Fungsi');
        $this->style()->bullet('Deteksi stack teknologi proyek via sinyal (ainstruct-detect.txt) lalu distribusikan otomatis.');
        $this->style()->bullet('Tidak pernah menebak: tanpa sinyal teknologi, perintah gagal (exit 1).');
        $this->style()->blank();
        $this->style()->section('Usage');
        $this->style()->bullet($this->style()->cyan('ainstruct init [--template <nama>] [--dry-run]'));
        $this->style()->blank();
        $this->style()->section('Opsi');
        $this->style()->table(
            ['Opsi', 'Penjelasan'],
            [
                ['--template <nama>', 'Paksa template, lewati deteksi.'],
                ['--dry-run', 'Laporkan hasil deteksi tanpa mengubah apa pun.'],
                ['--force', 'Diterima untuk keseragaman CLI; init tidak meminta konfirmasi.'],
            ]
        );
        $this->style()->blank();
        $this->style()->section('Contoh');
        $this->style()->bullet($this->style()->cyan('ainstruct init --dry-run'));
        $this->style()->bullet($this->style()->cyan('ainstruct init --template laravel'));
        $this->style()->blank();

        return 0;
    }

    private function statusDetail(): int
    {
        $this->header('Status');
        $this->style()->section('Fungsi');
        $this->style()->bullet('Periksa kesehatan state instruksi AI di direktori saat ini.');
        $this->style()->bullet('Laporkan template aktif, artefak hilang, file yang berbeda dari master, dan langkah perbaikan.');
        $this->style()->bullet('Tidak mengubah apa pun.');
        $this->style()->blank();
        $this->style()->section('Usage');
        $this->style()->bullet($this->style()->cyan('ainstruct status [--json]'));
        $this->style()->blank();
        $this->style()->section('Opsi');
        $this->style()->table(
            ['Opsi', 'Penjelasan'],
            [
                ['--json', 'Laporan JSON murni (tanpa header/ANSI) untuk automation/CI.'],
            ]
        );
        $this->style()->blank();
        $this->style()->section('Exit code');
        $this->style()->table(
            ['Kode', 'Arti'],
            [
                ['0', 'Sehat: artefak lengkap & sinkron dengan master.'],
                ['1', 'Ada masalah (artefak hilang / berbeda dari master).'],
            ]
        );
        $this->style()->blank();

        return 0;
    }

    private function resetDetail(): int
    {
        $this->header('Reset');
        $this->style()->section('Fungsi');
        $this->style()->bullet('Hapus master rules (ai-instructions/master) lalu distribusikan ulang dari template.');
        $this->style()->bullet('Custom yang pernah diedit di master ikut dihapus — kembali identik template.');
        $this->style()->blank();
        $this->style()->section('Usage');
        $this->style()->bullet($this->style()->cyan('ainstruct reset <framework>'));
        $this->style()->blank();
        $this->style()->section('Catatan');
        $this->style()->bullet('Framework WAJIB diisi; bila kosong, master tetap dihapus lalu perintah gagal (exit 1).');
        $this->style()->blank();

        return 0;
    }

    private function wipeDetail(): int
    {
        $this->header('Wipe');
        $this->style()->section('Fungsi');
        $this->style()->bullet('Hapus seluruh artefak hasil distribusi dari direktori saat ini (AGENTS.md, CLAUDE.md, dll.).');
        $this->style()->bullet('Tidak menghapus template; aman untuk proyek yang ingin lepas dari instruksi AI.');
        $this->style()->blank();
        $this->style()->section('Usage');
        $this->style()->bullet($this->style()->cyan('ainstruct wipe [--force]'));
        $this->style()->blank();
        $this->style()->section('Opsi');
        $this->style()->table(
            ['Opsi', 'Penjelasan'],
            [
                ['--force', 'Hapus tanpa konfirmasi (CI/automation).'],
            ]
        );
        $this->style()->bullet('Di terminal interaktif: minta konfirmasi dulu; non-interaktif tanpa --force gagal (exit 1).');
        $this->style()->blank();

        return 0;
    }

    private function templateDetail(): int
    {
        $this->header('Template');
        $this->style()->section('Fungsi');
        $this->style()->bullet('Kelola template AI Instructions: built-in (terproteksi) dan custom milik konsumen.');
        $this->style()->bullet('Built-in tidak bisa dihapus/diperbarui langsung; customisasi wajib lewat clone.');
        $this->style()->blank();
        $this->style()->section('Subcommands');
        $this->tableTwoColumns('Subcommand', 'Deskripsi', $this->templateSubcommands());

        $this->style()->blank();
        $this->style()->section('Contoh');
        $this->style()->bullet($this->style()->cyan('ainstruct template clone mylaravel laravel'));
        $this->style()->bullet($this->style()->cyan('ainstruct template create blank --force'));
        $this->style()->blank();

        return 0;
    }

    private function unknown(string $topic): int
    {
        $this->style()->error("Perintah tidak dikenal: {$topic}");
        $this->style()->bullet($this->style()->cyan('ainstruct help').'   untuk daftar command.');
        $this->style()->blank();

        return 1;
    }

    /**
     * @return list<array{name: string, description: string}>
     */
    private function commands(): array
    {
        return [
            ['name' => 'distribute <framework>', 'description' => 'Distribusikan set instruksi AI ke proyek saat ini.'],
            ['name' => 'init', 'description' => 'Deteksi stack teknologi proyek & distribusikan otomatis.'],
            ['name' => 'status', 'description' => 'Periksa kesehatan instruksi AI (artefak & sinkronisasi master).'],
            ['name' => 'reset <framework>', 'description' => 'Hapus master lalu distribusikan ulang dari template.'],
            ['name' => 'wipe', 'description' => 'Hapus seluruh artefak hasil distribusi (konfirmasi dulu).'],
            ['name' => 'template', 'description' => 'Kelola template: list/create/clone/update/delete/path.'],
            ['name' => 'help', 'description' => 'Tampilkan bantuan; gunakan `help <command>` untuk detail.'],
        ];
    }

    /**
     * @return list<array{name: string, description: string}>
     */
    private function templateSubcommands(): array
    {
        return [
            ['name' => 'list', 'description' => 'Lihat semua template (built-in + custom).'],
            ['name' => 'create <nama> [--force]', 'description' => 'Buat template kosong baru.'],
            ['name' => 'clone <nama> <sumber> [--force]', 'description' => 'Salin template sumber (built-in/custom).'],
            ['name' => 'update <nama> [--from <sumber>] [--force]', 'description' => 'Timpa custom dari sumber.'],
            ['name' => 'delete <nama> [--force]', 'description' => 'Hapus template custom (built-in terproteksi).'],
            ['name' => 'path <nama>', 'description' => 'Tampilkan path template (custom dulu, baru built-in).'],
        ];
    }

    /**
     * @param  list<array{name: string, description: string}>  $rows
     */
    private function tableTwoColumns(string $leftHeader, string $rightHeader, array $rows): void
    {
        $this->style()->table(
            [$leftHeader, $rightHeader],
            array_map(fn (array $row): array => [$row['name'], $row['description']], $rows)
        );
    }
}
