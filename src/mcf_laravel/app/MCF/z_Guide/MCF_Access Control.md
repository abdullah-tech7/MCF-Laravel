# Access Control System

## 1. Overview

The system separates two concerns:

- **Guard**: who can access the Route?
- **Access + Permissions**: what can the user do after route access is allowed?

```text
Guard → Who can access?
Access → How are permissions interpreted?
Permissions → Which permissions are affected?
```

## 2. Guard

Supported values:

```text
any
guest
auth
role
```

### any

Available to everyone:

```php
new AnyRouteAccess(
    routeNames: ['home'],
)
```

### guest

Available to unauthenticated guests:

```php
new GuestRouteAccess(
    routeNames: ['user.auth.login'],
)
```

### auth

Available to authenticated users:

```php
new AuthRouteAccess(
    routeNames: ['user.profile.index'],
)
```

### role

Associated with specific roles:

```php
new RoleRouteAccess(
    routeNames: ['admin.users.index'],
    roles: [
        new RoleData(role: 1),
    ],
)
```

> Guard controls Route access, not Actions.

---

## 3. Access

`access` defines how the `permissions` list is interpreted.

Supported values:

```text
all
none
only
except
```

The default is `all`, so developers normally do not specify it.

### all

All permissions are allowed and `permissions` is completely ignored.

```php
new AuthRouteAccess(
    routeNames: ['users.index'],
)
```

Means:

```text
auth
all
[]
```

Every Permission is allowed.

Even:

```php
access: 'all',
permissions: ['delete'],
```

still means Full Access.

### none

No permissions are allowed.

```php
new AuthRouteAccess(
    routeNames: ['users.index'],
    access: 'none',
)
```

> This does not mean the user is blocked from entering the page. The user may access the page but cannot perform any Action.

### only

Only permissions in the list are allowed:

```php
new AuthRouteAccess(
    routeNames: ['users.index'],
    access: 'only',
    permissions: ['create', 'update'],
)
```

Result:

```text
create → allowed
update → allowed
delete → denied
export → denied
```

`only + []` = `none`.

### except

All permissions are allowed except those in the list:

```php
new AuthRouteAccess(
    routeNames: ['users.index'],
    access: 'except',
    permissions: ['delete'],
)
```

Result:

```text
create → allowed
update → allowed
delete → denied
export → allowed
```

`except + []` = `none`.

---

## 4. Access Summary

| Access | Permissions | Result |
|---|---|---|
| `all` | `[]` | everything |
| `all` | any list | everything; list is ignored |
| `none` | `[]` | nothing |
| `none` | any list | nothing |
| `only` | `[]` | nothing |
| `only` | `[create]` | create only |
| `except` | `[]` | nothing |
| `except` | `[delete]` | everything except delete |

**Security rule:** an empty array must never grant Full Access unless `access = all` is explicitly selected.

---

## 5. RoleData

```php
new RoleData(
    role: 1,
)
```

means `access = all`.

Custom permissions:

```php
new RoleData(
    role: 2,
    access: 'only',
    permissions: ['create', 'update'],
)
```

Exception list:

```php
new RoleData(
    role: 3,
    access: 'except',
    permissions: ['delete'],
)
```

No Actions:

```php
new RoleData(
    role: 4,
    access: 'none',
)
```

### Complete example

```php
new RoleRouteAccess(
    routeNames: ['admin.users.index'],
    roles: [
        new RoleData(role: 1),
        new RoleData(
            role: 2,
            access: 'only',
            permissions: ['view', 'create', 'update'],
        ),
        new RoleData(
            role: 3,
            access: 'none',
        ),
    ],
)
```

Result:

```text
Role 1 → Full Access
Role 2 → view/create/update only
Role 3 → no Permissions
```

---

## 6. Non-Role Routes

`AuthRouteAccess`, `GuestRouteAccess`, and `AnyRouteAccess` use the same Access system.

```php
new AuthRouteAccess(
    routeNames: ['user.profile.updateEmail'],
    access: 'only',
    permissions: ['update'],
)
```

```php
new GuestRouteAccess(
    routeNames: ['guest.example'],
    access: 'only',
    permissions: ['view'],
)
```

```php
new AnyRouteAccess(
    routeNames: ['public.example'],
    access: 'except',
    permissions: ['delete'],
)
```

---

## 7. Permission Checking

There are no fixed methods such as:

```php
canView()
canCreate()
canUpdate()
canDelete()
```

Permission checking is dynamic:

```php
McfAccess::can('view');
McfAccess::can('create');
McfAccess::can('update');
McfAccess::can('delete');
```

New permissions can be introduced without modifying `McfAccess`:

```php
McfAccess::can('export');
McfAccess::can('approve');
McfAccess::can('publish');
McfAccess::can('restore');
```

### Blade

```blade
@if (McfAccess::can('create'))
    <button>Create</button>
@endif
```

```blade
@if (McfAccess::can('delete'))
    <button>Delete</button>
@endif
```

---

## 8. Unknown Access

Any unknown value is treated as `none`:

```php
access: 'something',
```

→ no Permissions.

Access comparison is case-insensitive:

```text
all / ALL / All
none / NONE
only / ONLY
except / EXCEPT
```

Each group represents the same value.

---

## 9. Registries

