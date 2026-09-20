<?php

namespace Lace\Ainstruct\Values;

final class WipeResult
{
    /**
     * @param  list<string>  $files  label artefak yang dihapus (format bash)
     */
    public function __construct(
        public readonly array $files,
    ) {}

    public function count(): int
    {
        return count($this->files);
    }
}
