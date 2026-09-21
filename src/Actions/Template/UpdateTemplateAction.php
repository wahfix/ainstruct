<?php

namespace Lace\Ainstruct\Actions\Template;

use Lace\Ainstruct\Abstractions\Actions\Action;
use Lace\Ainstruct\Contracts\Actions\RuledActionContract;
use Lace\Ainstruct\Contracts\Repository\InstructionFileRepositoryContract;
use Lace\Ainstruct\Contracts\Repository\TemplateRepositoryContract;
use Lace\Ainstruct\Enums\TemplateOrigin;
use Lace\Ainstruct\Exceptions\InvalidOperationException;
use Lace\Ainstruct\Exceptions\TemplateProtectedException;
use Lace\Ainstruct\Services\Template\SourceImporter;
use Lace\Ainstruct\Values\Template;

final class UpdateTemplateAction extends Action implements RuledActionContract
{
    public function __construct(
        private TemplateRepositoryContract $templates,
        private InstructionFileRepositoryContract $files,
        private SourceImporter $importer,
    ) {}

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'pattern:/^[A-Za-z0-9_-]+$/'],
            'from' => ['nullable', 'string'],
            'ref' => ['nullable', 'string'],
            'force' => ['nullable', 'boolean'],
        ];
    }

    protected function handler(array $payload): Template
    {
        $name = $payload['name'];
        $from = $payload['from'] ?? null;
        $ref = $payload['ref'] ?? null;
        $force = (bool) ($payload['force'] ?? false);

        if ($from !== null && $from !== '') {
            $from = $this->importer->expandSource($from);
        }

        $target = $this->templates->consumerPathFor($name);

        if (! $this->files->isDirectory($target)) {
            $builtin = $this->templates->findBuiltin($name);

            if ($builtin !== null) {
                throw new TemplateProtectedException($name);
            }

            throw new InvalidOperationException("Template custom tidak ditemukan: {$name}");
        }

        // Sumber git eksplisit (URL/jalur repo) → impor ulang dari sana.
        if ($from !== null && $from !== '' && $this->importer->isGitSource($from)) {
            $this->importer->import($from, $target, $ref);
            $this->importer->writeSource($target, $from, $ref);

            return new Template($name, $target, TemplateOrigin::CUSTOM);
        }

        // Tanpa --from: template yang pernah diimpor dari git ditarik ulang
        // dari sumber tersimpan (ainstruct.source); selain itu built-in senama.
        if ($from === null || $from === '') {
            $stored = $this->importer->readSource($target);

            if ($stored !== null) {
                $storedSource = $this->importer->expandSource($stored['source']);

                $this->importer->import($storedSource, $target, $ref ?? $stored['ref']);
                $this->importer->writeSource($target, $storedSource, $ref ?? $stored['ref']);

                return new Template($name, $target, TemplateOrigin::CUSTOM);
            }

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
