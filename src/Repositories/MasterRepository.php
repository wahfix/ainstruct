<?php

declare(strict_types=1);

namespace Lace\Ainstruct\Repositories;

use Lace\Ainstruct\Contracts\Repository\MasterRepositoryContract;
use Lace\Ainstruct\Support\Filesystem;
use Lace\Ainstruct\Support\Paths;
use Lace\Ainstruct\Values\Template;

final class MasterRepository implements MasterRepositoryContract
{
    public function __construct(
        private Paths $paths,
        private Filesystem $filesystem,
    ) {}

    public function syncTo(Template $template, string $masterDir): array
    {
        $this->filesystem->makeDirectory($masterDir);

        $constitutionStatus = 'created';
        $constitutionTarget = $masterDir.DIRECTORY_SEPARATOR.'ai-instructions.md';

        if ($this->filesystem->isFile($constitutionTarget)) {
            $constitutionStatus = 'exists';
        } else {
            $this->filesystem->copyFile($template->constitutionPath(), $constitutionTarget);
        }

        $moduleStatus = 'skipped';
        $moduleTarget = $masterDir.DIRECTORY_SEPARATOR.'ai-instructions';

        if ($this->filesystem->isDirectory($moduleTarget)) {
            $moduleStatus = 'exists';
        } elseif ($this->filesystem->isDirectory($template->moduleDirectory())) {
            $this->filesystem->copyDirectoryContents($template->moduleDirectory(), $moduleTarget);
            $moduleStatus = 'created';
        }

        return [
            'constitution' => $constitutionStatus,
            'module' => $moduleStatus,
        ];
    }
}
