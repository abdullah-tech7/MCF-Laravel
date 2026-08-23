# MCF Access Control

## 1. Overview

MCF Access Control is a framework-level authorization system for protecting Laravel Routes and checking application Permissions.

It is intentionally independent from the application's data source and business modules.

Authorization data may come from:

- Route files
- Configuration
- Generated application data
- A database
- Another service
- Any other source chosen by the developer

MCF Access Control does not care where the data came from.

The developer is responsible for constructing the appropriate Access Control data.

The framework is responsible for interpreting and enforcing that data.

MCF Access Control does not require:

- A specific database schema
- A Page model
- A RolePage model
- Dynamic Data
- A specific application module

These are application-level concerns.

---

# 2. Core Concepts

The system is based on:

```text
Route
Guard
Access
Permission
Role
```

Each concept has a separate responsibility.

```text
Guard
    ↓
Who is allowed to access the Route?

Role
    ↓
Which Role receives a Role-based Route Access definition?

Permission
    ↓
What capability is being granted?

Access
    ↓
How is the Permission list interpreted?
```

A Route and a Permission are not the same thing.

Example:

```text
Route:
user.userManagement.store

Permission:
create
```

The developer decides which Routes belong to which Permissions.

---

# 3. Route Access Data Types

MCF provides the following Route Access data types:

```text
AnyRouteAccess
GuestRouteAccess
AuthRouteAccess
RoleRouteAccess
CustomRouteAccess
```

They are not interchangeable.

Use the simplest type that matches the authorization requirement.

---

# 4. AnyRouteAccess

`AnyRouteAccess` is used when a Route does not require authentication or Role authorization.

It receives Route names directly.

```php
new AnyRouteAccess(
    routeNames: [
        'home',
        'about',
    ],
)
```

Model:

```text
AnyRouteAccess
    ↓
routeNames[]
```

It does not use:

```text
RoleData
RoutePermission
Access
Permissions
```

If the Route requires a Permission-based authorization model, use a more appropriate Access type.

---

# 5. GuestRouteAccess

`GuestRouteAccess` is used for Routes intended for guest users.

It receives Route names directly.

```php
new GuestRouteAccess(
    routeNames: [
        'auth.login',
        'auth.register',
    ],
)
```

Model:

```text
GuestRouteAccess
    ↓
routeNames[]
```

It does not use the Role Permission structure.

---

# 6. AuthRouteAccess

`AuthRouteAccess` is used for Routes that require an authenticated user.

It receives Route names directly.

```php
new AuthRouteAccess(
    routeNames: [
        'profile.index',
        'profile.settings',
    ],
)
```

Model:

```text
AuthRouteAccess
    ↓
routeNames[]
```

It does not receive:

```text
access
permissions
RoutePermission
RoleData
```

Do not use:

```php
new AuthRouteAccess(
    routeNames: [
        'profile.index',
    ],
    access: 'only',
    permissions: [
        'view',
    ],
)
```

That is not the intended model.

If an authenticated Route requires the Permission model described below, use `CustomRouteAccess`.

---

# 7. CustomRouteAccess

`CustomRouteAccess` provides direct Guard + Route Permission authorization without RoleData.

Its structure is:

```text
CustomRouteAccess
    ├── routes: RoutePermission[]
    ├── guard: GuardType
    ├── access: string
    └── permissions: string[]
```

The constructor is:

```php
new CustomRouteAccess(
    routes: [
        // RoutePermission[]
    ],

    guard: GuardType::AUTH,

    access: 'all',

    permissions: [],
)
```

The developer provides the Guard directly.

For example:

```php
new CustomRouteAccess(
    routes: [

        new RoutePermission(
            permission: 'view',
            routes: [
                'user.userManagement.index',
            ],
        ),

        new RoutePermission(
            permission: 'create',
            routes: [
                'user.userManagement.create',
                'user.userManagement.store',
            ],
        ),

    ],

    guard: GuardType::AUTH,

    access: 'only',

    permissions: [
        'view',
        'create',
    ],
)
```

