<?php

namespace Lace\Ainstruct\Abstractions\Commands;

use Lace\Ainstruct\Console\Input;
use Lace\Ainstruct\Console\Style;
use Lace\Ainstruct\Contracts\Repository\TemplateRepositoryContract;
use Lace\Ainstruct\Enums\TemplateOrigin;
use Lace\Ainstruct\Exceptions\TemplateNotFoundException;
use Lace\Ainstruct\Values\DistributionResult;

abstract class Command
{
    protected ?Style $style = null;

    /**
     * Style (lazy). Dipanggil `$this->style()->...` — membuat instance
     * tunggal per command tanpa bergantung pada parent constructor.
     */
    protected function style(): Style
    {
        return $this->style ??= new Style;
    }

    /**
     * Mainkan satu command; kembalikan exit code.
     */
    abstract public function handle(Input $input): int;

    /**
     * Panel header brand. JANGAN dipakai subcommand `status --json`
     * agar JSON tetap murni (kontrak automation/CI).
     */
    protected function header(string $subtitle = ''): void
    {
        $this->style()->panel('ainstruct', $subtitle, Style::version());
        $this->style()->blank();
    }

    /**
     * Konfirmasi destruktif di layer command (UI): `--force` → langsung,
     * non-TTY → null + pesan, TTY → prompt Laravel. Kembalikan null agar
     * command meneruskan exit 1.
     */
    protected function confirmOrFail(bool $force, string $question, string $action): ?bool
    {
        if ($force) {
            return true;
        }

        return $this->style()->confirm($question, $action);
    }

    /**
     * Render hasil distribusi lengkap (1:1 kontrak bash) — dipakai
     * `distribute`, `reset`, dan `init`.
     */
    protected function renderDistributionResult(DistributionResult $result): void
    {
        $sourceNote = $result->templateSource === 'custom'
            ? 'custom konsumen (terbuka untuk diedit)'
            : 'built-in (terproteksi, clone untuk customisasi)';

        $this->style()->section('Distribusi');
        $this->style()->keyValue('Framework', $result->framework);
        $this->style()->keyValue('Template', $sourceNote);
        $this->style()->keyValue('Sumber', $result->templateDir);
        $this->style()->keyValue('Target', getcwd() ?: '.');
        $this->style()->blank();

        foreach ($result->sections as $section) {
            $this->style()->line($this->style()->cyan($section['header']));

            foreach ($section['lines'] as $line) {
                $this->renderLine($line);
            }

            $this->style()->blank();
        }

        $this->style()->success('Selesai! '.$result->count().' file berhasil didistribusikan.');
        $this->style()->blank();
        $this->style()->section('File yang di-generate');
        $this->style()->bullet($this->style()->dim('ai-instructions/master/ai-instructions.md').'  (MASTER, edit di sini!)');
        $this->style()->bullet($this->style()->dim('ai-instructions/master/ai-instructions/').'  (Modul master, edit di sini, opsional)');
        $this->style()->bullet($this->style()->dim('AGENTS.md').'  (Claude/Anthropic + opencode)');
        $this->style()->bullet($this->style()->dim('CLAUDE.md').'  (Claude)');
        $this->style()->bullet($this->style()->dim('GEMINI.md').'  (Google Gemini)');
        $this->style()->bullet($this->style()->dim('.github/copilot-instructions.md').'  (GitHub Copilot)');
        $this->style()->bullet($this->style()->dim('.cursorrules').'  (Cursor, legacy)');
        $this->style()->bullet($this->style()->dim('.cursor/rules/'.$result->framework.'-directives.mdc').'  (Cursor, modular)');
        $this->style()->bullet($this->style()->dim('.windsurfrules').'  (Windsurf)');
        $this->style()->bullet($this->style()->dim('.clinerules/'.$result->framework.'-directives.md').'  (Cline)');
        $this->style()->bullet($this->style()->dim('.continuerules').'  (Continue.dev)');
        $this->style()->bullet($this->style()->dim('ai-instructions/').'  (Modul 01-11 + project-specific)');
        $this->style()->bullet($this->style()->dim('.aider.conf.yml').'  (Aider)');
        $this->style()->bullet($this->style()->dim('opencode.json').'  (opencode, default AI untuk pekerjaan)');
        $this->style()->blank();
        $this->style()->section('Tambah instruksi custom');
        $this->style()->bullet('Edit '.$this->style()->dim('ai-instructions/master/ai-instructions.md').' (dan/atau '.$this->style()->dim('ai-instructions/master/ai-instructions/').')');
        $this->style()->bullet('Jalankan ulang untuk mendistribusikan custom: '.$this->style()->cyan('ainstruct '.$result->framework));
        $this->style()->blank();
        $this->style()->section('Ganti framework');
        $this->style()->bullet($this->style()->cyan('ainstruct <framework>'));
        $this->style()->blank();
    }

    protected function renderLine(string $line): void
    {
        $labels = [
            '🆕' => 'check',
            '📝' => 'notice',
            '⚠️' => 'notice',
            'ℹ️' => 'notice',
            '✅' => 'check',
        ];

        // Backward-compat: baris dengan emoji status lama tetap ditampilkan
        // sebagai simbol fungsional (tanpa emoji).
        foreach ($labels as $emoji => $method) {
            if (str_starts_with($line, $emoji)) {
                $text = mb_substr($line, mb_strlen($emoji));

                $this->style()->{$method}($text);

                return;
            }
        }

        if (str_starts_with($line, '  ')) {
            $this->style()->bullet(ltrim($line));

            return;
        }

        $this->style()->bullet($line);
    }

    protected function renderNotFound(TemplateNotFoundException $e): void
    {
        $this->style()->error($e->getMessage());
        $this->style()->blank();

        if ($e->available() !== []) {
            $this->style()->section('Framework tersedia');
            foreach ($e->available() as $name) {
                $this->style()->bullet($this->style()->green($name));
            }
            $this->style()->blank();
        }
    }

    protected function showFrameworks(TemplateRepositoryContract $templates): void
    {
        $this->style()->section('Framework tersedia');

        $builtin = array_values(array_filter(
            $templates->all(),
            fn ($template): bool => $template->origin === TemplateOrigin::BUILTIN
        ));

        $custom = array_values(array_filter(
            $templates->all(),
            fn ($template): bool => $template->origin === TemplateOrigin::CUSTOM
        ));

        $this->style()->bullet($this->style()->cyan('Built-in (terproteksi):').' '.implode(', ', array_map(
            fn ($template): string => $this->style()->green($template->name),
            $builtin
        )));

        if ($custom !== []) {
            $this->style()->bullet($this->style()->yellow('Custom (milik konsumen):').' '.implode(', ', array_map(
                fn ($template): string => $this->style()->green($template->name),
                $custom
            )));
        }

        $this->style()->blank();
    }
}
