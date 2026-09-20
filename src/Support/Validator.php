<?php

declare(strict_types=1);

namespace Lace\Ainstruct\Support;

use Lace\Ainstruct\Exceptions\ValidationException;

/**
 * Validator minimal untuk RuledAction. Rule yang dikenali: required, nullable,
 * string, pattern:/.../. Aturan lain dilewati agar penambahan rule di Fase 2
 * (mis. in:[...]) tidak mengubah perilaku yang ada.
 */
final class Validator
{
    /**
     * @param  array<string, list<string>>  $rules
     */
    public function __construct(private array $rules) {}

    /**
     * @param  array<string, list<string>>  $rules
     */
    public static function fromRules(array $rules): self
    {
        return new self($rules);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validate(array $payload): array
    {
        $validated = [];

        foreach ($this->rules as $field => $ruleSet) {
            $value = $payload[$field] ?? null;

            foreach ($ruleSet as $rule) {
                if ($rule === 'nullable' && $value === null) {
                    break;
                }

                if ($rule === 'required' && ($value === null || $value === '')) {
                    throw ValidationException::for($field, 'wajib diisi');
                }

                if ($rule === 'string' && $value !== null && ! is_string($value)) {
                    throw ValidationException::for($field, 'harus berupa string');
                }

                if (str_starts_with($rule, 'pattern:') && $value !== null) {
                    $pattern = substr($rule, strlen('pattern:'));

                    if (preg_match($pattern, (string) $value) !== 1) {
                        throw ValidationException::for($field, 'format tidak valid');
                    }
                }
            }

            $validated[$field] = $value;
        }

        return $validated;
    }
}