This means:

```text
Guard:
AUTH

Allowed Permissions:
view
create

Routes:
view
    → user.userManagement.index

create
    → user.userManagement.create
    → user.userManagement.store
```

The important distinction is:

```text
CustomRouteAccess
    = Guard + RoutePermission[] + Access + Permissions
```

There is no `RoleData`.

---

# 8. RoleRouteAccess

`RoleRouteAccess` is used when authorization depends on Roles.

Its structure is:

```text
RoleRouteAccess
    ├── routes: RoutePermission[]
    └── roles: RoleData[]
```

Example:

```php
new RoleRouteAccess(
    routes: [

        new RoutePermission(
            permission: 'view',
            routes: [
                'user.userManagement.index',
            ],
        ),

        new RoutePermission(
            permission: 'create',
            routes: [
                'user.userManagement.create',
                'user.userManagement.store',
            ],
        ),

    ],

    roles: [

        new RoleData(
            role: 1,
        ),

    ],
)
```

Conceptually:

```text
RoleRouteAccess
    ↓
RoutePermission[]
    ↓
RoleData[]
```

The Role-based definition is where Role-specific Access and Permission configuration is represented through `RoleData`.

---

# 9. RoleData

`RoleData` represents the Role configuration used by `RoleRouteAccess`.

Basic example:

```php
new RoleData(
    role: 1,
)
```

This uses the default Access configuration.

A restricted Role can define Access and Permissions:

```php
new RoleData(
    role: 2,
    access: 'only',
    permissions: [
        'view',
        'create',
        'update',
    ],
)
```

The resulting model is:

```text
Role 2
    ↓
only
    ↓
view
create
update
```

---

# 10. RoutePermission

`RoutePermission` connects a Permission with one or more Routes.

Example:

```php
new RoutePermission(
    permission: 'create',
    routes: [
        'user.userManagement.create',
        'user.userManagement.store',
    ],
)
```

The Permission name does not need to match any Route name.

For example:

```text
Route:
user.userManagement.store

Permission:
create
```

is valid.

The relationship is explicitly defined by the developer.

---

# 11. One Permission Can Own Multiple Routes

A single capability may require multiple Laravel Routes.

Example:

```php
new RoutePermission(
    permission: 'update',
    routes: [
        'users.edit',
        'users.update',
    ],
)
```

Both Routes belong to:

```text
update
```

Another example:

```php
new RoutePermission(
    permission: 'create',
    routes: [
        'users.create',
        'users.store',
    ],
)
```

This is useful when a Page Route and an Action Route represent the same application capability.

---

# 12. Multiple RoutePermissions

A Route Access definition can contain multiple permissions.

```php
new RoleRouteAccess(
    routes: [

        new RoutePermission(
            permission: 'view',
            routes: [
                'users.index',
            ],
        ),

        new RoutePermission(
            permission: 'create',
            routes: [
                'users.create',
                'users.store',
            ],
        ),

        new RoutePermission(
            permission: 'update',
            routes: [
                'users.edit',
                'users.update',
            ],
        ),

        new RoutePermission(
            permission: 'delete',
            routes: [
                'users.delete',
            ],
        ),

    ],

    roles: [
        new RoleData(
            role: 1,
        ),
    ],
)
```

The model becomes:

```text
view
    → users.index

create
    → users.create
    → users.store

update
    → users.edit
    → users.update

delete
    → users.delete
```

---

# 13. Access

Access determines how a Permission list is interpreted.

Supported values:

```text
all
none
only
except
```

These values are used by the Permission-aware Route Access models.

They are relevant to:

```text
RoleRouteAccess
CustomRouteAccess
```

They are not parameters of:

```text
AnyRouteAccess
GuestRouteAccess
AuthRouteAccess
```

---

# 14. Access: all

`all` means:

```text
All Permissions are allowed.
```

Example:

```php
new RoleData(
    role: 1,
    access: 'all',
)
```

