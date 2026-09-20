<?php

namespace Lace\Ainstruct\Console;

use Composer\InstalledVersions;
use Lace\Ainstruct\Enums\TemplateOrigin;
use Lace\Ainstruct\Values\Template;

use function Laravel\Prompts\confirm;

/**
 * Style — presentasi CLI modern ala Laravel installer.
 *
 * Menulis via `echo` agar output tetap tertangkap test (ob_start) dan
 * kontrak murni (`status --json`, `template path` custom) tidak ternoda
 * ANSI. Warna dibatasi: satu accent cyan (brand lambda) + status
 * (green/red/yellow) + dim untuk teks sekunder.
 */
final class Style
{
    private const RESET = "\033[0m";

    private const BOLD = "\033[1m";

    private const DIM = "\033[2m";

    private const CYAN = "\033[36m";

    private const GREEN = "\033[32m";

    private const YELLOW = "\033[33m";

    private const RED = "\033[31m";

    private const BLUE_BG = "\033[44m";

    private const GREEN_BG = "\033[42m";

    private const YELLOW_BG = "\033[43m";

    private const RED_BG = "\033[41m";

    private const WHITE = "\033[97m";

    private const BLACK = "\033[30m";

    public static function version(): string
    {
        try {
            $version = InstalledVersions::getPrettyVersion('lace/ainstruct');
        } catch (\OutOfBoundsException) {
            return 'dev';
        }

        if ($version === null || str_starts_with($version, 'dev-')) {
            return 'dev';
        }

        return 'v'.ltrim($version, 'v');
    }

    public function line(string $text = ''): void
    {
        echo $text.PHP_EOL;
    }

