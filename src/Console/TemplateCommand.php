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
            $this->line($this->red("❌ Subcommand template tidak dikenal: {$subcommand}"));
            $this->line();

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
            $this->line($this->red('❌ '.$e->getMessage()));

            return 1;
        }
    }

    private function listTemplates(): int
    {
        $templates = $this->getTemplatesAction->handle([]);

        $builtin = array_values(array_filter(
            $templates,
            fn (Template $template): bool => $template->origin === TemplateOrigin::BUILTIN
        ));

        $custom = array_values(array_filter(
            $templates,
            fn (Template $template): bool => $template->origin === TemplateOrigin::CUSTOM
        ));

        $this->line($this->green('📚 Template AI Instructions'));
        $this->line('  '.$this->blue('Built-in (TERPROTEKSI):'));

        if ($builtin === []) {
            $this->line('    (tidak ada)');
        }

        foreach ($builtin as $template) {
            $this->line('    • '.$this->green($template->name).'  ('.$template->directory.')');
        }

        $this->line();
        $this->line('  '.$this->yellow('Custom (milik konsumen — dapat diubah/hapus):'));

        if ($custom === []) {
            $this->line('    (Belum ada template custom. Buat dengan: template create <nama>)');
        }

        foreach ($custom as $template) {
            $this->line('    • '.$this->green($template->name).'  ('.$template->directory.')');
        }

        $this->line();

        return 0;
    }

    private function create(Input $input): int
    {
        $name = (string) ($input->argument(1) ?? '');

        if ($name === '') {
            $this->line($this->red('❌ template create butuh <nama>'));
            $this->line();

            return $this->usage();
        }

        $force = $input->hasFlag('--force');

        $template = $this->createTemplateAction->handle([
            'name' => $name,
            'force' => $force,
        ]);

        $this->line($this->green("✅ Template '{$template->name}' berhasil dibuat."));
        $this->line('   '.$this->yellow('📁 Lokasi: ').$template->directory);
        $this->line('   '.$this->yellow('📄 Edit:   ').'ai-instructions.md (konstitusi), ai-instructions/ (modul)');
        $this->line('   '.$this->yellow('▶️  Distribusikan: ').'ainstruct '.$template->name);
        $this->line();

        return 0;
    }

    private function clone(Input $input): int
    {
        $name = (string) ($input->argument(1) ?? '');
        $source = (string) ($input->argument(2) ?? '');

        if ($name === '' || $source === '') {
            $this->line($this->red('❌ template clone butuh <nama> <sumber>'));
            $this->line();

            return $this->usage();
        }

        $force = $input->hasFlag('--force');

        $template = $this->cloneTemplateAction->handle([
            'name' => $name,
            'source' => $source,
            'force' => $force,
        ]);

        $this->line($this->green("✅ Template '{$template->name}' dibuat dari {$source}."));
        $this->line('   '.$this->yellow('📁 Lokasi: ').$template->directory);
        $this->line('   '.$this->yellow('▶️  Distribusikan: ').'ainstruct '.$template->name);
        $this->line();

        return 0;
    }

    private function update(Input $input): int
    {
        $name = (string) ($input->argument(1) ?? '');
        $from = $input->flagValue('--from');
        $force = $input->hasFlag('--force');

        if ($name === '') {
            $this->line($this->red('❌ template update butuh <nama>'));
            $this->line();

            return $this->usage();
        }

        $sourceLabel = $from !== null && $from !== '' ? $from : "(built-in {$name})";

        $confirmed = $this->confirmOrFail(
            $force,
            "Ada template custom '{$name}' — akan ditimpa dari sumber '{$sourceLabel}'. Lanjut? [y/N]",
            'update'
        );

        if ($confirmed === null) {
            return 1;
        }

        if (! $confirmed) {
            $this->line('Batal. Tidak ada yang diubah.');

            return 1;
        }

        $template = $this->updateTemplateAction->handle([
            'name' => $name,
            'from' => $from,
            'force' => true,
        ]);

        $this->line($this->green("✅ Template '{$template->name}' diperbarui."));
        $this->line('   '.$this->yellow('📁 Lokasi: ').$template->directory);
        $this->line('   '.$this->yellow('▶️  Distribusikan: ').'ainstruct '.$template->name);
        $this->line();

        return 0;
    }

    private function delete(Input $input): int
    {
        $name = (string) ($input->argument(1) ?? '');
        $force = $input->hasFlag('--force');

        if ($name === '') {
            $this->line($this->red('❌ template delete butuh <nama>'));
            $this->line();

            return $this->usage();
        }

        $confirmed = $this->confirmOrFail(
            $force,
            "Hapus template custom '{$name}'? Ini PERMANEN. [y/N]",
            'delete'
        );

        if ($confirmed === null) {
            return 1;
        }

        if (! $confirmed) {
            $this->line('Batal. Template tidak dihapus.');

            return 1;
        }

        $path = $this->deleteTemplateAction->handle([
            'name' => $name,
            'force' => true,
        ]);

        $this->line($this->green("✅ Template '{$name}' dihapus. ({$path})"));
        $this->line();

        return 0;
    }

    private function path(Input $input): int
    {
        $name = (string) ($input->argument(1) ?? '');

        if ($name === '') {
            $this->line($this->red('❌ template path butuh <nama>'));
            $this->line();

            return $this->usage();
        }

        $template = $this->getTemplatePathAction->handle(['name' => $name]);

        if ($template->origin === TemplateOrigin::BUILTIN) {
            $this->line($this->yellow("Template '{$template->name}' adalah BUILT-IN (terproteksi)."));
            $this->line('  '.$this->yellow('Path: ').$template->directory);
            $this->line('  ➜ Berasal dari instalasi ainstruct — tidak untuk diedit langsung.');
            $this->line('  ➜ Customisasi: template clone <nama> '.$template->name);
            $this->line();
        } else {
            $this->line($template->directory);
        }

        return 0;
    }

    private function usage(): int
    {
        $this->line($this->yellow('📚 Template AI Instructions — manajemen template'));
        $this->line();
        $this->line('Usage:');
        $this->line('  template list                  Lihat semua template');
        $this->line('  template create <nama> [--force]   Buat template kosong baru');
        $this->line('  template clone <nama> <sumber> [--force]   Salin template sumber (built-in/custom)');
        $this->line('  template update <nama> [--from <sumber>] [--force]   Timpa custom dari sumber');
        $this->line('  template delete <nama> [--force]   Hapus template custom');
        $this->line('  template path <nama>           Tampilkan path template (custom dulu, baru built-in)');
        $this->line();

        return 0;
    }
}
