<?php

namespace Lace\Ainstruct\Console;

use Lace\Ainstruct\Abstractions\Commands\Command;
use Lace\Ainstruct\Actions\Template\CloneTemplateAction;
use Lace\Ainstruct\Actions\Template\CreateTemplateAction;
use Lace\Ainstruct\Actions\Template\DeleteTemplateAction;
use Lace\Ainstruct\Actions\Template\GetTemplatePathAction;
use Lace\Ainstruct\Actions\Template\GetTemplatesAction;
use Lace\Ainstruct\Actions\Template\UpdateTemplateAction;
use Lace\Ainstruct\Enums\TemplateOrigin;
use Lace\Ainstruct\Exceptions\AinstructException;
use Lace\Ainstruct\Exceptions\TemplateNotFoundException;
use Lace\Ainstruct\Exceptions\TemplateProtectedException;
use Lace\Ainstruct\Exceptions\ValidationException;
use Lace\Ainstruct\Values\Template;

final class TemplateCommand extends Command
{
    private const SUBCOMMANDS = ['list', 'create', 'clone', 'update', 'delete', 'path', 'help'];

    public function __construct(
        private GetTemplatesAction $getTemplatesAction,
        private CreateTemplateAction $createTemplateAction,
        private CloneTemplateAction $cloneTemplateAction,
        private UpdateTemplateAction $updateTemplateAction,
        private DeleteTemplateAction $deleteTemplateAction,
        private GetTemplatePathAction $getTemplatePathAction,
    ) {}

    public function handle(Input $input): int
    {
        $subcommand = strtolower((string) ($input->argument(0) ?? 'help'));

        if (! in_array($subcommand, self::SUBCOMMANDS, true)) {
            $this->style()->error("Subcommand template tidak dikenal: {$subcommand}");
            $this->style()->blank();

            return $this->usage();
        }

        try {
            return match ($subcommand) {
                'list' => $this->listTemplates(),
                'create' => $this->create($input),
                'clone' => $this->clone($input),
                'update' => $this->update($input),
                'delete' => $this->delete($input),
                'path' => $this->path($input),
                default => $this->usage(),
            };
        } catch (TemplateNotFoundException $e) {
            $this->renderNotFound($e);

            return 1;
        } catch (TemplateProtectedException|AinstructException|ValidationException $e) {
            $this->style()->error($e->getMessage());

            return 1;
        }
    }

    private function listTemplates(): int
    {
        $templates = $this->getTemplatesAction->handle();

        $builtin = array_values(array_filter(
            $templates,
            fn (Template $template): bool => $template->origin === TemplateOrigin::BUILTIN
        ));

        $custom = array_values(array_filter(
            $templates,
            fn (Template $template): bool => $template->origin === TemplateOrigin::CUSTOM
        ));

        $this->style()->info('Template AI Instructions');

        $this->style()->section('Built-in (terproteksi)');

        if ($builtin === []) {
            $this->style()->bullet($this->style()->dim('(tidak ada)'));
        }

        foreach ($builtin as $template) {
            $this->style()->bullet($this->style()->green($template->name).'  '.$this->style()->dim('('.$template->directory.')'));
        }

        $this->style()->blank();
        $this->style()->section('Custom (milik konsumen, dapat diubah/hapus)');

        if ($custom === []) {
            $this->style()->bullet($this->style()->dim('Belum ada template custom. Buat dengan: template create <nama>'));
        }

        foreach ($custom as $template) {
            $this->style()->bullet($this->style()->green($template->name).'  '.$this->style()->dim('('.$template->directory.')'));
        }

        $this->style()->blank();

        return 0;
    }

    private function create(Input $input): int
    {
        $name = (string) ($input->argument(1) ?? '');

        if ($name === '') {
            $this->style()->error('template create butuh <nama>');
            $this->style()->blank();

            return $this->usage();
        }

        $force = $input->hasFlag('--force');

        $template = $this->createTemplateAction->handle([
            'name' => $name,
            'force' => $force,
        ]);

        $this->style()->success("Template '{$template->name}' berhasil dibuat.");
        $this->style()->keyValue('Lokasi', $template->directory);
        $this->style()->keyValue('Edit', 'ai-instructions.md (konstitusi), ai-instructions/ (modul)', 'dim');
        $this->style()->keyValue('Distribusikan', 'ainstruct '.$template->name);
        $this->style()->blank();

        return 0;
    }

