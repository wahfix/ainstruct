<?php

namespace Lace\Ainstruct\Actions\Template;

use Lace\Ainstruct\Abstractions\Actions\Action;
use Lace\Ainstruct\Contracts\Actions\RuledActionContract;
use Lace\Ainstruct\Contracts\Repository\TemplateRepositoryContract;
use Lace\Ainstruct\Enums\TemplateOrigin;
use Lace\Ainstruct\Exceptions\InvalidOperationException;
use Lace\Ainstruct\Services\Template\SourceImporter;
use Lace\Ainstruct\Values\Template;

final class ImportTemplateAction extends Action implements RuledActionContract
{
    public function __construct(
        private TemplateRepositoryContract $templates,
        private SourceImporter $importer,
    ) {}

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'pattern:/^[A-Za-z0-9_-]+$/'],
            'source' => ['required', 'string'],
            'ref' => ['nullable', 'string'],
            'force' => ['nullable', 'boolean'],
        ];
    }

    protected function handler(array $payload): Template
    {
        $name = $payload['name'];
        $source = $payload['source'];
        $ref = $payload['ref'] ?? null;
        $force = (bool) ($payload['force'] ?? false);

        // create --from hanya untuk sumber git; nama template lain = salah kaprah
        // (menyalin template lain adalah tugas `template clone`).
        if (! $this->importer->isGitSource($source)) {
            throw new InvalidOperationException(
                "--from <sumber> pada template create hanya menerima sumber git (URL https/ssh atau jalur repo lokal): '{$source}'. Untuk menyalin template lain, gunakan: template clone <nama> <sumber>."
            );
        }

        $conflict = $this->templates->find($name);

        if ($conflict !== null && ! $force) {
            throw new InvalidOperationException(
                "Nama '{$name}' sudah dipakai oleh: {$conflict->directory}\n".
                '   Gunakan --force untuk menimpa template custom / shadow built-in.'
            );
        }

        $target = $this->templates->consumerPathFor($name);

        $this->importer->import($source, $target, $ref);
        $this->importer->writeSource($target, $source, $ref);

        return new Template($name, $target, TemplateOrigin::CUSTOM);
    }
}
