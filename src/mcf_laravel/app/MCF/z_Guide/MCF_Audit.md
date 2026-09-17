# MCF Audit


The **MCF Audit** module provides structured audit logging for important user operations, using Laravel Eloquent Model Events and Observers.

It can record:

- The user who performed the operation.
- The user role.
- The route.
- The action.
- The operation description.
- IP address.
- User Agent.
- Change details when applicable.

The module also provides direct integration with **MCF Authentication** for authentication events such as login, logout, and failed login, and optional integration with **MCF Notification** for sending a notification after an Audit definition matches.

Audit is shipped with MCF by default. Using `McfAuditable` on a Model enables auditing for that Model.

---

## Core Concept

Using:

```php
use App\MCF\Audit\McfAuditable;

class User extends Authenticatable
{
    use McfAuditable;
}
```

does not mean every Model operation is logged.

The developer explicitly defines the operations to audit with `AuditDefinition`:

```php
new AuditDefinition(
    action: 'update',
    columns: ['email'],
    condition: null,
    message: 'The user updated the email.',
),
```

Only an update affecting `email` matches this rule.

---

## Installation

The Audit migration is shipped with MCF:

```text
0000_create_laravel_tables.php
0001_create_mcf_auth_tables.php
0002_create_mcf_audit_logs_table.php
```

It creates:

```text
audit_logs
```

Run:

```bash
php artisan migrate
```

The table is then ready.

If a project does not need Audit, its components, Model, and migration can be removed and `McfAuditable` can simply remain unused.

---

## Module Structure

```text
app/
└── MCF/
    └── Audit/
        ├── AuditSettings.php
        ├── McfAuditable.php
        ├── McfAuthAudit.php
        ├── Data/
        │   └── AuditDefinition.php
        └── Internal/
            ├── AuditConditionEvaluator.php
            ├── AuditDefinitionValidator.php
            ├── AuditHandler.php
            ├── AuditMatcher.php
            └── AuditObserver.php

app/
└── Models/
    └── AuditLog.php
```

---

# AuditDefinition

`AuditDefinition` represents one Audit rule:

```php
new AuditDefinition(
    action: 'update',
    columns: ['email'],
    condition: null,
    message: 'The user updated the email.',
),
```

### Properties

| Property | Meaning |
|---|---|
| `action` | Eloquent operation |
| `columns` | Changed columns required for `update` |
| `condition` | Optional row condition |
| `message` | Audit description |
| `notification` | Optional callback that builds a `NotificationRequest` after the Audit matches |

---

# Notification Integration

An `AuditDefinition` can optionally be connected to MCF Notification.

The `NotificationRequest` is not created when the Model is defined because the actual Model data is only available when the event occurs.

Instead, `notification` is a callback that receives the Model:

```php
notification: function (
    Model $model,
): NotificationRequest {
    return new NotificationRequest(
        data: NotificationData::passwordUpdated(),
        target: 'users',
        users: [$model->getKey()],
        channels: ['mail'],
    );
},
```

The callback is executed only after:

1. `action` matches.
2. `columns` match when applicable.
3. `condition` passes.
4. The Audit is recorded.

Flow:

```text
Model Event
    ↓
AuditObserver
    ↓
AuditMatcher
    ↓
AuditHandler
    ├── AuditLog
    │
    └── Notification Callback (optional)
            ↓
      NotificationRequest
            ↓
      McfNotification
```

### Actor and Recipient

The user who performed the operation is the **Audit Actor**. The actor is not automatically the Notification recipient.

The recipient can be:

- A specific user.
- A role.
- All users.

For example, when a user changes their password:

```php
target: 'users',
users: [$model->getKey()],
```

sends the notification to that user.

For an event such as creating a Classroom, the Audit Actor may be the administrator who created it while the Notification recipients may be students:

```php
target: 'roles',
roles: [2],
```

---

## Complete User Model Example

