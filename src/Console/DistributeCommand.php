<?php

declare(strict_types=1);

namespace Lace\Ainstruct\Console;

use Lace\Ainstruct\Abstractions\Commands\Command;
use Lace\Ainstruct\Actions\Distribution\DistributeInstructionsAction;
use Lace\Ainstruct\Contracts\Repository\TemplateRepositoryContract;
use Lace\Ainstruct\Enums\TemplateOrigin;
use Lace\Ainstruct\Exceptions\TemplateNotFoundException;
use Lace\Ainstruct\Exceptions\ValidationException;

final class DistributeCommand extends Command
{
    public function __construct(
        private DistributeInstructionsAction $distributeInstructionsAction,
        private TemplateRepositoryContract $templates,
    ) {}

    public function handle(Input $input): int
    {
        $this->header();

        try {
            $result = $this->distributeInstructionsAction->handle([
                'template' => $input->firstPositional(),
                'target_dir' => getcwd() ?: '.',
            ]);
        } catch (TemplateNotFoundException $e) {
            $this->line($this->red('❌ '.$e->getMessage()));
            $this->line();

            if ($e->available() !== []) {
                $this->line($this->yellow('📂 Frameworks tersedia:'));
                foreach ($e->available() as $name) {
                    $this->line('   • '.$this->green($name));
                }
                $this->line();
            }

            return 1;
        } catch (ValidationException $e) {
            $this->line($this->red('❌ '.$e->getMessage()));

            return 1;
        }

        $sourceNote = $result->templateSource === 'custom'
            ? '🧩 Template: custom konsumen (terbuka untuk diedit)'
            : '🧩 Template: built-in (terproteksi — clone untuk customisasi)';

        $this->line($this->yellow('📦 Framework/Template: '.$result->framework));
        $this->line($this->yellow($sourceNote));
        $this->line($this->yellow('📄 Sumber: '.$result->templateDir));
        $this->line($this->yellow('🎯 Target: '.getcwd() ?: '.'));
        $this->line();

        foreach ($result->sections as $section) {
            $this->line($this->blue($section['header']));

            foreach ($section['lines'] as $line) {
                $this->renderLine($line);
            }

            $this->line();
        }

        $this->line($this->green('══════════════════════════════════════════════════════════'));
        $this->line($this->green('✅ Selesai! '.$result->count().' file berhasil didistribusikan.'));
        $this->line($this->green('══════════════════════════════════════════════════════════'));
        $this->line();
        $this->line($this->yellow('📋 File yang di-generate:'));
        $this->line('   ├── ai-instructions/master/ai-instructions.md  (MASTER — edit di sini!)');
        $this->line('   ├── ai-instructions/master/ai-instructions/    (Modul master — edit di sini, opsional)');
        $this->line('   ├── AGENTS.md                                    (Claude/Anthropic + opencode)');
        $this->line('   ├── CLAUDE.md                                    (Claude)');
        $this->line('   ├── GEMINI.md                                    (Google Gemini)');
        $this->line('   ├── .github/copilot-instructions.md              (GitHub Copilot)');
        $this->line('   ├── .cursorrules                                 (Cursor - legacy)');
        $this->line('   ├── .cursor/rules/'.$result->framework.'-directives.mdc     (Cursor - modular)');
        $this->line('   ├── .windsurfrules                               (Windsurf)');
        $this->line('   ├── .clinerules/'.$result->framework.'-directives.md        (Cline)');
        $this->line('   ├── .continuerules                               (Continue.dev)');
        $this->line('   ├── ai-instructions/                             (Modul 01-11 + project-specific)');
        $this->line('   ├── .aider.conf.yml                              (Aider)');
        $this->line('   └── opencode.json                                (opencode — default AI untuk pekerjaan)');
        $this->line();
        $this->line($this->yellow('💡 Tambah instruksi custom:'));
        $this->line('   1. Edit ai-instructions/master/ai-instructions.md (dan/atau ai-instructions/master/ai-instructions/)');
        $this->line('   2. Jalankan ulang script untuk mendistribusikan ulang custom-nya:');
        $this->line('      ainstruct '.$result->framework);
        $this->line();
        $this->line($this->yellow('💡 Tip: Untuk mengganti framework, jalankan:'));
        $this->line('   ainstruct <framework>');
        $this->line();
        $this->showFrameworks();

        return 0;
    }

    private function renderLine(string $line): void
    {
        $coloredEmoji = [
            '🆕' => self::BLUE,
            '📝' => self::YELLOW,
            '⚠️' => self::YELLOW,
            'ℹ️' => self::YELLOW,
            '✅' => self::GREEN,
        ];

        foreach ($coloredEmoji as $emoji => $color) {
            if (str_starts_with($line, $emoji)) {
                $this->line('  '.$color.$emoji.self::NC.mb_substr($line, mb_strlen($emoji)));

                return;
            }
        }

        $this->line('  '.$line);
    }

    private function showFrameworks(): void
    {
        $this->line($this->yellow('📂 Frameworks tersedia:'));
        $this->line('  '.$this->blue('Built-in (terproteksi):'));

        foreach ($this->templates->all() as $template) {
            if ($template->origin === TemplateOrigin::BUILTIN) {
                $this->line('   • '.$this->green($template->name));
            }
        }

        $this->line('  '.$this->yellow('Custom (milik konsumen — dapat diubah/hapus):'));

        foreach ($this->templates->all() as $template) {
            if ($template->origin === TemplateOrigin::CUSTOM) {
                $this->line('   • '.$this->green($template->name));
            }
        }

        $this->line();
    }
}
