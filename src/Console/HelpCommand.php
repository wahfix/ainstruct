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
            'webui' => $this->webuiDetail(),
            default => $this->unknown($topic),
        };
    }

    private function overview(): int
    {
        $this->header('AI Instructions Distribution CLI');
        $this->style()->section('Usage');
        $this->style()->bullet($this->style()->cyan('ainstruct <command> [options]'));
        $this->style()->bullet($this->style()->cyan('ainstruct help <command>').'  # detail fungsi command/subcommand');
        $this->style()->blank();
        $this->style()->section('Available commands');

        foreach ($this->commands() as $command) {
            $this->pairLine($command['name'], $command['description']);
        }

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
        $this->style()->bullet($this->style()->cyan('ainstruct <framework>').'  # bentuk pendek');
        $this->style()->blank();
        $this->style()->section('Argumen');
        $this->pairLine('<framework>', 'Nama template; custom konsumen menang atas built-in bila nama sama.');
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
        $this->pairLine('--template <nama>', 'Paksa template, lewati deteksi.');
        $this->pairLine('--dry-run', 'Laporkan hasil deteksi tanpa mengubah apa pun.');
        $this->pairLine('--force', 'Diterima untuk keseragaman CLI; init tidak meminta konfirmasi.');
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
        $this->pairLine('--json', 'Laporan JSON murni (tanpa header/ANSI) untuk automation/CI.');
        $this->style()->blank();
        $this->style()->section('Exit code');
        $this->style()->bullet($this->style()->cyan('0').'  Sehat: artefak lengkap & sinkron dengan master.');
        $this->style()->bullet($this->style()->cyan('1').'  Ada masalah (artefak hilang / berbeda dari master).');
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
        $this->pairLine('--force', 'Hapus tanpa konfirmasi (CI/automation).');
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

        foreach ($this->templateSubcommands() as $command) {
            $this->pairLine($command['name'], $command['description']);
        }

        $this->style()->blank();
        $this->style()->section('Contoh');
        $this->style()->bullet($this->style()->cyan('ainstruct template clone mylaravel laravel'));
        $this->style()->bullet($this->style()->cyan('ainstruct template create blank --force'));
        $this->style()->bullet($this->style()->cyan('ainstruct template create myfw --from https://github.com/user/myfw'));
        $this->style()->bullet($this->style()->cyan('ainstruct template update myfw'));
        $this->style()->blank();

        return 0;
    }

    private function webuiDetail(): int
    {
        $this->header('WebUI');
        $this->style()->section('Fungsi');
        $this->style()->bullet('Jalankan server lokal dan antarmuka browser untuk mengelola template milik konsumen.');
        $this->style()->bullet('List/create/clone/update/delete/path plus penjelajah dan editor file template custom.');
        $this->style()->bullet('Template built-in tetap terproteksi: bisa dibaca, tidak bisa dihapus/diubah dari antarmuka.');
        $this->style()->bullet('Command ini tidak pernah menjalankan distribusi, jadi aman dari direktori mana pun.');
        $this->style()->blank();
        $this->style()->section('Usage');
        $this->style()->bullet($this->style()->cyan('ainstruct webui [--host <ip>] [--port <port>] [--no-open]'));
        $this->style()->blank();
        $this->style()->section('Opsi');
        $this->pairLine('--host <ip>', 'Alamat bind (default 127.0.0.1; jangan expose ke jaringan publik).');
        $this->pairLine('--port <port>', 'Port (default 8787; saat default sibuk, port bebas berikutnya dipakai).');
        $this->pairLine('--no-open', 'Jangan membuka browser otomatis.');
        $this->style()->blank();
        $this->style()->section('Contoh');
        $this->style()->bullet($this->style()->cyan('ainstruct webui'));
        $this->style()->bullet($this->style()->cyan('ainstruct webui --no-open --port 9000'));
        $this->style()->blank();

        return 0;
    }

    private function unknown(string $topic): int
    {
        $this->style()->error("Perintah tidak dikenal: {$topic}");
        $this->style()->bullet($this->style()->cyan('ainstruct help').'  untuk daftar command.');
        $this->style()->blank();

        return 1;
    }

    /**
     * Dua kolom rapi tanpa garis: label cyan di kiri, deskripsi di kanan.
     */
    private function pairLine(string $name, string $description): void
    {
        $this->style()->bullet($this->style()->cyan(str_pad($name, 42)).' '.$description);
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
            ['name' => 'webui', 'description' => 'Kelola template di browser (server lokal + antarmuka web).'],
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
            ['name' => 'create <nama> [--from <sumber>] [--ref <ref>] [--force]', 'description' => 'Buat scaffold kosong atau impor template dari git (URL/jalur repo).'],
            ['name' => 'clone <nama> <sumber> [--force]', 'description' => 'Salin template sumber (built-in/custom).'],
            ['name' => 'update <nama> [--from <sumber>] [--ref <ref>] [--force]', 'description' => 'Timpa custom dari sumber; tanpa --from menarik sumber tersimpan.'],
            ['name' => 'delete <nama> [--force]', 'description' => 'Hapus template custom (built-in terproteksi).'],
            ['name' => 'path <nama>', 'description' => 'Tampilkan path template (custom dulu, baru built-in).'],
        ];
    }
}
