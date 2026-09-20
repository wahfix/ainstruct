<?php

namespace Lace\Ainstruct\Actions\Template;

use Lace\Ainstruct\Abstractions\Actions\Action;
use Lace\Ainstruct\Contracts\Actions\RuledActionContract;
use Lace\Ainstruct\Contracts\Repository\InstructionFileRepositoryContract;
use Lace\Ainstruct\Contracts\Repository\TemplateRepositoryContract;
use Lace\Ainstruct\Enums\TemplateOrigin;
use Lace\Ainstruct\Exceptions\InvalidOperationException;
use Lace\Ainstruct\Exceptions\TemplateProtectedException;
use Lace\Ainstruct\Values\Template;

final class UpdateTemplateAction extends Action implements RuledActionContract
{
    public function __construct(
        private TemplateRepositoryContract $templates,
        private InstructionFileRepositoryContract $files,
    ) {}

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'pattern:/^[A-Za-z0-9_-]+$/'],
            'from' => ['nullable', 'string'],
            'force' => ['nullable', 'boolean'],
        ];
    }

    protected function handler(array $payload): Template
    {
        $name = $payload['name'];
        $from = $payload['from'] ?? null;
        $force = (bool) ($payload['force'] ?? false);

        $target = $this->templates->consumerPathFor($name);

        if (! $this->files->isDirectory($target)) {
            $builtin = $this->templates->findBuiltin($name);

            if ($builtin !== null) {
                throw new TemplateProtectedException($name);
            }

            throw new InvalidOperationException("Template custom tidak ditemukan: {$name}");
        }

        if ($from === null || $from === '') {
            $sourceTemplate = $this->templates->findBuiltin($name);

            if ($sourceTemplate === null) {
                throw new InvalidOperationException(
                    "Tidak ada built-in '{$name}' — berikan '--from <sumber>' (mis. template update {$name} --from laravel)"
                );
            }
        } else {
            $sourceTemplate = $this->templates->findOrFail($from);
        }

        if ($sourceTemplate->directory === $target) {
            $this->files->readFile($target.'/ai-instructions.md');

            return new Template($name, $target, TemplateOrigin::CUSTOM);
        }

        if ($force) {
            $this->files->remove($target);
            $this->files->copyDirectory($sourceTemplate->directory, $target);
        }

        return new Template($name, $target, TemplateOrigin::CUSTOM);
    }
}
