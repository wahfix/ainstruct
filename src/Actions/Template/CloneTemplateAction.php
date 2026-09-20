<?php

namespace Lace\Ainstruct\Actions\Template;

use Lace\Ainstruct\Abstractions\Actions\Action;
use Lace\Ainstruct\Contracts\Actions\RuledActionContract;
use Lace\Ainstruct\Contracts\Repository\InstructionFileRepositoryContract;
use Lace\Ainstruct\Contracts\Repository\TemplateRepositoryContract;
use Lace\Ainstruct\Enums\TemplateOrigin;
use Lace\Ainstruct\Exceptions\InvalidOperationException;
use Lace\Ainstruct\Values\Template;

final class CloneTemplateAction extends Action implements RuledActionContract
{
    public function __construct(
        private TemplateRepositoryContract $templates,
        private InstructionFileRepositoryContract $files,
    ) {}

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'pattern:/^[A-Za-z0-9_-]+$/'],
            'source' => ['required', 'string'],
            'force' => ['nullable', 'boolean'],
        ];
    }

    protected function handler(array $payload): Template
    {
        $name = $payload['name'];
        $source = $payload['source'];
        $force = (bool) ($payload['force'] ?? false);

        $sourceTemplate = $this->templates->findOrFail($source);

        $conflict = $this->templates->find($name);

        if ($conflict !== null && ! $force) {
            throw new InvalidOperationException(
                "Nama '{$name}' sudah dipakai oleh: {$conflict->directory}\n".
                '   Gunakan --force untuk menimpa template custom / shadow built-in.'
            );
        }

        $target = $this->templates->consumerPathFor($name);

        if ($this->files->exists($target)) {
            $this->files->remove($target);
        }

        $this->files->copyDirectory($sourceTemplate->directory, $target);

        return new Template($name, $target, TemplateOrigin::CUSTOM);
    }
}
