<?php

namespace Lace\Ainstruct\Values;

use Lace\Ainstruct\Enums\DetectionConfidence;

final class DetectedStack
{
    /**
     * @param  list<string>  $signals
     */
    public function __construct(
        public readonly string $templateName,
        public readonly string $templateDir,
        public readonly int $score,
        public readonly DetectionConfidence $confidence,
        public readonly array $signals,
    ) {}
}