Result:

```text
All Permissions
    ↓
Allowed
```

The empty Permission list with `all` still means Full Access:

```text
all + []
    = Full Access
```

Only explicit `all` grants Full Access.

---

# 15. Access: none

`none` means:

```text
No Permissions are allowed.
```

Example:

```php
new RoleData(
    role: 2,
    access: 'none',
)
```

Result:

```text
view    → denied
create  → denied
update  → denied
delete  → denied
```

---

# 16. Access: only

`only` means:

```text
Only the listed Permissions are allowed.
```

Example:

```php
new RoleData(
    role: 3,
    access: 'only',
    permissions: [
        'view',
        'create',
        'update',
    ],
)
```

Result:

```text
view    → allowed
create  → allowed
update  → allowed
delete  → denied
```

An empty Permission list means:

```text
only + []
    = No Permissions
```

---

# 17. Access: except

`except` means:

```text
All Permissions except the listed Permissions are allowed.
```

Example:

```php
new RoleData(
    role: 4,
    access: 'except',
    permissions: [
        'delete',
    ],
)
```

Result:

```text
view    → allowed
create  → allowed
update  → allowed
delete  → denied
```

An empty Permission list means:

```text
except + []
    = No Permissions
```

This avoids treating an empty exclusion list as Full Access.

---

# 18. Access Summary

| Access | Permissions | Result |
|---|---|---|
| `all` | `[]` | All Permissions |
| `all` | any list | All Permissions |
| `none` | `[]` | No Permissions |
| `none` | any list | No Permissions |
| `only` | `[]` | No Permissions |
| `only` | `[create]` | Create only |
| `only` | `[create, update]` | Create and Update |
| `except` | `[]` | No Permissions |
| `except` | `[delete]` | Everything except Delete |

Security rule:

```text
Only `all` grants Full Access.
```

---

# 19. Permission Checking

Permissions are developer-defined.

MCF does not require a fixed Permission vocabulary.

The application can check:

```php
McfAccess::can('view');

McfAccess::can('create');

McfAccess::can('update');

McfAccess::can('delete');
```

It can also define application-specific capabilities:

```php
McfAccess::can('approve');

McfAccess::can('publish');

McfAccess::can('restore');

McfAccess::can('export');

McfAccess::can('archive');
```

The same Permission name must be used consistently between the Access Control definition and the application check.

Example:

```php
new RoutePermission(
    permission: 'approve',
    routes: [
        'orders.approve',
    ],
)
```

Then:

```php
McfAccess::can('approve');
```

---

# 20. Blade Usage

Example:

```blade
@if (McfAccess::can('create'))
    <button type="button">
        Create
    </button>
@endif
```

Another example:

```blade
@if (McfAccess::can('delete'))
    <button type="button">
        Delete
    </button>
@endif
```

The UI does not need to know where the Permission data came from.

It only asks MCF whether the Permission is allowed.

---

# 21. Route vs Permission

Do not confuse a Route with a Permission.

A Route identifies a Laravel endpoint:

```text
users.store
```

A Permission identifies a capability:

```text
create
```

The developer may map them:

```php
new RoutePermission(
    permission: 'create',
    routes: [
        'users.create',
        'users.store',
    ],
)
```

Therefore:

```text
Routes
    ↓
Technical endpoints

Permissions
    ↓
Application capabilities
```

This separation allows multiple endpoints to represent one capability.

---

# 22. Page Routes and Action Routes

MCF does not impose a Page/Action architecture.

However, an application may naturally have:

```text
Page Route
    ↓
Displays UI

Action Route
    ↓
Performs an operation
```

Example:

```text
users.index
users.create
users.edit
users.store
users.update
users.delete
```

The developer may map them to capabilities:

```text
view
    → users.index

create
    → users.create
    → users.store

update
    → users.edit
    → users.update

delete
    → users.delete
```

MCF Access Control only understands the definitions supplied to it.

It does not require the application to use Pages, Page models, or a database.

---

