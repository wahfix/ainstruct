<?php

namespace Lace\Ainstruct\Exceptions;

final class SessionNotFoundException extends AinstructException
{
    public function __construct(
        private string $sessionId,
        ?string $message = null,
    ) {
        parent::__construct($message ?? "Sesi opencode tidak ditemukan: {$sessionId}");
    }

    public function sessionId(): string
    {
        return $this->sessionId;
    }
}
