<?php

namespace Lace\Ainstruct\Repositories;

use Lace\Ainstruct\Contracts\Repository\TemplateRepositoryContract;
use Lace\Ainstruct\Enums\TemplateOrigin;
use Lace\Ainstruct\Exceptions\TemplateNotFoundException;
use Lace\Ainstruct\Support\Filesystem;
use Lace\Ainstruct\Support\Paths;
use Lace\Ainstruct\Values\Template;

final class TemplateRepository implements TemplateRepositoryContract
{
    public function __construct(
        private Paths $paths,
        private Filesystem $filesystem,
    ) {}

    public function all(): array
    {
        return [
            ...$this->scanTemplates($this->paths->builtinTemplatesDir(), TemplateOrigin::BUILTIN),
            ...$this->scanTemplates($this->paths->consumerTemplatesDir(), TemplateOrigin::CUSTOM),
        ];
    }

    public function find(string $name): ?Template
    {
        $lower = strtolower($name);

        foreach ($this->custom() as $template) {
            if (strtolower($template->name) === $lower) {
                return $template;
            }
        }

        foreach ($this->builtin() as $template) {
            if (strtolower($template->name) === $lower) {
                return $template;
            }
        }

        return null;
    }

    public function findOrFail(string $name): Template
    {
        $template = $this->find($name);

        if ($template === null) {
            throw new TemplateNotFoundException($name, $this->names());
        }

        return $template;
    }

    public function names(): array
    {
        $names = array_map(
            fn (Template $template): string => $template->name,
            $this->all(),
        );

        sort($names);

        return $names;
    }

    public function consumerPathFor(string $name): string
    {
        return $this->paths->consumerTemplatesDir().DIRECTORY_SEPARATOR.$name;
    }

    public function findBuiltin(string $name): ?Template
    {
        $lower = strtolower($name);

        foreach ($this->builtin() as $template) {
            if (strtolower($template->name) === $lower) {
                return $template;
            }
        }

        return null;
    }

    public function candidates(): array
    {
        $seen = [];
        $candidates = [];

        foreach ([...$this->custom(), ...$this->builtin()] as $template) {
            $key = strtolower($template->name);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $candidates[] = $template;
        }

        return $candidates;
    }

    /**
     * @return list<Template>
     */
    private function builtin(): array
    {
        return $this->scanTemplates($this->paths->builtinTemplatesDir(), TemplateOrigin::BUILTIN);
    }

    /**
     * @return list<Template>
     */
    private function custom(): array
    {
        return $this->scanTemplates($this->paths->consumerTemplatesDir(), TemplateOrigin::CUSTOM);
    }

    /**
     * @return list<Template>
     */
    private function scanTemplates(string $directory, TemplateOrigin $origin): array
    {
        if (! $this->filesystem->isDirectory($directory)) {
            return [];
        }

        $templates = [];

        foreach (glob($directory.DIRECTORY_SEPARATOR.'*') ?: [] as $path) {
            $name = basename($path);

            if (! $this->filesystem->isDirectory($path)) {
                continue;
            }

            if (! $this->filesystem->isFile($path.DIRECTORY_SEPARATOR.'ai-instructions.md')) {
                continue;
            }

            $templates[] = new Template($name, $path, $origin);
        }

        return $templates;
    }
}