Registries handle registration and retrieval, not permission interpretation.

```text
McfRouteDataRegistry
route name → RouteAccess

McfRoleDataRegistry
route name + role → RoleData
```

Registering a duplicate Route throws an exception.

Registering a duplicate Role for the same Route throws an exception.

---

## 10. Separation of Responsibilities

```text
McfAccessHandler
    ↓
Guard
    ↓
Can the user access the Route?

McfAccess
    ↓
Access + Permissions
    ↓
What can the user do?
```

Example:

```text
Guard = role
Role = 3
Access = none
```

The user can access the page but cannot perform any Action.

---

## 11. Security Rules

1. Only `all` grants Full Access.
2. `none` denies all Permissions.
3. `only + []` = `none`.
4. `except + []` = `none`.
5. Unknown Access = `none`.
6. `all` ignores `permissions`.
7. An empty array never grants Full Access unless `all` is explicitly selected.
8. Adding a new Permission does not require modifying `McfAccess`.
9. Guard and Permissions are separate layers.
10. Duplicate Routes or Roles must fail during registration.

## 12. Quick Mental Model

```text
Guard
↓
Who can access?

Access
↓
How are permissions interpreted?

Permissions
↓
Which permissions are affected?
```

Example:

```text
Guard: auth
Access: only
Permissions: create, update
```

Meaning: the user must be authenticated and is allowed to perform only Create and Update.

Another example:

```text
Guard: role
Role: 3
Access: except
Permissions: delete
```

Meaning: Role 3 can access the route and has every permission except Delete.


# MCF — Role Access and Action Routes

## Rule

When a route is protected by `RoleRouteAccess` and authorization depends dynamically on the user's Role from the database:

- Register **actual Page Routes only** in `RoleRouteAccess`.
- Do not pass Action/Operation Routes into `routeNames`.
- The Action Route remains a normal Laravel Route, but it performs the required Permission check directly before calling the Controller.

### Page Route

A Page Route represents an actual page and may have a corresponding record in the `pages` table. Therefore, it belongs in `RoleRouteAccess`.

Example:

```php
Route::get('/userManagement', [UserManagementController::class, 'index'])
    ->name('user.userManagement.index');

Route::get('/userManagement/create', [UserManagementController::class, 'create'])
    ->name('user.userManagement.create');

Route::get('/userManagement/{id}/edit', [UserManagementController::class, 'edit'])
    ->whereNumber('id')
    ->name('user.userManagement.edit');
```

Then:

```php
new RoleRouteAccess(
    routeNames: [
        'user.userManagement.index',
        'user.userManagement.create',
        'user.userManagement.edit',
    ],
    roles: [
        new RoleData(
            role: 1,
            access: 'all',
            permissions: [],
        ),
    ],
)
```

## Action Route

An Action Route is not an independent Page. It is an endpoint that performs an operation within a page.

Examples:

```text
store
update
delete
disable
enable
restore
```

These Routes are not registered in `RoleRouteAccess` when their authorization is Role-based and dynamic.

Instead, the Permission is checked directly in the Route before calling the Controller.

Example:

```php
Route::post('/userManagement/{user}/delete', function ($user) {

    if (! McfAccess::can('delete')) {
        abort(403);
    }

    return app(UserManagementController::class)->delete($user);

})->name('user.userManagement.delete');
```

Flow:

```text
Action Route
    ↓
McfAccess::can('delete')
    ↓
Current Role
    ↓
Role Data / RolePage
    ↓
Permission
    ↓
true  → Controller
false → 403
```

## Page vs Permission

Do not confuse a Page Route with a Permission, even when the words are similar.

Example:

```text
user.userManagement.create
```

This is a **Page Route** for the user creation page.

Whereas:

```text
create
```

is a **Permission** that allows the creation operation.

Likewise:

```text
user.userManagement.edit
```

is a Page Route.

Whereas:

```text
update
```

is the Permission for performing an update.

And:

```text
user.userManagement.store
```

is an Action Route that performs the creation operation. It is checked using:

```php
McfAccess::can('create')
```

## When This Rule Does Not Apply

This rule applies specifically to dynamic Role-based access.

If a Route uses `AuthRouteAccess` or another static access type that does not depend on the user's Role, there is no problem with registering different Routes, including Action Routes, directly in `routeNames`.

Example:

```php
new AuthRouteAccess(
    routeNames: [
        'user.profile.index',
        'user.profile.updatePassword',
        'user.profile.updatePasswordPost',
        'user.profile.updateEmail',
        'user.profile.updateEmailPost',
        'user.profile.deleteAccountPost',
    ],
)
```

Authentication is the protection mechanism here, not Role-specific authorization.

## Short Rule

```text
RoleRouteAccess + Dynamic Role
    ↓
Page Routes only
    ↓
Action Routes
    ↓
McfAccess::can(permission)
```

Whereas:

```text
AuthRouteAccess / Static Access
    ↓
Required Routes may be registered directly
    ↓
Action Routes do not need to be separated using this rule
```

## Design Principle

A Page answers:

> Can this Role access this page?

A Permission answers:

> What can the user execute within the page?

Therefore:

```text
Page Route
    → RoleRouteAccess / RolePage

Action Route
    → Permission
```

Do not turn every Action into an independent Page merely to apply Role-based access to it.
