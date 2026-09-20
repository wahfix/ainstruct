<?php

namespace Lace\Ainstruct\Console;

use Lace\Ainstruct\Abstractions\Commands\Command;
use Lace\Ainstruct\Actions\Distribution\DistributeInstructionsAction;
use Lace\Ainstruct\Contracts\Repository\TemplateRepositoryContract;
use Lace\Ainstruct\Exceptions\TemplateNotFoundException;
use Lace\Ainstruct\Exceptions\ValidationException;

final class DistributeCommand extends Command
{
    public function __construct(
        private DistributeInstructionsAction $distributeInstructionsAction,
        private TemplateRepositoryContract $templates,
    ) {}

    public function handle(Input $input): int
    {
        $this->header('AI Instructions Distribution CLI');

        try {
            $result = $this->distributeInstructionsAction->handle([
                'template' => $input->firstPositional(),
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
}
