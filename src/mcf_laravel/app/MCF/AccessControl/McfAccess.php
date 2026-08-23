<?php

declare(strict_types=1);

namespace App\MCF\AccessControl;
use App\MCF\AccessControl\Internal\McfAccessHandler;
use App\MCF\Authentication\UserSettings;
use Illuminate\Contracts\Auth\Authenticatable;

final class McfAccess
{
    private function __construct()
    {
    }

    /*
    |--------------------------------------------------------------------------
    | Resolution
    |--------------------------------------------------------------------------
    */

    /**
     * Resolve the authenticated user's Role.
     *
     * Customize this method if the project
     * uses a different Role structure.
     */
    public static function resolveRole(
        Authenticatable $user,
    ): int|string|null {
        return UserSettings::resolveRole(
            $user,
        );
    }

    /**
     * Resolve the Route name used for authentication.
     */
    public static function resolveLoginRouteName(): string
    {
        return UserSettings::resolveLoginRouteName();
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    /**
     * Check whether the current user has
     * the given Permission for the current Route.
     *
     * The Permission name is always supplied by the caller.
     *
     * For Role and Custom Access:
     *
     * Current Route
     *      ↓
     * RoutePermission
     *      ↓
     * Permission name
     *      ↓
     * Access evaluation
     *
     * For Auth, Guest and Any:
     *
     * Route Guard is sufficient and no Permission
     * evaluation is performed.
     */
    public static function can(
        string $permission,
    ): bool {
        return app(
            McfAccessHandler::class,
        )->can(
            $permission,
        );
    }


}
