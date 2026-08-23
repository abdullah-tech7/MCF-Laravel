<?php

declare(strict_types=1);

namespace App\MCF\AccessControl\Data;

final readonly class RoutePermission
{
    /**
     * @param string[] $routes
     */
    public function __construct(
        public string $permission,
        public array $routes,
    ) {
    }
}
