<?php

declare(strict_types=1);

namespace Lace\Ainstruct\Enums;

enum TemplateOrigin: string
{
    case BUILTIN = 'builtin';
    case CUSTOM = 'custom';
}
