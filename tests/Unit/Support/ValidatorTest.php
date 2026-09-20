<?php

namespace Lace\Ainstruct\Tests\Unit\Support;

use Lace\Ainstruct\Exceptions\ValidationException;
use Lace\Ainstruct\Support\Validator;
use Lace\Ainstruct\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class ValidatorTest extends TestCase
{
    #[Test]
    public function it_passes_required_string_and_pattern(): void
    {
        $validator = Validator::fromRules([
            'name' => ['required', 'string', 'pattern:/^[A-Za-z0-9_-]+$/'],
        ]);

        $validated = $validator->validate(['name' => 'laravel']);

        $this->assertSame('laravel', $validated['name']);
    }

    #[Test]
    public function it_throws_when_required_field_missing(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("field 'name'");

        Validator::fromRules(['name' => ['required', 'string']])->validate([]);
    }

    #[Test]
    public function it_keeps_nullable_null_without_failing(): void
    {
        $validator = Validator::fromRules([
            'name' => ['nullable', 'string', 'pattern:/^[A-Za-z0-9_-]+$/'],
        ]);

        $validated = $validator->validate([]);

        $this->assertNull($validated['name']);
    }

    #[Test]
    public function it_throws_when_pattern_does_not_match(): void
    {
        $this->expectException(ValidationException::class);

        Validator::fromRules([
            'name' => ['string', 'pattern:/^[A-Za-z0-9_-]+$/'],
        ])->validate(['name' => '../bad']);
    }
}
