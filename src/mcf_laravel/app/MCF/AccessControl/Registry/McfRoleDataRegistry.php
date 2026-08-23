<?php

declare(strict_types=1);

namespace App\MCF\AccessControl\Registry;

use App\MCF\AccessControl\Data\RoleData;
use RuntimeException;

final class McfRoleDataRegistry
{
    /**
     * @var array<string, array<int|string, RoleData>>
     */
    private static array $roles = [];

    /**
     * Register RoleData for a specific Route.
     *
     * One Role may only be registered once
     * for the same Route.
     *
     * @throws RuntimeException
     */
    public static function register(
        string $routeName,
        RoleData $roleData,
    ): void {
        if (isset(self::$roles[$routeName][$roleData->role])) {
            throw new RuntimeException(
                sprintf(
                    'Role [%s] is already registered for route [%s].',
                    (string) $roleData->role,
                    $routeName,
                ),
            );
        }

        self::$roles[$routeName][$roleData->role] = $roleData;
    }

    /**
     * Get RoleData for a specific Route and Role.
     */
    public static function get(
        string $routeName,
        int|string $role,
    ): ?RoleData {
        return self::$roles[$routeName][$role] ?? null;
    }

    /**
     * Get all RoleData registered for a Route.
     *
     * @return array<int|string, RoleData>
     */
    public static function allForRoute(
        string $routeName,
    ): array {
        return self::$roles[$routeName] ?? [];
    }

    /**
     * Get the complete RoleData registry.
     *
     * @return array<string, array<int|string, RoleData>>
     */
    public static function all(): array
    {
        return self::$roles;
    }
}
