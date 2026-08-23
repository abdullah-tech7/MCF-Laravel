<?php

declare(strict_types=1);

namespace App\MCF\AccessControl\Data;

use App\MCF\AccessControl\Enum\GuardType;

abstract readonly class RouteAccess
{
    public function __construct(
        public GuardType $guard,
    ) {
    }
}