The following example demonstrates the Audit + Notification integration:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\MCF\Audit\Data\AuditDefinition;
use App\MCF\Audit\McfAuditable;
use App\MCF\Notification\Internal\NotificationRequest;
use App\MCF\Notification\NotificationData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use McfAuditable, Notifiable;

    public static function auditDefinitions(): array
    {
        return [
            new AuditDefinition(
                action: 'update',
                columns: ['password'],
                condition: null,
                message: 'The user updated the password.',
                notification: function (
                    Model $model,
                ): NotificationRequest {
                    return new NotificationRequest(
                        data: NotificationData::passwordUpdated(),
                        target: 'users',
                        users: [$model->getKey()],
                        channels: ['mail'],
                    );
                },
            ),

            new AuditDefinition(
                action: 'update',
                columns: ['email'],
                condition: null,
                message: 'The user updated the email.',
                notification: function (
                    Model $model,
                ): NotificationRequest {
                    return new NotificationRequest(
                        data: NotificationData::emailUpdated(
                            email: $model->email,
                        ),
                        target: 'users',
                        users: [$model->getKey()],
                        channels: ['mail'],
                    );
                },
            ),

            new AuditDefinition(
                action: 'update',
                columns: ['phone'],
                condition: null,
                message: 'The user updated the phone number.',
                notification: function (
                    Model $model,
                ): NotificationRequest {
                    return new NotificationRequest(
                        data: NotificationData::phoneUpdated(
                            phone: $model->phone,
                        ),
                        target: 'users',
                        users: [$model->getKey()],
                        channels: ['mail'],
                    );
                },
            ),

            new AuditDefinition(
                action: 'update',
                columns: ['email_verified_at'],
                condition: null,
                message: 'The user verified their email address.',
                notification: function (
                    Model $model,
                ): NotificationRequest {
                    return new NotificationRequest(
                        data: NotificationData::emailVerified(),
                        target: 'users',
                        users: [$model->getKey()],
                        channels: ['mail'],
                    );
                },
            ),

            new AuditDefinition(
                action: 'update',
                columns: ['phone_verified_at'],
                condition: null,
                message: 'The user verified their phone number.',
                notification: function (
                    Model $model,
                ): NotificationRequest {
                    return new NotificationRequest(
                        data: NotificationData::phoneVerified(),
                        target: 'users',
                        users: [$model->getKey()],
                        channels: [
                            'sms',
                            'mail',
                        ],
                    );
                },
            ),

            new AuditDefinition(
                action: 'delete',
                columns: [],
                condition: null,
                message: 'The user deleted a user account.',
            ),
        ];
    }
}
```

This example also demonstrates that Notification is not required for every Audit:

```text
password       → Audit + Mail
email          → Audit + Mail
phone          → Audit + Mail
email verified → Audit + Mail
phone verified → Audit + Mail + SMS
delete         → Audit only
```

---

# Actions

Model Audit supports:

```text
create
update
delete
restore
```

Other Actions are ignored.

Authentication operations such as:

```text
login
logout
failed_login
```

are not Model Events, so they are recorded through a separate Authentication integration using the same `AuditLog`.

---

# Action

`action` is checked first.

Supported values:

```php
AuditAction::CREATE
AuditAction::UPDATE
AuditAction::DELETE
AuditAction::RESTORE
```

If the current event does not match the definition, nothing is recorded and its Notification callback is not executed.

---

# Columns

`columns` means the columns affected by an `update`.

Example:

```php
new AuditDefinition(
    action: 'update',
    columns: ['email'],
    condition: null,
    message: 'The user updated the email.',
),
```

If:

```text
email
name
```

changed, the rule matches.

If:

```text
name
phone
```

changed, it does not.

If:

```php
columns: []
```

the update is not restricted to a particular column.

### Create and Delete

For `create` and `delete`, `columns` is skipped completely. Evaluation proceeds directly to `condition`.

---

## Condition

A condition restricts when an Audit Definition is applied.

MCF supports two condition types:

1. **Array condition** — checks Model column values.
2. **Closure condition** — evaluates custom logic using the Model instance.

---

### Array Condition

Use an array condition when the Audit depends directly on one or more column values from the Model's table.

Example:

```php
condition: [
    'role_id' => 5,
],
````

This means the Audit is applied only when the configured column matches the configured value.

Multiple columns can be configured:

```
condition: [
    'role_id' => 5,
    'is_active' => true,
],
```

All configured conditions must match.

#### Column Validation

Every column used in an array condition must exist in the Model's database table.

A missing configured column is considered a configuration error and must throw an `Exception` during Audit Definition validation.

Example:

```
condition: [
    'unknown_column' => true,
],
```

