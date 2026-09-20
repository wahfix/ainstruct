<?php

declare(strict_types=1);

namespace Lace\Ainstruct\Abstractions\Commands;

use Lace\Ainstruct\Console\Input;

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
}
