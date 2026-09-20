<?php

namespace Lace\Ainstruct\Console;

final class Input
{
    /**
     * @param  list<string>  $args
     */
    public function __construct(private array $args) {}

    /**
     * @param  list<string>  $argv
     */
    public static function fromArgv(array $argv): self
    {
        return new self(array_slice($argv, 1));
    }

    /**
     * Argumen posisional ke-N (tanpa flag) atau null bila tidak ada.
     */
    public function argument(int $index): ?string
    {
        $positionals = array_values(array_filter(
            $this->args,
            fn (string $arg): bool => $arg === '' || ! str_starts_with($arg, '-')
        ));

        return $positionals[$index] ?? null;
    }

    public function firstPositional(): ?string
    {
        return $this->argument(0);
    }

    public function hasFlag(string $flag): bool
    {
        return in_array($flag, $this->args, true);
    }

    /**
     * Nilai flag dalam bentuk `--flag value` atau `--flag=value`.
     */
    public function flagValue(string $flag): ?string
    {
        foreach ($this->args as $index => $arg) {
            if ($arg === $flag && isset($this->args[$index + 1])) {
                return $this->args[$index + 1];
            }

            if (str_starts_with($arg, $flag.'=')) {
                return substr($arg, strlen($flag) + 1);
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return $this->args;
    }
}
