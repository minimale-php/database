<?php

declare(strict_types=1);

namespace Minimale\Database\Driver\QueryNormalizer;

enum TokenType
{
    case NAMED;

    case POSITIONAL;

    case TEXT;
}
