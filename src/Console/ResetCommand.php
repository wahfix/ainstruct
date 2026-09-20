<?php

namespace Lace\Ainstruct\Console;

use Lace\Ainstruct\Abstractions\Commands\Command;
use Lace\Ainstruct\Actions\Distribution\DistributeInstructionsAction;
use Lace\Ainstruct\Actions\Distribution\ResetMasterAction;
use Lace\Ainstruct\Contracts\Repository\TemplateRepositoryContract;
use Lace\Ainstruct\Exceptions\TemplateNotFoundException;
use Lace\Ainstruct\Exceptions\ValidationException;

/**
 * `reset [<framework>]` — hapus master rules lalu distribusikan ulang
 * (kontrak bash: reset_master diikuti jatuh ke alur distribute).
 */
final class ResetCommand extends Command
{
    public function __construct(
        private ResetMasterAction $resetMasterAction,
        private DistributeInstructionsAction $distributeInstructionsAction,
        private TemplateRepositoryContract $templates,
    ) {}

    public function handle(Input $input): int
    {
        $this->header();

        $this->resetMasterAction->handle([
            'target_dir' => getcwd() ?: '.',
        ]);

        $this->line($this->blue('♻️  Reset: master rules removed. Re-distributing...'));
        $this->line();

        try {
            $result = $this->distributeInstructionsAction->handle([
                'template' => $input->firstPositional(),
                'target_dir' => getcwd() ?: '.',
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
}
