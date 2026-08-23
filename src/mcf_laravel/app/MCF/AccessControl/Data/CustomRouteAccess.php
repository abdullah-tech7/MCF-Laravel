<?php

declare(strict_types=1);

namespace App\MCF\AccessControl\Data;

use App\MCF\AccessControl\Enum\GuardType;

final readonly class CustomRouteAccess extends PermissionRouteAccess
{
    /**
     * @param RoutePermission[] $routes
     * @param string[] $permissions
     */
    public function __construct(
        array $routes,
        GuardType $guard,
        public string $access = 'all',
        public array $permissions = [],
    ) {
        parent::__construct(
            routes: $routes,
            guard: $guard,
        );
    }
}
