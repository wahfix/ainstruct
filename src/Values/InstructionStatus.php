<?php

namespace Lace\Ainstruct\Values;

/**
 * Hasil pemeriksaan status. Field publik mengikuti kontrak JSON
 * `ainstruct status --json` (lihat setup-ai-rules.sh) ditambah
 * templateDir untuk laporan human (tidak ikut JSON).
 */
final class InstructionStatus
{
    /**
     * @param  list<string>  $missing
     * @param  list<string>  $outOfSync
     */
    public function __construct(
        public readonly string $targetDir,
        public readonly bool $opencodeInstalled,
        public readonly ?string $activeTemplate,
        public readonly ?string $templateSource,
        public readonly bool $masterExists,
        public readonly bool $masterCustomized,
        public readonly int $artifactsPresent,
        public readonly int $artifactsTotal,
        public readonly array $missing,
        public readonly array $outOfSync,
        public readonly bool $moduleOutOfSync,
        public readonly int $issues,
        public readonly string $status,
        public readonly ?string $templateDir = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toJsonArray(): array
    {
        return [
            'target_dir' => $this->targetDir,
            'opencode_installed' => $this->opencodeInstalled,
            'active_template' => $this->activeTemplate,
            'template_source' => $this->templateSource,
            'master_exists' => $this->masterExists,
            'master_customized' => $this->masterCustomized,
            'artifacts_present' => $this->artifactsPresent,
            'artifacts_total' => $this->artifactsTotal,
            'missing' => $this->missing,
            'out_of_sync' => $this->outOfSync,
            'module_out_of_sync' => $this->moduleOutOfSync,
            'issues' => $this->issues,
            'status' => $this->status,
        ];
    }
}
