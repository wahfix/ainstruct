<?php

declare(strict_types=1);

namespace Lace\Ainstruct\Exceptions;

final class TemplateNotFoundException extends AinstructException
{
    /**
     * @param  list<string>  $available
     */
    public function __construct(
        private string $templateName,
        private array $available = [],
        ?string $message = null,
    ) {
        parent::__construct($message ?? "Framework/template directory tidak ditemukan: {$templateName}");
    }

    /**
     * @param  list<string>  $available
     */
    public static function noneSpecified(array $available): self
    {
        return new self(
            templateName: '',
            available: $available,
            message: 'Tidak ada framework yang ditentukan. Jalankan: ainstruct <framework>',
        );
    }

    public function templateName(): string
    {
        return $this->templateName;
    }

    /**
     * @return list<string>
     */
    public function available(): array
    {
        return $this->available;
    }
}
