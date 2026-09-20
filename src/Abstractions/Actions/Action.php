<?php

declare(strict_types=1);

namespace Lace\Ainstruct\Abstractions\Actions;

use Lace\Ainstruct\Contracts\Actions\RuledActionContract;
use Lace\Ainstruct\Support\Validator;

abstract class Action
{
    /**
     * Entry publik aksi. Action yang mengimplementasikan RuledActionContract
     * divalidasi di sini, lalu payload tervalidasi diteruskan ke handler.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): mixed
    {
        if ($this instanceof RuledActionContract) {
            $validated = Validator::fromRules($this->rules())->validate($payload);

            return $this->handler($validated);
        }

        return $this->handler($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    abstract protected function handler(array $payload): mixed;
}
