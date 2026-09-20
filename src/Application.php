<?php

namespace Lace\Ainstruct;

use Illuminate\Container\Container;
use Lace\Ainstruct\Abstractions\Commands\Command;
use Lace\Ainstruct\Console\DistributeCommand;
use Lace\Ainstruct\Console\HelpCommand;
use Lace\Ainstruct\Console\InitCommand;
use Lace\Ainstruct\Console\Input;
use Lace\Ainstruct\Console\ResetCommand;
use Lace\Ainstruct\Console\StatusCommand;
use Lace\Ainstruct\Console\TemplateCommand;
use Lace\Ainstruct\Console\WipeCommand;

final class Application
{
    /**
     * Command token ke class. Token yang tidak dikenal dianggap sebagai
     * framework untuk subcommand default `distribute` (kontrak bash lama).
     *
     * @var array<string, class-string<Command>>
     */
    private const COMMANDS = [
        'distribute' => DistributeCommand::class,
        'status' => StatusCommand::class,
        'wipe' => WipeCommand::class,
        'reset' => ResetCommand::class,
        'template' => TemplateCommand::class,
        'init' => InitCommand::class,
        'help' => HelpCommand::class,
    ];

    public function __construct(private Container $container) {}

    /**
     * Jalankan CLI dengan argumen user (tanpa nama script).
     *
     * Tanpa argumen (atau `help`, `-h`, `--help`) → daftar command.
     *
     * @param  list<string>  $args
     */
    public function run(array $args): int
    {
        $commandName = 'distribute';
        $commandArgs = $args;

        $first = strtolower((string) ($args[0] ?? 'help'));

        if (in_array($first, ['help', '-h', '--help'], true)) {
            $first = 'help';
        }

        if (isset(self::COMMANDS[$first])) {
            $commandName = $first;
            $commandArgs = array_slice($args, 1);
        }

        $command = $this->container->make(self::COMMANDS[$commandName]);

        return $command->handle(new Input($commandArgs));
    }
}
