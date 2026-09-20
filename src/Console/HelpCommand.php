<?php

namespace Lace\Ainstruct\Console;

use Lace\Ainstruct\Abstractions\Commands\Command;

/**
 * `help` / tanpa argumen — daftar command tersedia beserta deskripsi.
 */
final class HelpCommand extends Command
{
    public function handle(Input $input): int
    {
        $this->header('AI Instructions Distribution CLI');
        $this->style()->section('Usage');
        $this->style()->bullet($this->style()->cyan('ainstruct <command> [options]'));
        $this->style()->bullet($this->style()->cyan('ainstruct <framework>').'   # = distribute (mis. ainstruct laravel)');
        $this->style()->blank();
        $this->style()->section('Available commands');

        foreach ($this->commands() as $command) {
            $this->commandLine($command['name'], $command['description']);
        }

        $this->style()->blank();
        $this->style()->bullet($this->style()->dim('Bantuan per-command: ainstruct <command> --help'));

        return 0;
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
            ['name' => 'reset [<framework>]', 'description' => 'Hapus master lalu distribusikan ulang dari template.'],
            ['name' => 'wipe', 'description' => 'Hapus seluruh artefak hasil distribusi (konfirmasi dulu).'],
            ['name' => 'template', 'description' => 'Kelola template: list/create/clone/update/delete/path.'],
            ['name' => 'help', 'description' => 'Tampilkan daftar command ini.'],
        ];
    }

    private function commandLine(string $name, string $description): void
    {
        $this->style()->bullet($this->style()->cyan(str_pad($name, 32)).' '.$description);
    }
}
