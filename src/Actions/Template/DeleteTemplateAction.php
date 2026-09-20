<?php

namespace Lace\Ainstruct\Actions\Template;

use Lace\Ainstruct\Abstractions\Actions\Action;
use Lace\Ainstruct\Contracts\Actions\RuledActionContract;
use Lace\Ainstruct\Contracts\Repository\InstructionFileRepositoryContract;
use Lace\Ainstruct\Contracts\Repository\TemplateRepositoryContract;
use Lace\Ainstruct\Exceptions\InvalidOperationException;
use Lace\Ainstruct\Exceptions\TemplateProtectedException;

final class DeleteTemplateAction extends Action implements RuledActionContract
{
    public function __construct(
        private TemplateRepositoryContract $templates,
        private InstructionFileRepositoryContract $files,
    ) {}

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'pattern:/^[A-Za-z0-9_-]+$/'],
            'force' => ['nullable', 'boolean'],
        ];
    }

    protected function handler(array $payload): string
    {
        $name = $payload['name'];
        $force = (bool) ($payload['force'] ?? false);

        $target = $this->templates->consumerPathFor($name);

        if ($this->files->isDirectory($target)) {
            if ($force) {
                $this->files->remove($target);
            }

            return $target;
        }

        if ($this->templates->findBuiltin($name) !== null) {
            throw new TemplateProtectedException($name);
        }

        throw new InvalidOperationException("Template tidak ditemukan: {$name}");
    }
}
