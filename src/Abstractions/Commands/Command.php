<?php

namespace Lace\Ainstruct\Abstractions\Commands;

use Lace\Ainstruct\Console\Input;
use Lace\Ainstruct\Contracts\Repository\TemplateRepositoryContract;
use Lace\Ainstruct\Enums\TemplateOrigin;
use Lace\Ainstruct\Exceptions\TemplateNotFoundException;
use Lace\Ainstruct\Values\DistributionResult;

abstract class Command
{
    protected const BLUE = "\033[0;34m";

    protected const GREEN = "\033[0;32m";

    protected const YELLOW = "\033[1;33m";

    protected const RED = "\033[0;31m";

    protected const NC = "\033[0m";

    /**
     * Mainkan satu command; kembalikan exit code.
     */
    abstract public function handle(Input $input): int;

    /**
     * Header kotak biru. Output JANGAN dicetak oleh subcommand `status --json`
     * agar JSON tetap murni (kontrak untuk automation/CI).
     */
    protected function header(): void
    {
        $this->line(self::BLUE.'╔══════════════════════════════════════════════════════════╗'.self::NC);
        $this->line(self::BLUE.'║  AI Instructions Distribution Script                    ║'.self::NC);
        $this->line(self::BLUE.'╚══════════════════════════════════════════════════════════╝'.self::NC);
        $this->line();
    }

    protected function line(string $text = ''): void
    {
        echo $text.PHP_EOL;
    }

    protected function blue(string $text): string
    {
        return self::BLUE.$text.self::NC;
    }

    protected function green(string $text): string
    {
        return self::GREEN.$text.self::NC;
    }

    protected function yellow(string $text): string
    {
        return self::YELLOW.$text.self::NC;
    }

    protected function red(string $text): string
    {
        return self::RED.$text.self::NC;
    }

    /**
     * Konfirmasi destruktif di layer command (UI): `--force` → langsung,
     * non-TTY → false + pesan, TTY → tanya [y/N]. Kembalikan null saat
     * non-interaktif agar command meneruskan exit 1.
     */
    protected function confirmOrFail(bool $force, string $question, string $action): ?bool
    {
        if ($force) {
            return true;
        }

        if (! stream_isatty(STDIN)) {
            $this->line($this->red('❌ Terminal non-interaktif — jalankan dengan --force untuk mengeksekusi '.$action.'.'));

            return null;
        }

        $this->line($question);
        $answer = strtolower(trim((string) fgets(STDIN)));

        return in_array($answer, ['y', 'yes'], true);
    }

    /**
     * Render hasil distribusi lengkap (1:1 kontrak bash) — dipakai
     * `distribute`, `reset`, dan `init`.
     */
    protected function renderDistributionResult(DistributionResult $result): void
    {
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
    }

    protected function renderLine(string $line): void
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

    protected function renderNotFound(TemplateNotFoundException $e): void
    {
        $this->line($this->red('❌ '.$e->getMessage()));
        $this->line();

        if ($e->available() !== []) {
            $this->line($this->yellow('📂 Frameworks tersedia:'));
            foreach ($e->available() as $name) {
                $this->line('   • '.$this->green($name));
            }
            $this->line();
        }
    }

    protected function showFrameworks(TemplateRepositoryContract $templates): void
    {
        $this->line($this->yellow('📂 Frameworks tersedia:'));
        $this->line('  '.$this->blue('Built-in (terproteksi):'));

        foreach ($templates->all() as $template) {
            if ($template->origin === TemplateOrigin::BUILTIN) {
                $this->line('   • '.$this->green($template->name));
            }
        }

        $this->line('  '.$this->yellow('Custom (milik konsumen — dapat diubah/hapus):'));

        foreach ($templates->all() as $template) {
            if ($template->origin === TemplateOrigin::CUSTOM) {
                $this->line('   • '.$this->green($template->name));
            }
        }

        $this->line();
    }
}
