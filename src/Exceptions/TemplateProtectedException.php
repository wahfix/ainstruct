<?php

namespace Lace\Ainstruct\Exceptions;

final class TemplateProtectedException extends AinstructException
{
    public function __construct(string $name)
    {
        parent::__construct(
            "❌ '{$name}' adalah built-in TERPROTEKSI — tidak bisa dihapus/diubah langsung.\n".
            "   Clone sebagai milik konsumen dulu: 'template clone <nama> {$name}', lalu kelola '<nama>'."
        );
    }
}
