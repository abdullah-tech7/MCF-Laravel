<?php

declare(strict_types=1);

namespace App\MCF\AccessControl\Data;

use App\MCF\AccessControl\Enum\GuardType;

abstract readonly class PermissionRouteAccess extends RouteAccess
{
    /**
     * @param RoutePermission[] $routes
     */
    public function __construct(
        public array $routes,
        GuardType $guard,
    ) {
        parent::__construct(
            guard: $guard,
        );
    }
}
