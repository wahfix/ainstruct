<?php

namespace Lace\Ainstruct\Actions\Template;

use Lace\Ainstruct\Abstractions\Actions\Action;
use Lace\Ainstruct\Contracts\Repository\TemplateRepositoryContract;
use Lace\Ainstruct\Values\Template;

final class GetTemplatePathAction extends Action
{
    public function __construct(private TemplateRepositoryContract $templates) {}

    protected function handler(array $payload): Template
    {
        $name = $payload['name'];

        return $this->templates->findOrFail($name);
    }
}
