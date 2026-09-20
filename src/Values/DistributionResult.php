<?php

namespace Lace\Ainstruct\Values;

final class DistributionResult
{
    /**
     * @param  list<string>  $files  label → path, seperti laporan bash
     * @param  list<string>  $notes  pesan tambahan (master sudah ada, folder dilewati)
     * @param  list<array{header: string, lines: list<string>}>  $sections  bagian output kontrak bash
     */
    public function __construct(
        public readonly string $framework,
        public readonly string $templateSource,
        public readonly string $templateDir,
        public readonly array $files = [],
        public readonly array $notes = [],
        public readonly array $sections = [],
    ) {}

    public function count(): int
    {
        return count($this->files);
    }
}