# 23. CustomRouteAccess vs RoleRouteAccess

These two models are similar in Permission behavior but differ in how authorization is assigned.

## RoleRouteAccess

Uses Roles:

```text
RoutePermission[]
+
RoleData[]
```

Example:

```php
new RoleRouteAccess(
    routes: [
        new RoutePermission(
            permission: 'view',
            routes: [
                'users.index',
            ],
        ),
    ],
    roles: [
        new RoleData(
            role: 1,
        ),
    ],
)
```

## CustomRouteAccess

Uses a Guard directly:

```text
RoutePermission[]
+
GuardType
+
Access
+
Permissions
```

Example:

```php
new CustomRouteAccess(
    routes: [
        new RoutePermission(
            permission: 'view',
            routes: [
                'users.index',
            ],
        ),
    ],

    guard: GuardType::AUTH,

    access: 'only',

    permissions: [
        'view',
    ],
)
```

Therefore:

```text
RoleRouteAccess
    → Role-based

CustomRouteAccess
    → Direct Guard-based Permission authorization
```

There is no `RoleData` inside `CustomRouteAccess`.

---

# 24. Choosing the Correct Type

## Public Route

```php
new AnyRouteAccess(
    routeNames: [
        'home',
    ],
)
```

## Guest Route

```php
new GuestRouteAccess(
    routeNames: [
        'auth.login',
    ],
)
```

## Authenticated Route Without Permission Model

```php
new AuthRouteAccess(
    routeNames: [
        'profile.index',
    ],
)
```

## Role-based Permission Authorization

```php
new RoleRouteAccess(
    routes: [
        new RoutePermission(
            permission: 'view',
            routes: [
                'users.index',
            ],
        ),
    ],
    roles: [
        new RoleData(
            role: 1,
        ),
    ],
)
```

## Direct Guard + Permission Authorization

```php
new CustomRouteAccess(
    routes: [
        new RoutePermission(
            permission: 'view',
            routes: [
                'users.index',
            ],
        ),
    ],
    guard: GuardType::AUTH,
    access: 'only',
    permissions: [
        'view',
    ],
)
```

---

# 25. Registry

Route Access definitions are registered through:

```php
McfRouteDataRegistry::register(
    $accessRoutes,
);
```

Example:

```php
$accessRoutes = [

    new RoleRouteAccess(
        routes: [

            new RoutePermission(
                permission: 'view',
                routes: [
                    'admin.users.index',
                ],
            ),

        ],

        roles: [
            new RoleData(
                role: 1,
            ),
        ],
    ),
];

McfRouteDataRegistry::register(
    $accessRoutes,
);
```

The Registry is responsible for registration and retrieval of Access Control definitions.

It is not responsible for application-specific business logic.

---

# 26. Data Source Independence

MCF Access Control does not care whether the final data was produced from:

```text
PHP Route definitions
Configuration
Database
Generated data
API
Another service
```

For example, the developer may define:

```php
new RoleData(
    role: 1,
)
```

directly in a Route file.

Another application may construct the same object from its own data source.

MCF Access Control only receives the resulting framework data objects.

This separation is intentional.

---

# 27. No Database Dependency

MCF Access Control itself does not require:

```text
roles table
permissions table
pages table
role_pages table
```

or any other specific schema.

The framework only defines the Access Control structures and their behavior.

An application may choose to persist its authorization information in a database, but that persistence mechanism is outside the core Access Control concept.

---

# 28. No Dynamic Data Dependency

MCF Access Control does not require Dynamic Data.

If an application has dynamic authorization data, the application may convert that data into the MCF Access Control structures.

If an application uses static definitions, it may define them directly.

Both are valid.

MCF Access Control remains independent of the source.

---

# 29. Security Rules

The core security rules are:

1. `all` is the only Access value that grants Full Access.
2. `none` grants no Permissions.
3. `only + []` grants no Permissions.
4. `except + []` grants no Permissions.
5. Unknown Access values must fail securely.
6. `AnyRouteAccess`, `GuestRouteAccess`, and `AuthRouteAccess` remain simple Route-name definitions.
7. Permission-aware Guard behavior belongs to `CustomRouteAccess` or `RoleRouteAccess`.
8. `CustomRouteAccess` has no `RoleData`.
9. `RoleRouteAccess` uses `RoleData`.
10. A Permission name does not need to match a Route name.
11. Multiple Routes can belong to one Permission.
12. MCF Access Control does not require a database.
13. MCF Access Control does not require Dynamic Data.
14. MCF Access Control does not depend on RolePage or any application-specific module.
15. Authorization data may originate from any source chosen by the developer.
16. Conflicting duplicate Route Access definitions must not silently overwrite each other.

---

# 30. Complete Example

```php
<?php

use App\MCF\AccessControl\Data\RoleData;
use App\MCF\AccessControl\Data\RoleRouteAccess;
use App\MCF\AccessControl\Data\RoutePermission;
use App\MCF\AccessControl\Registry\McfRouteDataRegistry;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
*/

Route::get(
    '/users',
    [UserController::class, 'index'],
)
    ->name('users.index');

Route::get(
    '/users/create',
    [UserController::class, 'create'],
)
    ->name('users.create');

Route::post(
    '/users',
    [UserController::class, 'store'],
)
    ->name('users.store');

Route::get(
    '/users/{user}/edit',
    [UserController::class, 'edit'],
)
    ->name('users.edit');

Route::put(
    '/users/{user}',
    [UserController::class, 'update'],
)
    ->name('users.update');

Route::delete(
    '/users/{user}',
    [UserController::class, 'delete'],
)
    ->name('users.delete');


/*
|--------------------------------------------------------------------------
| Access
|--------------------------------------------------------------------------
*/

$accessRoutes = [

    new RoleRouteAccess(

        routes: [

            new RoutePermission(
                permission: 'view',
                routes: [
                    'users.index',
                ],
            ),

            new RoutePermission(
                permission: 'create',
                routes: [
                    'users.create',
                    'users.store',
                ],
            ),

            new RoutePermission(
                permission: 'update',
                routes: [
                    'users.edit',
                    'users.update',
                ],
            ),

            new RoutePermission(
                permission: 'delete',
                routes: [
                    'users.delete',
                ],
            ),

        ],

        roles: [

            /*
             * Full Access
             */
            new RoleData(
                role: 1,
            ),

            /*
             * View + Create + Update
             */
            new RoleData(
                role: 2,
                access: 'only',
                permissions: [
                    'view',
                    'create',
                    'update',
                ],
            ),

            /*
             * View only
             */
            new RoleData(
                role: 3,
                access: 'only',
                permissions: [
                    'view',
                ],
            ),

        ],
    ),
];


McfRouteDataRegistry::register(
    $accessRoutes,
);
```

Result:

```text
Role 1
    Full Access

Role 2
    view
    create
    update

Role 3
    view
```

The application can then use:

```php
McfAccess::can('view');

McfAccess::can('create');

McfAccess::can('update');

McfAccess::can('delete');
```

---

# 31. Mental Model

The framework can be understood as:

```text
Route
    ↓
Choose Route Access Type
    │
    ├── Any
    │     └── routeNames[]
    │
    ├── Guest
    │     └── routeNames[]
    │
    ├── Auth
    │     └── routeNames[]
    │
    ├── Custom
    │     ├── Guard
    │     ├── RoutePermission[]
    │     ├── Access
    │     └── Permissions[]
    │
    └── Role
          ├── RoutePermission[]
          └── RoleData[]

RoutePermission
    ↓
Permission
    ↓
Routes[]

RoleData / Custom Access
    ↓
Access
    ↓
Allowed Permissions

McfAccess::can()
    ↓
Permission decision
```

The central principle is:

```text
MCF Access Control does not care where the data came from.

It only cares that the developer supplies the correct
Access Control definition.
```

Application-specific systems may build or persist that data however they choose.
