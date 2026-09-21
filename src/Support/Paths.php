<?php

namespace Lace\Ainstruct\Support;

/**
 * Lokasi direktori yang dipakai CLI. Mengikuti kontrak bash lama:
 * AINSTRUCT_HOME (atau XDG_CONFIG_HOME/ainstruct, lalu HOME/.config/ainstruct)
 * menjadi rumah template konsumen; target kerja = direktori saat ini.
 */
final class Paths
{
    public function __construct(
        private string $targetDir,
        private string $packageRoot,
    ) {}

    public static function fromEnvironment(): self
    {
        return new self(
            getcwd() ?: '.',
            dirname(__DIR__, 2),
        );
    }

    public function targetDir(): string
    {
        return $this->targetDir;
    }

    public function packageRoot(): string
    {
        return $this->packageRoot;
    }

    public function builtinTemplatesDir(): string
    {
        return $this->packageRoot.DIRECTORY_SEPARATOR.'templates';
    }

    public function ainstructHomeDir(): string
    {
        $env = getenv('AINSTRUCT_HOME');

        if (is_string($env) && $env !== '') {
            return $env;
        }

        $xdg = getenv('XDG_CONFIG_HOME');

        if (is_string($xdg) && $xdg !== '') {
            return $xdg.DIRECTORY_SEPARATOR.'ainstruct';
        }

        return (getenv('HOME') ?: sys_get_temp_dir()).DIRECTORY_SEPARATOR.'.config'.DIRECTORY_SEPARATOR.'ainstruct';
    }

    public function consumerTemplatesDir(): string
    {
        return $this->ainstructHomeDir().DIRECTORY_SEPARATOR.'templates';
    }

    /**
     * Direktori state WebUI untuk sesi opencode. Prioritas env mengikuti
     * pola ainstructHomeDir(): AINSTRUCT_STATE_HOME, lalu XDG_STATE_HOME,
     * lalu HOME/.local/state — semua diakhiri ainstruct/webui/opencode.
     */
    public function opencodeStateDir(): string
    {
        $base = getenv('AINSTRUCT_STATE_HOME');

        if (! is_string($base) || $base === '') {
            $base = getenv('XDG_STATE_HOME');
        }

        if (! is_string($base) || $base === '') {
            $base = (getenv('HOME') ?: sys_get_temp_dir()).DIRECTORY_SEPARATOR.'.local'.DIRECTORY_SEPARATOR.'state';
        }

        return $base.DIRECTORY_SEPARATOR.'ainstruct'.DIRECTORY_SEPARATOR.'webui'.DIRECTORY_SEPARATOR.'opencode';
    }

    public function masterDir(): string
    {
        return $this->targetDir().DIRECTORY_SEPARATOR.'ai-instructions'.DIRECTORY_SEPARATOR.'master';
    }

    public function moduleDir(): string
    {
        return $this->targetDir().DIRECTORY_SEPARATOR.'ai-instructions';
    }
}