    private function clone(Input $input): int
    {
        $name = (string) ($input->argument(1) ?? '');
        $source = (string) ($input->argument(2) ?? '');

        if ($name === '' || $source === '') {
            $this->style()->error('template clone butuh <nama> <sumber>');
            $this->style()->blank();

            return $this->usage();
        }

        $force = $input->hasFlag('--force');

        $template = $this->cloneTemplateAction->handle([
            'name' => $name,
            'source' => $source,
            'force' => $force,
        ]);

        $this->style()->success("Template '{$template->name}' dibuat dari {$source}.");
        $this->style()->keyValue('Lokasi', $template->directory);
        $this->style()->keyValue('Distribusikan', 'ainstruct '.$template->name);
        $this->style()->blank();

        return 0;
    }

    private function update(Input $input): int
    {
        $name = (string) ($input->argument(1) ?? '');
        $from = $input->flagValue('--from');
        $force = $input->hasFlag('--force');

        if ($name === '') {
            $this->style()->error('template update butuh <nama>');
            $this->style()->blank();

            return $this->usage();
        }

        $sourceLabel = $from !== null && $from !== '' ? $from : "(built-in {$name})";

        $confirmed = $this->confirmOrFail(
            $force,
            "Ada template custom '{$name}', akan ditimpa dari sumber '{$sourceLabel}'. Lanjut?",
            'update'
        );

        if ($confirmed === null) {
            return 1;
        }

        if (! $confirmed) {
            $this->style()->notice('Batal. Tidak ada yang diubah.');

            return 1;
        }

        $template = $this->updateTemplateAction->handle([
            'name' => $name,
            'from' => $from,
            'force' => true,
        ]);

        $this->style()->success("Template '{$template->name}' diperbarui.");
        $this->style()->keyValue('Lokasi', $template->directory);
        $this->style()->keyValue('Distribusikan', 'ainstruct '.$template->name);
        $this->style()->blank();

        return 0;
    }

    private function delete(Input $input): int
    {
        $name = (string) ($input->argument(1) ?? '');
        $force = $input->hasFlag('--force');

        if ($name === '') {
            $this->style()->error('template delete butuh <nama>');
            $this->style()->blank();

            return $this->usage();
        }

        $confirmed = $this->confirmOrFail(
            $force,
            "Hapus template custom '{$name}'? Ini PERMANEN.",
            'delete'
        );

        if ($confirmed === null) {
            return 1;
        }

        if (! $confirmed) {
            $this->style()->notice('Batal. Template tidak dihapus.');

            return 1;
        }

        $path = $this->deleteTemplateAction->handle([
            'name' => $name,
            'force' => true,
        ]);

        $this->style()->success("Template '{$name}' dihapus. ({$path})");
        $this->style()->blank();

        return 0;
    }

    private function path(Input $input): int
    {
        $name = (string) ($input->argument(1) ?? '');

        if ($name === '') {
            $this->style()->error('template path butuh <nama>');
            $this->style()->blank();

            return $this->usage();
        }

        $template = $this->getTemplatePathAction->handle(['name' => $name]);

        if ($template->origin === TemplateOrigin::BUILTIN) {
            $this->style()->notice("Template '{$template->name}' adalah BUILT-IN (terproteksi).");
            $this->style()->keyValue('Path', $template->directory);
            $this->style()->bullet($this->style()->dim('Berasal dari instalasi ainstruct, tidak untuk diedit langsung.'));
            $this->style()->bullet($this->style()->dim('Customisasi: template clone <nama> '.$template->name));
            $this->style()->blank();
        } else {
            // Kontrak murni untuk scripting: path custom dicetak apa adanya.
            echo $template->directory.PHP_EOL;
        }

        return 0;
    }

    private function usage(): int
    {
        $this->style()->info('Template AI Instructions, manajemen template');
        $this->style()->section('Usage');
        $this->style()->bullet($this->style()->cyan('template list').'                  Lihat semua template');
        $this->style()->bullet($this->style()->cyan('template create <nama>').' [--force]   Buat template kosong baru');
        $this->style()->bullet($this->style()->cyan('template clone <nama> <sumber>').' [--force]   Salin template sumber (built-in/custom)');
        $this->style()->bullet($this->style()->cyan('template update <nama>').' [--from <sumber>] [--force]   Timpa custom dari sumber');
        $this->style()->bullet($this->style()->cyan('template delete <nama>').' [--force]   Hapus template custom');
        $this->style()->bullet($this->style()->cyan('template path <nama>').'           Tampilkan path template (custom dulu, baru built-in)');
        $this->style()->blank();

        return 0;
    }
}
