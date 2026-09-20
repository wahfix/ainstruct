<?php

declare(strict_types=1);

namespace Lace\Ainstruct\Actions\Distribution;

use Lace\Ainstruct\Abstractions\Actions\Action;
use Lace\Ainstruct\Contracts\Repository\InstructionFileRepositoryContract;
use Lace\Ainstruct\Contracts\Repository\TemplateRepositoryContract;
use Lace\Ainstruct\Enums\TemplateOrigin;
use Lace\Ainstruct\Support\Paths;
use Lace\Ainstruct\Values\InstructionStatus;
use Lace\Ainstruct\Values\Template;

final class GetStatusAction extends Action
{
    /** File konstitusi yang harus identik dengan master (kontrak bash). */
    private const CONSTITUTION_FILES = [
        'AGENTS.md',
        'CLAUDE.md',
        'GEMINI.md',
        '.cursorrules',
        '.windsurfrules',
        '.continuerules',
        '.github/copilot-instructions.md',
    ];

    public function __construct(
        private TemplateRepositoryContract $templates,
        private InstructionFileRepositoryContract $files,
        private Paths $paths,
    ) {}

    protected function handler(array $payload): InstructionStatus
    {
        $targetDir = is_string($payload['target_dir'] ?? null) ? $payload['target_dir'] : $this->paths->targetDir();
        $masterFile = $targetDir.'/ai-instructions/master/ai-instructions.md';
        $masterModuleDir = $targetDir.'/ai-instructions/master/ai-instructions';
        $moduleDir = $targetDir.'/ai-instructions';

        $opencodeInstalled = $this->files->isOpenCodeCliInstalled();

        [$activeTemplate, $templateDir, $templateSource] = $this->detectActiveTemplate($masterFile);

        $masterCustomized = false;

        if ($this->files->fileExists($masterFile)) {
            $masterCustomized = $templateDir !== null
                ? ! $this->files->filesIdentical($masterFile, $templateDir.'/ai-instructions.md')
                : true;
        }

        $missing = [];
        $outOfSync = [];
        $present = 0;
        $total = 0;

        foreach (self::CONSTITUTION_FILES as $relative) {
            $total++;
            $path = $targetDir.'/'.$relative;

            if ($this->files->isFile($path)) {
                $present++;

                if (
                    $this->files->fileExists($masterFile)
                    && ! $this->files->filesIdentical($path, $masterFile)
                ) {
                    $outOfSync[] = $relative;
                }
            } else {
                $missing[] = $relative;
            }
        }

        // Cursor .mdc & Cline — nama file memuat framework aktif
        foreach ($this->directiveArtifacts($targetDir, $activeTemplate) as $path) {
            if ($path === null) {
                continue;
            }

            $relative = $this->relativeTo($targetDir, $path);
            $total++;

            if ($this->files->isFile($path)) {
                $present++;

                if ($this->files->fileExists($masterFile)) {
                    $inSync = str_ends_with($path, '.mdc')
                        ? $this->files->mdcMatchesMaster($path, $masterFile)
                        : $this->files->filesIdentical($path, $masterFile);

                    if (! $inSync) {
                        $outOfSync[] = $relative;
                    }
                }
            } else {
                $missing[] = $relative;
            }
        }

        // Artefak generated — cukup dicek keberadaannya
        foreach (['.aider.conf.yml', 'opencode.json'] as $generated) {
            $total++;

            if ($this->files->fileExists($targetDir.'/'.$generated)) {
                $present++;
            } else {
                $missing[] = $generated;
            }
        }

        // Master konstitusi & modul
        $total++;

        if ($this->files->isFile($masterFile)) {
            $present++;
        } else {
            $missing[] = 'ai-instructions/master/ai-instructions.md';
        }

        $total++;

        if ($this->files->moduleDirHasMdFiles($moduleDir)) {
            $present++;
        } else {
            $missing[] = 'ai-instructions/ (modul)';
        }

        $moduleOutOfSync = false;

        if (
            $this->files->isDirectory($masterModuleDir)
            && $this->files->isDirectory($moduleDir)
            && $this->files->directoriesDifferIgnoringLeftOnlyMaster($masterModuleDir, $moduleDir)
        ) {
            $moduleOutOfSync = true;
        }

        $issues = count($missing) + count($outOfSync) + (int) $moduleOutOfSync;

        $status = match (true) {
            ! $this->files->fileExists($masterFile) => 'not-distributed',
            $issues > 0 => 'needs-redistribute',
            default => 'ok',
        };

        return new InstructionStatus(
            targetDir: $targetDir,
            opencodeInstalled: $opencodeInstalled,
            activeTemplate: $activeTemplate,
            templateSource: $templateSource,
            masterExists: $this->files->fileExists($masterFile),
            masterCustomized: $masterCustomized,
            artifactsPresent: $present,
            artifactsTotal: $total,
            missing: $missing,
            outOfSync: $outOfSync,
            moduleOutOfSync: $moduleOutOfSync,
            issues: $issues,
            status: $status,
            templateDir: $templateDir,
        );
    }

    /**
     * @return array{0: string|null, 1: string|null, 2: string|null} name, dir, source
     */
    private function detectActiveTemplate(string $masterFile): array
    {
        if (! $this->files->fileExists($masterFile)) {
            return [null, null, null];
        }

        $customFirst = array_values(array_filter(
            $this->templates->all(),
            fn (Template $template): bool => $template->origin === TemplateOrigin::CUSTOM,
        ));

        $builtinOnly = array_values(array_filter(
            $this->templates->all(),
            fn (Template $template): bool => $template->origin === TemplateOrigin::BUILTIN,
        ));

        foreach ([...$customFirst, ...$builtinOnly] as $template) {
            if ($this->files->filesIdentical($masterFile, $template->constitutionPath())) {
                return [$template->name, $template->directory, $template->origin->value];
            }
        }

        return [null, null, null];
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function directiveArtifacts(string $targetDir, ?string $activeTemplate): array
    {
        if ($activeTemplate !== null) {
            return [
                $targetDir.'/.cursor/rules/'.$activeTemplate.'-directives.mdc',
                $targetDir.'/.clinerules/'.$activeTemplate.'-directives.md',
            ];
        }

        return [
            $this->firstGlob($targetDir.'/.cursor/rules/*-directives.mdc'),
            $this->firstGlob($targetDir.'/.clinerules/*-directives.md'),
        ];
    }

    private function firstGlob(string $pattern): ?string
    {
        $matches = glob($pattern) ?: [];

        return $matches[0] ?? null;
    }

    private function relativeTo(string $targetDir, string $path): string
    {
        return str_starts_with($path, $targetDir)
            ? substr($path, strlen($targetDir) + 1)
            : $path;
    }
}
