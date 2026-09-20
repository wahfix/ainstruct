<?php

namespace Lace\Ainstruct\Actions\Distribution;

use Lace\Ainstruct\Abstractions\Actions\Action;
use Lace\Ainstruct\Contracts\Actions\RuledActionContract;
use Lace\Ainstruct\Contracts\Repository\InstructionFileRepositoryContract;
use Lace\Ainstruct\Support\Paths;

final class ResetMasterAction extends Action implements RuledActionContract
{
    public function __construct(
        private InstructionFileRepositoryContract $files,
        private Paths $paths,
    ) {}

    public function rules(): array
    {
        return [
            'target_dir' => ['required', 'string'],
        ];
    }

    /**
     * @return array{removed: bool, path: string}
     */
    protected function handler(array $payload): array
    {
        $masterDir = $this->paths->masterDir();

        if ($this->files->isDirectory($masterDir)) {
            $this->files->remove($masterDir);

            return ['removed' => true, 'path' => $masterDir];
        }

        return ['removed' => false, 'path' => $masterDir];
    }
}
