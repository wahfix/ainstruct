<?php

declare(strict_types=1);

namespace Lace\Ainstruct\Contracts\Repository;

use Lace\Ainstruct\Values\Template;

interface MasterRepositoryContract
{
    /**
     * Sinkronkan master instruction dari template ke direktori master target.
     * Master yang sudah ada tidak ditimpa (custom konsumen dipertahankan).
     *
     * @return array{constitution: string, module: string} status: created|exists|skipped
     */
    public function syncTo(Template $template, string $masterDir): array;
}
