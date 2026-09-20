<?php

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

    /**
     * Path direktori custom konsumen untuk nama template (tanpa validasi ada).
     */
    public function consumerPathFor(string $name): string;

    /**
     * Built-in dengan nama sama (case-insensitive) atau null.
     */
    public function findBuiltin(string $name): ?Template;

    /**
     * Kandidat untuk `init`: custom dulu lalu built-in, nama unik
     * (case-insensitive) — custom shadow built-in.
     *
     * @return list<Template>
     */
    public function candidates(): array;
}
