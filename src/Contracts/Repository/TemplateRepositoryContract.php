<?php

declare(strict_types=1);

namespace Lace\Ainstruct\Contracts\Repository;

use Lace\Ainstruct\Exceptions\TemplateNotFoundException;
use Lace\Ainstruct\Values\Template;

interface TemplateRepositoryContract
{
    /**
     * Semua template yang valid: built-in lalu custom konsumen.
     *
     * @return list<Template>
     */
    public function all(): array;

    /**
     * Temukan template (case-insensitive); custom konsumen didahulukan
     * (shadow built-in). Null bila tidak ada.
     */
    public function find(string $name): ?Template;

    /**
     * @throws TemplateNotFoundException
     */
    public function findOrFail(string $name): Template;

    /**
     * Nama semua template (untuk pesan error/daftar).
     *
     * @return list<string>
     */
    public function names(): array;
}
