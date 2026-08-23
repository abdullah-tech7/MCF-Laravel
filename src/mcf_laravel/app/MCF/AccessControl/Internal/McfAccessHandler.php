<?php

declare(strict_types=1);

namespace App\MCF\AccessControl\Internal;

use App\MCF\AccessControl\Data\PermissionRouteAccess;
use App\MCF\AccessControl\Data\RoleRouteAccess;
use App\MCF\AccessControl\Data\RouteAccess;
use App\MCF\AccessControl\Enum\GuardType;
use App\MCF\AccessControl\McfAccess;
use App\MCF\AccessControl\Registry\McfRouteDataRegistry;
use App\MCF\Authentication\McfAuth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class McfAccessHandler
{
    /**
     * Handle access for the current request.
     *
     * The process is:
     *
     * 1. Resolve the current Route.
     * 2. Resolve its RouteAccess definition.
     * 3. Execute the Guard.
     * 4. If the Route is Permission-based:
     *      Route -> Permission
     *      Permission -> Authorization
     *
     * BaseRouteAccess ends after the Guard.
     */
    public function handle(
        Request $request,
    ): ?Response {
        $routeName = $request->route()?->getName();

        if ($routeName === null) {
            return null;
        }

        $routeAccess = McfRouteDataRegistry::get(
            $routeName,
        );

        if ($routeAccess === null) {
            return null;
        }

        /*
         * ----------------------------------------------------------
         * Guard
         * ----------------------------------------------------------
         */

        $guardResponse = $this->handleGuard(
            $routeAccess,
        );

        if ($guardResponse !== null) {
            return $guardResponse;
        }

        /*
         * ----------------------------------------------------------
         * Permission Authorization
         * ----------------------------------------------------------
         *
         * Only Role and Custom Route Access require
         * Permission Authorization.
         */
        if ($routeAccess instanceof PermissionRouteAccess) {
            if (
                ! $this->authorizeRoute(
                    routeAccess: $routeAccess,
                    routeName: $routeName,
                )
            ) {
                abort(401);
            }
        }

        return null;
    }

    /**
     * Check a Permission for the current Route.
     *
     * This is the public authorization operation used
     * by McfAccess::can().
     *
     * The Permission is supplied by the caller.
     *
     * Example:
     *
     * McfAccess::can('Create Manager')
     */
    public function can(
        string $permission,
    ): bool {
        $permission = trim(
            $permission,
        );

        if ($permission === '') {
            return false;
        }

        $routeName = request()->route()?->getName();

        if ($routeName === null) {
            return false;
        }

        $routeAccess = McfRouteDataRegistry::get(
            $routeName,
        );

        if ($routeAccess === null) {
            return false;
        }

        /*
         * Only Permission-based Route Access
         * participates in Permission Authorization.
         */
        if (! $routeAccess instanceof PermissionRouteAccess) {
            return false;
        }

        return $this->authorizePermission(
            routeAccess: $routeAccess,
            permission: $permission,
        );
    }

    /**
     * Execute the Guard associated with the Route Access.
     */
    private function handleGuard(
        RouteAccess $routeAccess,
    ): ?Response {
        return match ($routeAccess->guard) {
            GuardType::ANY => null,

            GuardType::GUEST => $this->handleGuest(),

            GuardType::AUTH => $this->handleAuth(),

            GuardType::ROLE => $this->handleRole(
                $routeAccess,
            ),
        };
    }

    /**
     * Handle Guest Guard.
     *
     * Guest users may continue.
     *
     * Authenticated users are redirected away.
     */
    private function handleGuest(): ?Response
    {
        if (! McfAuth::check()) {
            return null;
        }

        return redirect('/');
    }

    /**
     * Handle Auth Guard.
     *
     * Authenticated users may continue.
     *
     * Guests are redirected to the login route.
     */
    private function handleAuth(): ?Response
    {
        if (McfAuth::check()) {
            return null;
        }

        return redirect()->route(
            McfAccess::resolveLoginRouteName(),
        );
    }

    /**
     * Handle Role Guard.
     *
     * This verifies that:
     *
     * 1. The user is authenticated.
     * 2. The user's Role exists in the RoleRouteAccess definition.
     *
     * Permission Authorization is performed afterwards.
     */
    private function handleRole(
        RouteAccess $routeAccess,
    ): ?Response {
        if (! $routeAccess instanceof RoleRouteAccess) {
            abort(500);
        }

        if (! McfAuth::check()) {
            return redirect()->route(
                McfAccess::resolveLoginRouteName(),
            );
        }

        $user = McfAuth::user();

        if ($user === null) {
            return redirect()->route(
                McfAccess::resolveLoginRouteName(),
            );
        }

        $userRole = McfAccess::resolveRole(
            $user,
        );

        foreach ($routeAccess->roles as $roleData) {
            if ($userRole === $roleData->role) {
                return null;
            }
        }

        abort(401);
    }

    /**
     * Resolve the Permission represented by the current Route.
     *
     * A Permission-based Route must be registered inside
     * a RoutePermission definition.
     *
     * If the Route is not registered:
     *
     *     false
     *
     * This is intentionally fail-closed.
     */
    private function authorizeRoute(
        PermissionRouteAccess $routeAccess,
        string $routeName,
    ): bool {
        $permission = $this->resolveRoutePermission(
            routeAccess: $routeAccess,
            routeName: $routeName,
        );

        if ($permission === null) {
            return false;
        }

        return $this->authorizePermission(
            routeAccess: $routeAccess,
            permission: $permission,
        );
    }

    /**
     * Resolve the Permission name associated
     * with a Route.
     *
     * RoutePermission defines:
     *
     * Permission -> Routes
     *
     * Therefore the current Route is searched
     * inside the registered RoutePermission definitions.
     */
    private function resolveRoutePermission(
        PermissionRouteAccess $routeAccess,
        string $routeName,
    ): ?string {
        foreach ($routeAccess->routes as $routePermission) {
            foreach ($routePermission->routes as $registeredRoute) {
                if ($registeredRoute === $routeName) {
                    return $routePermission->permission;
                }
            }
        }

        return null;
    }

    /**
     * Authorize a Permission according to
     * the concrete Permission Route Access type.
     */
    private function authorizePermission(
        PermissionRouteAccess $routeAccess,
        string $permission,
    ): bool {
        /*
         * ----------------------------------------------------------
         * Role
         * ----------------------------------------------------------
         */
        if ($routeAccess instanceof RoleRouteAccess) {
            return $this->authorizeRolePermission(
                routeAccess: $routeAccess,
                permission: $permission,
            );
        }

        /*
         * ----------------------------------------------------------
         * Custom
         * ----------------------------------------------------------
         */
        return $this->checkAccess(
            access: $routeAccess->access,
            permissions: $routeAccess->permissions,
            permission: $permission,
        );
    }

    /**
     * Authorize a Permission against the authenticated
     * user's RoleData.
     *
     * The Role Guard has already been checked when this
     * method is called from handle().
     *
     * can() also reaches this method directly, therefore
     * authentication and RoleData are checked safely here.
     */
    private function authorizeRolePermission(
        RoleRouteAccess $routeAccess,
        string $permission,
    ): bool {
        if (! McfAuth::check()) {
            return false;
        }

        $user = McfAuth::user();

        if ($user === null) {
            return false;
        }

        $userRole = McfAccess::resolveRole(
            $user,
        );

        foreach ($routeAccess->roles as $roleData) {
            if ($userRole !== $roleData->role) {
                continue;
            }

            return $this->checkAccess(
                access: $roleData->access,
                permissions: $roleData->permissions,
                permission: $permission,
            );
        }

        return false;
    }

    /**
     * Resolve the Access definition.
     *
     * Supported values:
     *
     * all
     * none
     * only
     * except
     *
     * Unknown values fail safely as none.
     */
    private function checkAccess(
        string $access,
        array $permissions,
        string $permission,
    ): bool {
        $access = strtolower(
            trim($access),
        );

        return match ($access) {
            'all' => true,

            'none' => false,

            'only' => $this->hasPermission(
                permissions: $permissions,
                permission: $permission,
            ),

            'except' => ! $this->hasPermission(
                permissions: $permissions,
                permission: $permission,
            ),

            default => false,
        };
    }

    /**
     * Determine whether a Permission exists
     * in the Permission list.
     */
    private function hasPermission(
        array $permissions,
        string $permission,
    ): bool {
        return in_array(
            $permission,
            $permissions,
            true,
        );
    }
}
