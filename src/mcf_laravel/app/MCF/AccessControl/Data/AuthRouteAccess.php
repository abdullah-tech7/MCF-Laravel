<?php

declare(strict_types=1);

namespace App\MCF\AccessControl\Data;

use App\MCF\AccessControl\Enum\GuardType;

final readonly class AuthRouteAccess extends BaseRouteAccess
{
    /**
     * @param string[] $routeNames
     */
    public function __construct(
        array $routeNames,
    ) {
        parent::__construct(
            routeNames: $routeNames,
            guard: GuardType::AUTH,
        );
    }
}
