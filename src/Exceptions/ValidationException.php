<?php

declare(strict_types=1);

namespace Lace\Ainstruct\Exceptions;

final class ValidationException extends AinstructException
{
    public static function for(string $field, string $message): self
    {
        return new self("Validasi gagal pada field '{$field}': {$message}");
    }
}
