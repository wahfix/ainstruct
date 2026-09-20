<?php

namespace Lace\Ainstruct\Console;

use Lace\Ainstruct\Abstractions\Commands\Command;
use Lace\Ainstruct\Actions\Distribution\WipeInstructionsAction;
use Lace\Ainstruct\Exceptions\ValidationException;

final class WipeCommand extends Command
{
    public function __construct(private WipeInstructionsAction $wipeInstructionsAction) {}

    public function handle(Input $input): int
    {
        $this->header();

        $force = $input->hasFlag('--force');
        $targetDir = getcwd() ?: '.';

        $this->line('🧹 Wipe instruksi AI dari: '.$targetDir);

        foreach (WipeInstructionsAction::WIPE_FILES as $file) {
            $this->line('  - '.$file);
        }

        foreach (WipeInstructionsAction::WIPE_DIRECTORIES as $directory) {
            $this->line('  - '.$directory.'/');
        }

        $this->line();

        $confirmed = $this->confirmOrFail(
            $force,
            'Hapus instruksi AI dari direktori ini? [y/N]',
            'wipe'
        );

        if ($confirmed === null) {
            return 1;
        }

        if (! $confirmed) {
            $this->line('Batal. Tidak ada yang dihapus.');

            return 1;
        }

        try {
            $result = $this->wipeInstructionsAction->handle([
                'force' => true,
                'target_dir' => $targetDir,
            ]);
        } catch (ValidationException $e) {
            $this->line($this->red('❌ '.$e->getMessage()));

            return 1;
        }

        if ($result->files === []) {
            $this->line($this->yellow('ℹ️  Tidak ada artefak instruksi yang ditemukan.'));

            return 0;
        }

        foreach ($result->files as $label) {
            $this->line('  '.$this->green('✓ removed: '.$label));
        }

        $this->line();
        $this->line($this->green('✅ Wipe selesai. '.$result->count().' artefak dihapus.'));

        return 0;
    }
}