If `unknown_column` does not exist in the Model's table, the Audit Definition is invalid.

This validation ensures configuration errors are detected during development instead of silently preventing the Audit from being applied.

***

### Closure Condition

Use a Closure condition when the Audit requires custom logic that cannot be expressed as simple column-value matching.

The Closure receives the Model instance and must return a boolean.

Example:

```
condition: fn(Model $model): bool =>
    $model->created_by === McfAuth::id()
    && McfAuth::user()?->role_id === 3,
```

The Audit is applied only when the Closure returns:

```
true
```

If the Closure returns:

```
false
```

the Audit Definition is skipped.

Closures are useful when the condition depends on:

* Multiple Model values
* The authenticated user
* Relationships or related data
* Runtime application state
* Custom business rules

Example:

```
condition: fn(Model $model): bool =>
    $model->created_by === McfAuth::id(),
```

Another example:

```
condition: fn(Model $model): bool =>
    $model->created_by === McfAuth::id()
    && McfAuth::user()?->role_id === 2,
```

***

### No Condition

If the Audit Definition should always be applicable, use:

```
condition: null,
```

No condition means that no additional condition is applied to the Audit Definition.

***

### Choosing the Condition Type

Use an **array condition** when the requirement is a direct comparison against Model columns:

```
condition: [
    'status_id' => 1,
],
```

Use a **Closure condition** when the requirement contains custom logic that cannot be represented as simple column-value matching:

```
condition: fn(Model $model): bool =>
    $model->created_by === McfAuth::id()
    && McfAuth::user()?->role_id === 3,
```

Use:

```
condition: null,
```

when no condition is required.

***

### Condition Types Summary

| Condition | Purpose                              |
| --------- | ------------------------------------ |
| `null`    | Always applicable                    |
| `array`   | Direct Model column-value matching   |
| `Closure` | Custom runtime logic using the Model |

Examples:

```
// No condition
condition: null,
```

```
// Column-based condition
condition: [
    'status_id' => 1,
],
```

```
// Custom condition
condition: fn(Model $model): bool =>
    $model->created_by === McfAuth::id()
    && McfAuth::user()?->role_id === 3,
```

### Condition Behavior

```
condition: null
        ↓
Audit Definition is applicable


condition: [column => value]
        ↓
MCF checks the configured Model column(s)
        ↓
All conditions match
        ↓
Audit Definition is applicable


condition: fn(Model $model): bool => ...
        ↓
MCF passes the Model instance to the Closure
        ↓
Closure returns true / false
        ↓
true  → Audit Definition is applicable
false → Audit Definition is skipped
```


---

# Matching Sequence

```text
Action
  ↓
Match?
  ↓
update / restore?
  ├── yes → Columns
  │          ├── empty → Continue
  │          └── configured → one must be affected
  │
  └── create/delete → Skip Columns
              ↓
           Condition
              ↓
            Match
              ↓
         AuditHandler
          ├── AuditLog
          └── Notification (optional)
```

A failed rule does not stop other rules.

A configuration error such as a missing column should throw an Exception.

---

# Using McfAuditable

```php
use App\MCF\Audit\McfAuditable;
use App\MCF\Audit\Data\AuditDefinition;

class User extends Authenticatable
{
    use McfAuditable;

    public static function auditDefinitions(): array
    {
        return [
            new AuditDefinition(
                action: 'update',
                columns: ['password'],
                condition: null,
                message: 'The user updated the password.',
            ),

            new AuditDefinition(
                action: 'update',
                columns: ['email'],
                condition: null,
                message: 'The user updated the email.',
            ),

            new AuditDefinition(
                action: 'update',
                columns: ['email_verified_at'],
                condition: null,
                message: 'The user verified their email address.',
            ),

            new AuditDefinition(
                action: 'delete',
                columns: [],
                condition: null,
                message: 'The user deleted a user account.',
            ),
        ];
    }
}
```

---

# Update

When:

```php
$user->email = 'new@example.com';
$user->save();
```

Laravel emits the `updated` event.

`AuditObserver` obtains the changed columns from Eloquent and passes them to the matcher.

Example:

```text
changed columns:
[
    "name",
    "email"
]
```

Definition:

```php
columns: ['email']
```

Result:

```text
MATCH
```

After the match, the Audit is recorded and the optional Notification callback is executed.

---

# Create

Example:

