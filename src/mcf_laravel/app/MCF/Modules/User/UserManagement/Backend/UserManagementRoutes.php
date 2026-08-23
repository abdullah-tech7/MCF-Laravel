<?php

use App\MCF\AccessControl\Data\RoleData;
use App\MCF\AccessControl\Data\RoleRouteAccess;
use App\MCF\AccessControl\Data\RoutePermission;
use App\MCF\AccessControl\Registry\McfRouteDataRegistry;
use App\MCF\Modules\User\UserManagement\Backend\UserManagementController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| User Management
|--------------------------------------------------------------------------
*/

Route::get(
    '/userManagement',
    [UserManagementController::class, 'index'],
)
    ->name('user.userManagement.index');

Route::post(
    '/userManagement/{user}/disable',
    [UserManagementController::class, 'disable'],
)
    ->name('user.userManagement.disable');

Route::post(
    '/userManagement/{user}/enable',
    [UserManagementController::class, 'enable'],
)
    ->name('user.userManagement.enable');

Route::post(
    '/userManagement/{user}/delete',
    [UserManagementController::class, 'delete'],
)
    ->name('user.userManagement.delete');

Route::post(
    '/userManagement/{user}/restore',
    [UserManagementController::class, 'restore'],
)
    ->withTrashed()
    ->name('user.userManagement.restore');


/*
|--------------------------------------------------------------------------
| Route Access
|--------------------------------------------------------------------------
|
| Version 3
|
| Each RoutePermission represents one permission and contains
| all routes belonging to that permission.
|
*/

$accessRoutes = [

    new RoleRouteAccess(
        routes: [

            /*
             * View User Management
             */
            new RoutePermission(
                permission: 'View Users',
                routes: [
                    'user.userManagement.index',
                ],
            ),

            /*
             * Disable User
             */
            new RoutePermission(
                permission: 'Disable User',
                routes: [
                    'user.userManagement.disable',
                ],
            ),

            /*
             * Enable User
             */
            new RoutePermission(
                permission: 'Enable User',
                routes: [
                    'user.userManagement.enable',
                ],
            ),

            /*
             * Delete User
             */
            new RoutePermission(
                permission: 'Delete User',
                routes: [
                    'user.userManagement.delete',
                ],
            ),

            /*
             * Restore User
             */
            new RoutePermission(
                permission: 'Restore User',
                routes: [
                    'user.userManagement.restore',
                ],
            ),
        ],

        roles: [
            new RoleData(
                role: 1,
            ),
            new RoleData(
                role: 2,
                access:"only",
                permissions:['View Users'],
            ),
            
        ],
    ),
];

McfRouteDataRegistry::register(
    $accessRoutes,
);