    public function blank(int $count = 1): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->line();
        }
    }

    public function cyan(string $text): string
    {
        return self::CYAN.$text.self::RESET;
    }

    public function green(string $text): string
    {
        return self::GREEN.$text.self::RESET;
    }

    public function yellow(string $text): string
    {
        return self::YELLOW.$text.self::RESET;
    }

    public function red(string $text): string
    {
        return self::RED.$text.self::RESET;
    }

    public function dim(string $text): string
    {
        return self::DIM.$text.self::RESET;
    }

    public function bold(string $text): string
    {
        return self::BOLD.$text.self::RESET;
    }

    /**
     * Panel header brand (motif lambda) — dipakai command utama.
     */
    public function panel(string $title, string $subtitle = '', string $version = ''): void
    {
        $mark = 'Λ';
        $titleLine = ' '.$this->cyan($mark).' '.$this->bold($title).($version !== '' ? '  '.$this->dim($version) : '');
        $contentWidth = max(
            $this->visibleWidth($titleLine),
            $subtitle !== '' ? $this->visibleWidth(' '.$subtitle) : 0
        );

        $border = '╭'.str_repeat('─', $contentWidth + 2).'╮';
        $bottom = '╰'.str_repeat('─', $contentWidth + 2).'╯';

        $this->line($this->cyan($border));
        $this->line($this->cyan('│').$this->padRight($titleLine, $contentWidth).$this->cyan(' │'));

        if ($subtitle !== '') {
            $this->line($this->cyan('│').' '.$this->padRight($subtitle, $contentWidth - 1).$this->cyan(' │'));
        }

        $this->line($this->cyan($bottom));
    }

    /**
     * Baris section dengan label biru + garis.
     */
    public function section(string $label, string $trailing = ''): void
    {
        $base = ' '.$label;

        if ($trailing !== '') {
            $base .= '  '.$trailing;
        }

        $this->line($this->cyan($base));
    }

    /**
     * Blok status ala Laravel: `  INFO  pesan`.
     */
    public function info(string $text): void
    {
        $this->block('INFO', $text, self::BLUE_BG, self::WHITE);
    }

    public function success(string $text): void
    {
        $this->block('OK', $text, self::GREEN_BG, self::BLACK);
    }

    public function warn(string $text): void
    {
        $this->block('WARN', $text, self::YELLOW_BG, self::BLACK);
    }

    public function error(string $text): void
    {
        $this->block('ERR', $text, self::RED_BG, self::WHITE);
    }

    /**
     * Simbol status fungsional (bukan emoji dekoratif).
     */
    public function check(string $label, string $detail = ''): void
    {
        $this->line('   '.$this->green('✔').'  '.$label.($detail !== '' ? ' '.$this->dim($detail) : ''));
    }

    public function cross(string $label): void
    {
        $this->line('   '.$this->red('✖').'  '.$label);
    }

    public function notice(string $label, string $detail = ''): void
    {
        $this->line('   '.$this->yellow('!').'  '.$label.($detail !== '' ? ' '.$this->dim($detail) : ''));
    }

    public function bullet(string $text): void
    {
        $this->line('   • '.$text);
    }

    /**
     * Pasangan key - value untuk metadata.
     */
    public function keyValue(string $key, string $value, string $valueColor = 'green'): void
    {
        // Jangan bungkus ulang nilai yang sudah punya ANSI (nesting warna).
        if (str_contains($value, "\033")) {
            $this->line('   '.$this->cyan($key).': '.$value);

            return;
        }

        $formatted = match ($valueColor) {
            'red' => $this->red($value),
            'yellow' => $this->yellow($value),
            'dim' => $this->dim($value),
            default => $this->green($value),
        };

        $this->line('   '.$this->cyan($key).': '.$formatted);
    }

    /**
     * Tabel sederhana; baris terpilih di-highlight hijau + label.
     *
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    public function table(array $headers, array $rows, ?int $highlight = null, string $highlightLabel = 'terpilih'): void
    {
        $plain = $this->stripAnsiArrays($rows);

        // Bundel label ke cell pertama baris terpilih SEBELUM kalibrasi lebar.
        if ($highlight !== null && isset($rows[$highlight])) {
            $plain[$highlight][0] = $rows[$highlight][0].'  '.$highlightLabel;
            $rows[$highlight][0] = $rows[$highlight][0].'  '.$this->green($highlightLabel);
        }

        $widths = array_map('strlen', $headers);

        foreach ($plain as $row) {
            foreach ($row as $index => $cell) {
                if (isset($widths[$index])) {
                    $widths[$index] = max($widths[$index], strlen((string) $cell));
                }
            }
        }

        $separator = '   '.implode('─', array_map(fn (int $w): string => str_repeat('─', $w + 2), $widths));

        $this->line('   '.implode('', array_map(
            fn (string $header, int $w): string => $this->padCell($this->cyan(strtoupper($header)), $w),
            $headers,
            $widths
        )));
        $this->line($separator);

        foreach ($rows as $index => $row) {
            $cells = array_map(
                fn ($cell, int $w): string => $this->padCell((string) $cell, $w),
                $row,
                $widths
            );

            if ($highlight !== null && $index === $highlight) {
                $cells = array_map(fn (string $cell): string => $this->green($cell), $cells);
            }

            $this->line('   '.implode('', $cells));
        }
    }

    /**
     * Konfirmasi destruktif ala Laravel prompts. Null = non-interaktif.
     */
    public function confirm(string $question, string $action): ?bool
    {
        if (! stream_isatty(STDIN)) {
            $this->error('Terminal non-interaktif. Jalankan dengan --force untuk mengeksekusi '.$action.'.');

            return null;
        }

        return confirm($question);
    }

    /**
     * Resource type badge untuk template list.
     */
    public function templateOriginTag(Template $template): string
    {
        return $template->origin === TemplateOrigin::BUILTIN
            ? $this->cyan('built-in')
            : $this->yellow('custom');
    }

    private function block(string $label, string $text, string $bg, string $fg): void
    {
        $labelPadded = str_pad($label, $this->visibleWidth($label));

        $this->line('   '.$bg.$fg.' '.$labelPadded.' '.self::RESET.'  '.$text);
    }

    private function visibleWidth(string $text): int
    {
        return strlen((string) preg_replace('/\033\[[0-9;]*m/', '', $text));
    }

    private function padRight(string $text, int $width): string
    {
        return $text.str_repeat(' ', max(0, $width - $this->visibleWidth($text)));
    }

    /**
     * Pad kanan dengan margin +2 di kanan, ANSI-aware.
     */
    private function padCell(string $text, int $width): string
    {
        return $text.str_repeat(' ', max(0, $width + 2 - $this->visibleWidth($text)));
    }

    /**
     * @param  list<list<string>>  $rows
     * @return list<list<string>>
     */
    private function stripAnsiArrays(array $rows): array
    {
        return array_map(
            fn (array $row): array => array_map(
                fn (string $cell): string => (string) preg_replace('/\033\[[0-9;]*m/', '', $cell),
                $row
            ),
            $rows
        );
    }
}