```php
$user = User::create([
    'name' => 'Audit Test User',
    'email' => 'audit-test@example.com',
    'password' => Hash::make('Password123!'),
    'role_id' => 5,
]);
```

Definition:

```php
new AuditDefinition(
    action: 'create',
    columns: [],
    condition: [
        'role_id' => 5,
    ],
    message: 'The user created a user with role 5.',
),
```

Flow:

```text
create
  ↓
columns skipped
  ↓
role_id == 5
  ↓
Audit
```

---

# Delete

```php
$user = User::findOrFail(5);
$user->delete();
```

Definition:

```php
new AuditDefinition(
    action: 'delete',
    columns: [],
    condition: null,
    message: 'The user deleted a user account.',
),
```

---

# Authentication Audit

Authentication operations do not depend on Eloquent Model Events.

They are recorded through `McfAuthAudit`, which integrates directly with `McfAuth`.

Successful login:

```php
McfAuthAudit::record(
    action: 'login',
    description: __('The user logged in.'),
);
```

Logout:

```php
McfAuthAudit::record(
    action: 'logout',
    description: __('The user logged out.'),
);
```

Failed login:

```php
McfAuthAudit::record(
    action: 'failed_login',
    description: __('The user failed to log in.'),
);
```

If Authentication Audit is disabled through `AuditSettings`, `McfAuthAudit` records nothing.

No Listener or Provider is required for these operations; `McfAuthAudit` is called directly from the Authentication flow when the operation succeeds or fails.

---

---

# Account Operations Audit

MCF Account operations can be audited through the same `McfAuditable` mechanism used for other User Model operations.

The account-management operations covered by this integration are:

```text
disable
  → The account is disabled by an authorized actor.

enable
  → The account is enabled by an authorized actor.

delete
  → The account is soft-deleted, either by the account owner or by an authorized actor.

restore
  → The soft-deleted account is restored, either by the account owner or by an authorized actor.
```

The important distinction is the **Audit Actor** versus the **account owner**. The Audit Actor is the authenticated user who performed the operation. For example, when an administrator disables another user's account, the administrator is the Actor and the affected user is the Model being audited.

For self-service deletion or restoration, the Actor and the affected user are normally the same user.

### Account Notifications

Account Audit definitions may optionally create a `NotificationRequest` for the affected account owner:

```text
Account disabled
    → notify the affected user

Account enabled
    → notify the affected user

Account deleted
    → notify the affected user when the operation is performed by another actor,
      or use the self-deletion notification when the owner deleted the account

Account restored
    → notify the affected user
```

The notification recipient is resolved independently from the Audit Actor. The recommended recipient for these account-state notifications is the affected user's own account.

### Self vs Actor Account Operations

When the account operation can be initiated either by the account owner or by an authorized actor, the Audit description should make the source of the action unambiguous.

Examples:

```text
The account was disabled by an authorized actor.
The account was enabled by an authorized actor.
The account was deleted by the account owner.
The account was deleted by an authorized actor.
The account was restored by the account owner.
The account was restored by an authorized actor.
```

The exact wording and `AuditDefinition` conditions should follow the project's account fields, such as the deletion type used to distinguish self-deletion from actor deletion.

### Restore Events

Account restoration is different from an ordinary `update`: Laravel emits the Eloquent `restored` event when a Soft Deleted Model is restored.

Therefore, if the Audit implementation supports restoration as an explicit action, `restore` must be treated as a first-class Audit action rather than relying on an `updated` definition for `deleted_at`.

Flow:

```text
Account Restore
    ↓
Eloquent restored event
    ↓
AuditObserver
    ↓
AuditMatcher
    ↓
AuditHandler
    ├── AuditLog
    └── Notification (optional)
```

This keeps account restoration auditable even though the account is no longer soft-deleted after the operation.


# Audit Data

An update may contain change details:

```json
{
    "changes": {
        "name": {
            "old": "Ahmed",
            "new": "Mohammed"
        }
    }
}
```

In `AuditLog`:

```php
protected $casts = [
    'data' => 'array',
    'created_at' => 'datetime',
];
```

The UI can read:

```php
$log->data
```

and:

```php
$log->data['changes']
```

to display changed columns and old/new values.

---

# Sensitive Data

Sensitive column values can be excluded from `data` through `AuditSettings`:

```php
public static array $excludedDataColumns = [
    'users' => [
        'password',
        'remember_token',
    ],
];
```

This does not disable the Audit itself.

For example:

```php
columns: ['password']
```

still records:

```text
action = update
message = The user updated the password.
```

but the old and new password values are not stored in `data`.

If all changed columns are excluded:

```text
data = null
```

The distinction is:

```text
AuditDefinition.columns
    → Does the operation match the rule?

AuditSettings.$excludedDataColumns
    → May the column value be stored in data?
```

---

# AuditSettings

`AuditSettings` is the customization point for Audit.

It controls, among other things:

- The Audit storage Model.
- How the user role is resolved.
- Whether Authentication Audit is enabled.
- Which column values are excluded from `data`.

Example:

```php
public static function auditModel(): string
{
    return AuditLog::class;
}

public static function resolveRole(
    Authenticatable $user,
): int|string|null {
    return $user->role_id;
}

public static bool $authentication = true;

public static array $excludedDataColumns = [
    'users' => [
        'password',
        'remember_token',
    ],
];
```

`auditModel()` defines the storage Model.

`resolveRole()` defines how Audit resolves the user's role independently from Authentication and Access Control.

`authentication` controls Authentication event auditing.

`excludedDataColumns` defines values that must not be stored in `data`.

---

# AuditLog

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class AuditLog extends Model
{
    protected $table = 'audit_logs';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'user_role',
        'route_name',
        'action',
        'description',
        'data',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
    ];
}
```

There is no `updated_at` because the Audit record is historical.

---

# audit_logs Table

```text
id
user_id
user_role
route_name
action
description
data
ip_address
user_agent
created_at
```

`user_id` is nullable.

The table intentionally does not use a foreign key that would remove the historical record when the user is deleted. Audit should preserve historical evidence.

---

# Route, IP, and User Agent

For HTTP requests, the Audit can record:

```text
route_name
ip_address
user_agent
```

Example:

```text
route_name:
user.profile.updatePasswordPost

ip_address:
192.168.1.133

user_agent:
Mozilla/5.0 ...
```

The Route provides context; the Eloquent Action remains the source of the Model Audit operation.

---

# Authentication and Access Control

**Access Control** answers:

```text
Is the user allowed?
```

**Audit** answers:

```text
What happened?
```

Example:

```text
Access Control
    ↓
Operation allowed
    ↓
Model operation
    ↓
AuditObserver
    ↓
AuditLog
```

Audit must not replace authorization.

Authentication is responsible for authentication events and uses `McfAuthAudit` for:

```text
login
logout
failed_login
```

while using the same `AuditLog`.

---

# Audit and Notification

The integration between the two modules is optional.

Having:

```php
notification: ...
```

means:

```text
Audit + Notification
```

while omitting it means:

```text
Audit only
```

A Notification failure does not cancel the Audit record.

Notification channels are also independent: a Mail failure does not prevent Database or SMS, and a failure for one user does not prevent delivery to other users. Operational delivery failures are logged, while invalid `NotificationRequest` configuration remains a developer-facing configuration error.

---

# Best Practices

- Do not log every Model operation without a reason.
- Use `columns` for important Updates.
- Use `condition` when an operation must be restricted by row values.
- Never store passwords, tokens, or secrets in `data`.
- Use `excludedDataColumns`.
- Keep `message` clear and suitable for an administration UI.
- Do not manually call `AuditLog::create()` in every Controller for Model operations.
- Do not use Audit as a replacement for Access Control.
- Use `McfAuthAudit` for Authentication events instead of treating them as Model Events.
- Treat account restoration as a dedicated `restore` Audit action when the implementation exposes the Eloquent `restored` event.
- Keep the Audit Actor separate from the affected account and Notification recipient.
- Use a Notification callback only when an actual notification is required.
- Do not assume the Audit Actor is the Notification Recipient.
- Treat missing database columns in definitions as configuration errors.

---

# Independence

Audit is shipped with MCF by default, but its use is not mandatory.

A project that does not need Audit can remove:

- `McfAuditable`
- `McfAuthAudit`
- `AuditDefinition`
- Audit components
- `AuditLog`
- the `audit_logs` migration

A project that uses `McfAuditable` gets automatic auditing according to the definitions explicitly declared by the developer.
