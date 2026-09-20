<?php

namespace Lace\Ainstruct\Actions\Distribution;

use Lace\Ainstruct\Abstractions\Actions\Action;
use Lace\Ainstruct\Contracts\Actions\RuledActionContract;
use Lace\Ainstruct\Contracts\Repository\InstructionFileRepositoryContract;
use Lace\Ainstruct\Contracts\Repository\MasterRepositoryContract;
use Lace\Ainstruct\Contracts\Repository\TemplateRepositoryContract;
use Lace\Ainstruct\Exceptions\TemplateNotFoundException;
use Lace\Ainstruct\Support\Paths;
use Lace\Ainstruct\Values\DistributionResult;

final class DistributeInstructionsAction extends Action implements RuledActionContract
{
    public function __construct(
        private TemplateRepositoryContract $templates,
        private MasterRepositoryContract $masters,
        private InstructionFileRepositoryContract $files,
        private Paths $paths,
    ) {}

    public function rules(): array
    {
        return [
            'template' => ['nullable', 'string', 'pattern:/^[A-Za-z0-9_-]+$/'],
        ];
    }

    protected function handler(array $payload): DistributionResult
    {
        $name = $payload['template'];

        $template = is_string($name) && $name !== ''
            ? $this->templates->findOrFail($name)
            : throw TemplateNotFoundException::noneSpecified($this->templates->names());

        $targetDir = $this->paths->targetDir();
        $masterDir = $this->paths->masterDir();
        $masterConstitution = $masterDir.DIRECTORY_SEPARATOR.'ai-instructions.md';
        $masterModuleDir = $masterDir.DIRECTORY_SEPARATOR.'ai-instructions';
        $frameworkLower = strtolower($template->name);

        $files = [];
        $notes = [];
        $sections = [];

        $this->addSection($sections, '[0] Sinkronisasi Master Instruction');
        $sync = $this->masters->syncTo($template, $masterDir);

        $constitutionNote = $sync['constitution'] === 'created'
            ? "🆕 Master utama dibuat dari template: {$masterConstitution}"
            : "📝 Master utama sudah ada, tidak ditimpa: {$masterConstitution}";
        $this->addNote($notes, $sections, $constitutionNote);

        if (in_array($sync['module'], ['created', 'exists'], true)) {
            $moduleNote = $sync['module'] === 'created'
                ? "🆕 Master modul dibuat dari template: {$masterModuleDir}/"
                : "📝 Master modul sudah ada, tidak ditimpa: {$masterModuleDir}/";
            $this->addNote($notes, $sections, $moduleNote);
        }

        // 1. Claude / Anthropic → AGENTS.md + CLAUDE.md
        $this->addSection($sections, '[1/10] Claude / Anthropic');
        $this->addFile($files, $sections, 'AGENTS.md', $targetDir.'/AGENTS.md');
        $this->files->copyFile($masterConstitution, $targetDir.'/AGENTS.md');
        $this->addFile($files, $sections, 'CLAUDE.md', $targetDir.'/CLAUDE.md');
        $this->files->copyFile($masterConstitution, $targetDir.'/CLAUDE.md');

        // 2. Google Gemini → GEMINI.md
        $this->addSection($sections, '[2/10] Google Gemini');
        $this->addFile($files, $sections, 'GEMINI.md', $targetDir.'/GEMINI.md');
        $this->files->copyFile($masterConstitution, $targetDir.'/GEMINI.md');

        // 3. GitHub Copilot → .github/copilot-instructions.md
        $this->addSection($sections, '[3/10] GitHub Copilot');
        $this->addFile($files, $sections, 'Copilot Instructions', $targetDir.'/.github/copilot-instructions.md');
        $this->files->copyFile($masterConstitution, $targetDir.'/.github/copilot-instructions.md');

        // 4. Cursor → .cursorrules + .cursor/rules/<fw>-directives.mdc
        $this->addSection($sections, '[4/10] Cursor');
        $this->addFile($files, $sections, '.cursorrules', $targetDir.'/.cursorrules');
        $this->files->copyFile($masterConstitution, $targetDir.'/.cursorrules');
        $mdcTarget = $targetDir.'/.cursor/rules/'.$frameworkLower.'-directives.mdc';
        $this->addFile($files, $sections, 'Cursor (.mdc)', $mdcTarget);
        $this->files->distributeCursorMdc($masterConstitution, $mdcTarget, $frameworkLower);

        // 5. Windsurf → .windsurfrules
        $this->addSection($sections, '[5/10] Windsurf');
        $this->addFile($files, $sections, '.windsurfrules', $targetDir.'/.windsurfrules');
        $this->files->copyFile($masterConstitution, $targetDir.'/.windsurfrules');

        // 6. Cline → .clinerules/<fw>-directives.md
        $this->addSection($sections, '[6/10] Cline');
        $clineTarget = $targetDir.'/.clinerules/'.$frameworkLower.'-directives.md';
        $this->addFile($files, $sections, 'Cline Rules', $clineTarget);
        $this->files->copyFile($masterConstitution, $clineTarget);

        // 7. Continue.dev → .continuerules
        $this->addSection($sections, '[7/10] Continue.dev');
        $this->addFile($files, $sections, '.continuerules', $targetDir.'/.continuerules');
        $this->files->copyFile($masterConstitution, $targetDir.'/.continuerules');

        // 8. Aider → .aider.conf.yml
        $this->addSection($sections, '[8/10] Aider');
        $this->addFile($files, $sections, 'Aider', $targetDir.'/.aider.conf.yml');
        $this->files->createAiderConfig($targetDir.'/.aider.conf.yml', $masterModuleDir, $template->name);

        // 9. opencode → opencode.json (default AI untuk pekerjaan)
        $this->addSection($sections, '[9/10] opencode');
        $this->addFile($files, $sections, 'opencode', $targetDir.'/opencode.json');
        $this->files->createOpenCodeConfig($targetDir.'/opencode.json');

        // 10. Modul ai-instructions/ (01-11 + 12-project-specific)
        $this->addSection($sections, '[10/10] Modul Instruksi');
        if ($this->files->isDirectory($masterModuleDir)) {
            $moduleTarget = $this->paths->moduleDir();
            $this->files->distributeModuleDir($masterModuleDir, $moduleTarget);
            $this->addFile($files, $sections, 'Modul Instruksi', $moduleTarget);
        } else {
            $this->addNote($notes, $sections, '⚠️  Modul Instruksi tidak ditemukan, dilewati');
        }

        // 11. Tim opencode (opsional — template dengan folder opencode/agent|skills)
        $this->addSection($sections, '[11/11] Tim opencode (agent + skill)');
        $opencodeLabels = $this->files->distributeOpenCodeDir(
            $template->directory.DIRECTORY_SEPARATOR.'opencode',
            $targetDir.DIRECTORY_SEPARATOR.'.opencode',
        );

        foreach ($opencodeLabels as $label) {
            $files[] = $label;
            $this->appendLine($sections, '✅ '.$label);
        }

        if ($opencodeLabels !== []) {
            $this->addNote($notes, $sections, 'ℹ️  Tim development opencode siap: .opencode/agent/* + .opencode/skills/');
        } else {
            $this->addNote($notes, $sections, "⚠️  Folder 'opencode/' template tidak ada — tim opencode dilewati");
        }

        return new DistributionResult(
            framework: $template->name,
            templateSource: $template->origin->value,
            templateDir: $template->directory,
            files: $files,
            notes: $notes,
            sections: $sections,
        );
    }

    private function addSection(array &$sections, string $header): void
    {
        $sections[] = ['header' => $header, 'lines' => []];
    }

    private function addNote(array &$notes, array &$sections, string $note): void
    {
        $notes[] = $note;
        $this->appendLine($sections, $note);
    }

    private function addFile(array &$files, array &$sections, string $label, string $path): void
    {
        $files[] = $label.' → '.$path;
        $this->appendLine($sections, '✅ '.$label.' → '.$path);
    }

    private function appendLine(array &$sections, string $line): void
    {
        $last = array_key_last($sections);
        if ($last !== null) {
            $sections[$last]['lines'][] = $line;
        }
    }
}
