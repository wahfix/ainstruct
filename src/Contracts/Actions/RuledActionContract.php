<?php

declare(strict_types=1);

namespace Lace\Ainstruct\Contracts\Actions;

interface RuledActionContract
{
    /**
     * Aturan validasi per field payload.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array;
}
