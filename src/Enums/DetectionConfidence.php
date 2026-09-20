<?php

namespace Lace\Ainstruct\Enums;

enum DetectionConfidence: string
{
    case CONFIRMED = 'CONFIRMED';
    case STRONG = 'STRONG';
    case WEAK = 'WEAK';
    case UNKNOWN = 'UNKNOWN';

    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 6 => self::CONFIRMED,
            $score >= 4 => self::STRONG,
            $score >= 2 => self::WEAK,
            default => self::UNKNOWN,
        };
    }
}
