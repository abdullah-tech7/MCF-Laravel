<?php

declare(strict_types=1);

namespace App\MCF\AccessControl\Registry;

use App\MCF\AccessControl\Data\BaseRouteAccess;
use App\MCF\AccessControl\Data\PermissionRouteAccess;
use App\MCF\AccessControl\Data\RouteAccess;
use App\MCF\AccessControl\Data\RoleRouteAccess;
use LogicException;

final class McfRouteDataRegistry
{
    /**
     * @var array<string, RouteAccess>
     */
    private static array $routes = [];

    /**
     * Register Route Access definitions.
     *
     * Supported definitions:
     *
     * - BaseRouteAccess
     * - PermissionRouteAccess
     *
     * PermissionRouteAccess includes:
     *
     * - RoleRouteAccess
     * - CustomRouteAccess
     *
     * @param RouteAccess[] $routeAccessList
     *
     * @throws LogicException
     */
    public static function register(
        array $routeAccessList,
    ): void {
        foreach ($routeAccessList as $routeAccess) {
            self::registerRouteAccess(
                $routeAccess,
            );
        }
    }

    /**
     * Register a single Route Access definition.
     *
     * @throws LogicException
     */
    private static function registerRouteAccess(
        RouteAccess $routeAccess,
    ): void {
        /*
         * ----------------------------------------------------------
         * Base Route Access
         * ----------------------------------------------------------
         *
         * Guest / Auth / Any
         *
         * Routes are registered directly.
         */
        if ($routeAccess instanceof BaseRouteAccess) {
            foreach ($routeAccess->routeNames as $routeName) {
                self::registerRoute(
                    routeName: $routeName,
                    routeAccess: $routeAccess,
                );
            }

            return;
        }

        /*
         * ----------------------------------------------------------
         * Permission Route Access
         * ----------------------------------------------------------
         *
         * Role / Custom
         *
         * Routes are registered through RoutePermission.
         */
        if ($routeAccess instanceof PermissionRouteAccess) {
            foreach ($routeAccess->routes as $routePermission) {
                foreach ($routePermission->routes as $routeName) {
                    self::registerRoute(
                        routeName: $routeName,
                        routeAccess: $routeAccess,
                    );

                    /*
                     * RoleData is registered separately
                     * for Role Route Access.
                     */
                    if ($routeAccess instanceof RoleRouteAccess) {
                        foreach ($routeAccess->roles as $roleData) {
                            McfRoleDataRegistry::register(
                                routeName: $routeName,
                                roleData: $roleData,
                            );
                        }
                    }
                }
            }

            return;
        }

        throw new LogicException(
            sprintf(
                'Unsupported RouteAccess type [%s].',
                $routeAccess::class,
            ),
        );
    }

    /**
     * Register one concrete Route.
     *
     * A Route may only be registered once.
     *
     * @throws LogicException
     */
    private static function registerRoute(
        string $routeName,
        RouteAccess $routeAccess,
    ): void {
        $routeName = trim(
            $routeName,
        );

        if ($routeName === '') {
            throw new LogicException(
                'Cannot register an empty route name.',
            );
        }

        if (isset(self::$routes[$routeName])) {
            throw new LogicException(
                "Route [{$routeName}] has already been registered.",
            );
        }

        self::$routes[$routeName] = $routeAccess;
    }

    /**
     * Get Route Access definition.
     */
    public static function get(
        string $routeName,
    ): ?RouteAccess {
        return self::$routes[$routeName] ?? null;
    }

    /**
     * @return array<string, RouteAccess>
     */
    public static function all(): array
    {
        return self::$routes;
    }
}
