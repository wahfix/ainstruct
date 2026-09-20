<?php

namespace Lace\Ainstruct\Actions\Distribution;

use Lace\Ainstruct\Abstractions\Actions\Action;
use Lace\Ainstruct\Contracts\Actions\RuledActionContract;
use Lace\Ainstruct\Contracts\Repository\InstructionFileRepositoryContract;
use Lace\Ainstruct\Support\Paths;
use Lace\Ainstruct\Values\WipeResult;

final class WipeInstructionsAction extends Action implements RuledActionContract
{
    /** File artefak yang dihapus wipe (kontrak bash). */
    public const WIPE_FILES = [
        'AGENTS.md',
        'CLAUDE.md',
        'GEMINI.md',
        '.github/copilot-instructions.md',
        '.cursorrules',
        '.windsurfrules',
        '.continuerules',
        '.aider.conf.yml',
        'opencode.json',
    ];

    /** Direktori artefak yang dihapus wipe (kontrak bash). */
    public const WIPE_DIRECTORIES = [
        '.cursor',
        '.clinerules',
        '.opencode',
        'ai-instructions',
    ];

    /** Direktori induk yang dibersihkan bila kosong setelah wipe. */
    private const PARENT_DIRECTORIES = [
        '.github',
        '.clinerules',
        '.cursor',
        '.opencode',
    ];

    public function __construct(
        private InstructionFileRepositoryContract $files,
        private Paths $paths,
    ) {}

    public function rules(): array
    {
        return [
            'force' => ['nullable', 'boolean'],
            'target_dir' => ['required', 'string'],
        ];
    }

    protected function handler(array $payload): WipeResult
    {
        $targetDir = $this->paths->targetDir();
        $deleted = [];

        foreach (self::WIPE_FILES as $relative) {
            $path = $targetDir.DIRECTORY_SEPARATOR.$relative;

            if ($this->files->exists($path)) {
                $this->files->remove($path);
                $deleted[] = $relative;
            }
        }

        foreach (self::WIPE_DIRECTORIES as $relative) {
            $path = $targetDir.DIRECTORY_SEPARATOR.$relative;

            if ($this->files->isDirectory($path)) {
                $this->files->remove($path);
                $deleted[] = $relative.'/';
            }
        }

        foreach (self::PARENT_DIRECTORIES as $relative) {
            $this->files->rmdirIfEmpty($targetDir.DIRECTORY_SEPARATOR.$relative);
        }

        return new WipeResult($deleted);
    }
}
