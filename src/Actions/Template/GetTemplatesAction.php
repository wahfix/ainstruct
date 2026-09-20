<?php

namespace Lace\Ainstruct\Actions\Template;

use Lace\Ainstruct\Abstractions\Actions\Action;
use Lace\Ainstruct\Contracts\Repository\TemplateRepositoryContract;
use Lace\Ainstruct\Values\Template;

final class GetTemplatesAction extends Action
{
    public function __construct(private TemplateRepositoryContract $templates) {}

    /**
     * @return list<Template>
     */
    protected function handler(array $payload): array
    {
        return $this->templates->all();
    }
}
