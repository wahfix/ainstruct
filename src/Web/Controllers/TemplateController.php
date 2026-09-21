<?php

namespace Lace\Ainstruct\Web\Controllers;

use Lace\Ainstruct\Actions\Template\CloneTemplateAction;
use Lace\Ainstruct\Actions\Template\CreateTemplateAction;
use Lace\Ainstruct\Actions\Template\DeleteTemplateAction;
use Lace\Ainstruct\Actions\Template\GetTemplatePathAction;
use Lace\Ainstruct\Actions\Template\GetTemplatesAction;
use Lace\Ainstruct\Actions\Template\ImportTemplateAction;
use Lace\Ainstruct\Actions\Template\UpdateTemplateAction;
use Lace\Ainstruct\Enums\TemplateOrigin;
use Lace\Ainstruct\Exceptions\InvalidOperationException;
use Lace\Ainstruct\Exceptions\TemplateProtectedException;
use Lace\Ainstruct\Exceptions\ValidationException;
use Lace\Ainstruct\Services\Template\SourceImporter;
use Lace\Ainstruct\Services\Template\TemplateFileService;
use Lace\Ainstruct\Values\Template;
use Lace\Ainstruct\Web\Request;
use Lace\Ainstruct\Web\Response;

/**
 * Controller REST template. Memakai Action yang sama dengan CLI, jadi
 * proteksi built-in (TemplateProtectedException) dan validasi nama berasal
 * dari satu sumber kebenaran yang sama.
 */
final class TemplateController
{
    public function __construct(
        private GetTemplatesAction $getTemplatesAction,
        private CreateTemplateAction $createTemplateAction,
        private ImportTemplateAction $importTemplateAction,
        private CloneTemplateAction $cloneTemplateAction,
        private UpdateTemplateAction $updateTemplateAction,
        private DeleteTemplateAction $deleteTemplateAction,
        private GetTemplatePathAction $getTemplatePathAction,
        private TemplateFileService $files,
        private SourceImporter $importer,
    ) {}

    public function templates(Request $request): Response
    {
        $templates = $this->getTemplatesAction->handle();

        $builtin = [];
        $custom = [];

        foreach ($templates as $template) {
            $payload = $this->templatePayload($template);

            if ($template->origin === TemplateOrigin::CUSTOM) {
                $custom[] = $payload;
            } else {
                $builtin[] = $payload;
            }
        }

        return Response::json(200, [
            'ok' => true,
            'data' => ['builtin' => $builtin, 'custom' => $custom],
        ]);
    }

    public function detail(Request $request, array $matches): Response
    {
        $template = $this->getTemplatePathAction->handle(['name' => $matches['name']]);

        return Response::json(200, ['ok' => true, 'data' => $this->templatePayload($template)]);
    }

    public function create(Request $request): Response
    {
        $body = $request->body();

        $name = trim((string) ($body['name'] ?? ''));

        if ($name === '') {
            throw ValidationException::for('name', 'wajib diisi');
        }

        $source = trim((string) ($body['source'] ?? ''));
        $ref = isset($body['ref']) ? (string) $body['ref'] : null;
        $force = (bool) ($body['force'] ?? false);

        if ($source !== '') {
            $template = $this->importTemplateAction->handle([
                'name' => $name,
                'source' => $source,
                'ref' => $ref,
                'force' => $force,
            ]);
        } else {
            $template = $this->createTemplateAction->handle([
                'name' => $name,
                'force' => $force,
            ]);
        }

        return Response::json(201, ['ok' => true, 'data' => $this->templatePayload($template)]);
    }

    public function clone(Request $request, array $matches): Response
    {
        $body = $request->body();

        $source = trim((string) ($body['source'] ?? ''));

        if ($source === '') {
            throw ValidationException::for('source', 'wajib diisi');
        }

        $template = $this->cloneTemplateAction->handle([
            'name' => $matches['name'],
            'source' => $source,
            'force' => (bool) ($body['force'] ?? false),
        ]);

        return Response::json(201, ['ok' => true, 'data' => $this->templatePayload($template)]);
    }

    public function update(Request $request, array $matches): Response
    {
        $body = $request->body();

        if (! (bool) ($body['force'] ?? false)) {
            throw new InvalidOperationException(
                'Update menimpa isi template custom. Konfirmasi dulu lewat dialog (kirim force: true).'
            );
        }

        $template = $this->updateTemplateAction->handle([
            'name' => $matches['name'],
            'from' => isset($body['from']) ? (string) $body['from'] : null,
            'ref' => isset($body['ref']) ? (string) $body['ref'] : null,
            'force' => true,
        ]);

        return Response::json(200, ['ok' => true, 'data' => $this->templatePayload($template)]);
    }

    public function delete(Request $request, array $matches): Response
    {
        $body = $request->body();

        if (! (bool) ($body['force'] ?? false)) {
            throw new InvalidOperationException(
                'Penghapusan template custom bersifat permanen. Konfirmasi dulu lewat dialog (kirim force: true).'
            );
        }

        $path = $this->deleteTemplateAction->handle([
            'name' => $matches['name'],
            'force' => true,
        ]);

        return Response::json(200, [
            'ok' => true,
            'data' => ['name' => $matches['name'], 'path' => $path, 'removed' => true],
        ]);
    }

    public function tree(Request $request, array $matches): Response
    {
        $template = $this->getTemplatePathAction->handle(['name' => $matches['name']]);

        $path = (string) ($request->query()['path'] ?? '');

        return Response::json(200, [
            'ok' => true,
            'data' => [
                'template' => $template->name,
                'path' => $path,
                'editable' => $template->origin === TemplateOrigin::CUSTOM,
                'entries' => $this->files->tree($template->directory, $path),
            ],
        ]);
    }

    public function file(Request $request, array $matches): Response
    {
        $template = $this->getTemplatePathAction->handle(['name' => $matches['name']]);

        if ($request->method() === 'GET') {
            $path = (string) ($request->query()['path'] ?? '');

            if ($path === '') {
                throw ValidationException::for('path', 'wajib diisi');
            }

            return Response::json(200, [
                'ok' => true,
                'data' => [
                    'template' => $template->name,
                    'editable' => $template->origin === TemplateOrigin::CUSTOM,
                    ...$this->files->read($template->directory, $path),
                ],
            ]);
        }

        if ($template->origin === TemplateOrigin::BUILTIN) {
            throw new TemplateProtectedException($template->name);
        }

        $body = $request->body();
        $path = trim((string) ($body['path'] ?? ''));
        $content = (string) ($body['content'] ?? '');

        if ($path === '') {
            throw ValidationException::for('path', 'wajib diisi');
        }

        $this->files->write($template->directory, $path, $content);

        return Response::json(200, [
            'ok' => true,
            'data' => [
                'template' => $template->name,
                'path' => $path,
                'size' => strlen($content),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function templatePayload(Template $template): array
    {
        $payload = [
            'name' => $template->name,
            'directory' => $template->directory,
            'origin' => $template->origin->value,
            'editable' => $template->origin === TemplateOrigin::CUSTOM,
        ];

        if ($template->origin === TemplateOrigin::CUSTOM) {
            $source = $this->importer->readSource($template->directory);
            $payload['source'] = $source === null ? null : [
                'source' => $source['source'],
                'ref' => $source['ref'],
            ];
        }

        return $payload;
    }
}
