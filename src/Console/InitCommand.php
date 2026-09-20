<?php

namespace Lace\Ainstruct\Console;

use Lace\Ainstruct\Abstractions\Commands\Command;
use Lace\Ainstruct\Actions\Distribution\DistributeInstructionsAction;
use Lace\Ainstruct\Contracts\Detection\StackDetectorContract;
use Lace\Ainstruct\Contracts\Repository\TemplateRepositoryContract;
use Lace\Ainstruct\Exceptions\TemplateNotFoundException;
use Lace\Ainstruct\Exceptions\ValidationException;
use Lace\Ainstruct\Values\DetectedStack;

final class InitCommand extends Command
{
    public function __construct(
        private TemplateRepositoryContract $templates,
        private StackDetectorContract $detector,
        private DistributeInstructionsAction $distributeInstructionsAction,
    ) {}

    public function handle(Input $input): int
    {
        $this->header();

        $force = $input->hasFlag('--force');
        $dryRun = $input->hasFlag('--dry-run');
        $templateName = $input->flagValue('--template');
        $projectDir = getcwd() ?: '.';

        $chosen = null;

        if ($templateName !== null && $templateName !== '') {
            try {
                $template = $this->templates->findOrFail($templateName);
                $chosen = $this->detector->evaluate($template, $projectDir);
            } catch (TemplateNotFoundException $e) {
                $this->renderNotFound($e);

                return 1;
            }

            $this->line('👉 Mode paksa: memakai template '.$this->green($templateName).' (diabaikan: deteksi otomatis).');
        } else {
            $detected = [];

            foreach ($this->templates->candidates() as $template) {
                $stack = $this->detector->evaluate($template, $projectDir);

                if ($stack->score > 0) {
                    $detected[] = $stack;
                }
            }

            if ($detected === []) {
                $this->line($this->yellow('📭 Tidak ada sinyal teknologi terdeteksi di '.$projectDir));
                $this->line('   Proyek belum terdeteksi — paksa dengan --template <nama>, atau lihat template list.');
                $this->line();

                return 1;
            }

            $best = $detected[0];

            foreach (array_slice($detected, 1) as $stack) {
                if ($stack->score > $best->score) {
                    $best = $stack;
                }
            }

            $this->renderDetectionTable($detected, $best);

            $chosen = $best;
        }

        if ($dryRun) {
            $this->line($this->yellow('🔎 Dry-run: tidak ada perubahan. Distribusi akan memakai: '.$chosen->templateName));
            $this->line();

            return 0;
        }

        try {
            $result = $this->distributeInstructionsAction->handle([
                'template' => $chosen->templateName,
                'target_dir' => $projectDir,
            ]);
        } catch (TemplateNotFoundException $e) {
            $this->renderNotFound($e);

            return 1;
        } catch (ValidationException $e) {
            $this->line($this->red('❌ '.$e->getMessage()));

            return 1;
        }

        $this->renderDistributionResult($result);
        $this->showFrameworks($this->templates);

        return 0;
    }

    /**
     * @param  list<DetectedStack>  $detected
     */
    private function renderDetectionTable(array $detected, DetectedStack $best): void
    {
        $this->line('🔍 Deteksi teknologi di: '.getcwd() ?: '.');
        $this->line(sprintf('  %-18s %-10s %6s  %s', 'TEMPLATE', 'KEYAKINAN', 'SKOR', 'SINYAL COCOK'));
        $this->line('  '.str_repeat('-', 66));
        $this->line();

        foreach ($detected as $stack) {
            $line = sprintf(
                '  %-18s %-10s %6d  %s',
                $stack->templateName,
                $stack->confidence->value,
                $stack->score,
                implode(', ', $stack->signals)
            );

            if ($stack === $best) {
                $this->line($this->green($line.' ★ terpilih'));
            } else {
                $this->line($line);
            }
        }

        $this->line();
    }
}
