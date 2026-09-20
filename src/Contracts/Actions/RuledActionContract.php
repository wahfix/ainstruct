<?php

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
