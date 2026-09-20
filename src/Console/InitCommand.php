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
        $this->header('Deteksi stack & distribusi otomatis');

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

            $this->style()->notice('Mode paksa: memakai template '.$this->style()->green($templateName).', deteksi otomatis diabaikan.');
            $this->style()->blank();
        } else {
            $detected = [];

            foreach ($this->templates->candidates() as $template) {
                $stack = $this->detector->evaluate($template, $projectDir);

                if ($stack->score > 0) {
                    $detected[] = $stack;
                }
            }

            if ($detected === []) {
                $this->style()->warn('Tidak ada sinyal teknologi terdeteksi di '.$projectDir);
                $this->style()->bullet('Paksa dengan '.$this->style()->cyan('--template <nama>').', atau lihat '.$this->style()->cyan('template list').'.');
                $this->style()->blank();

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
            $this->style()->notice('Dry-run: tidak ada perubahan. Distribusi akan memakai '.$chosen->templateName);
            $this->style()->blank();

            return 0;
        }

        try {
            $result = $this->distributeInstructionsAction->handle([
                'template' => $chosen->templateName,
            ]);
        } catch (TemplateNotFoundException $e) {
            $this->renderNotFound($e);

            return 1;
        } catch (ValidationException $e) {
            $this->style()->error($e->getMessage());

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
        $this->style()->info('Deteksi teknologi di '.(getcwd() ?: '.'));

        $headers = ['TEMPLATE', 'KEYAKINAN', 'SKOR', 'SINYAL'];

        // Cell polos; table yang menangani highlight hijau (hindari wrap ganda).
        $rows = array_map(
            fn (DetectedStack $stack): array => [
                $stack->templateName,
                $stack->confidence->value,
                (string) $stack->score,
                implode(', ', $stack->signals),
            ],
            $detected
        );

        $highlight = null;

        foreach ($detected as $index => $stack) {
            if ($stack === $best) {
                $highlight = $index;
            }
        }

        $this->style()->table($headers, $rows, $highlight);
        $this->style()->blank();
    }
}
