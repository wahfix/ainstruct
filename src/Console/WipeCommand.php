<?php

namespace Lace\Ainstruct\Console;

use Lace\Ainstruct\Abstractions\Commands\Command;
use Lace\Ainstruct\Actions\Distribution\WipeInstructionsAction;

final class WipeCommand extends Command
{
    public function __construct(private WipeInstructionsAction $wipeInstructionsAction) {}

    public function handle(Input $input): int
    {
        $this->header('Wipe instruksi AI');

        $force = $input->hasFlag('--force');
        $targetDir = getcwd() ?: '.';

        $this->style()->notice('Akan menghapus dari: '.$targetDir);

        foreach (WipeInstructionsAction::WIPE_FILES as $file) {
            $this->style()->bullet($this->style()->dim($file));
        }

        foreach (WipeInstructionsAction::WIPE_DIRECTORIES as $directory) {
            $this->style()->bullet($this->style()->dim($directory.'/'));
        }

        $this->style()->blank();

        $confirmed = $this->confirmOrFail(
            $force,
            'Hapus instruksi AI dari direktori ini?',
            'wipe'
        );

        if ($confirmed === null) {
            return 1;
        }

        if (! $confirmed) {
            $this->style()->notice('Batal. Tidak ada yang dihapus.');

            return 1;
        }

        $result = $this->wipeInstructionsAction->handle();

        if ($result->files === []) {
            $this->style()->info('Tidak ada artefak instruksi yang ditemukan.');

            return 0;
        }

        foreach ($result->files as $label) {
            $this->style()->check('removed: '.$label);
        }

        $this->style()->blank();
        $this->style()->success('Wipe selesai. '.$result->count().' artefak dihapus.');

        return 0;
    }
}
