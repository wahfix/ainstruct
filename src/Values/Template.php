<?php

namespace Lace\Ainstruct\Values;

use Lace\Ainstruct\Enums\TemplateOrigin;

final class Template
{
    public function __construct(
        public readonly string $name,
        public readonly string $directory,
        public readonly TemplateOrigin $origin,
    ) {}

    public function constitutionPath(): string
    {
        return $this->directory.DIRECTORY_SEPARATOR.'ai-instructions.md';
    }

    public function moduleDirectory(): string
    {
        return $this->directory.DIRECTORY_SEPARATOR.'ai-instructions';
    }
}
