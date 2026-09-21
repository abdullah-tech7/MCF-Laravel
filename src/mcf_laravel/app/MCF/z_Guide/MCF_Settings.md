# MCF Settings

## 1. System Definition

`MCF Settings` is a settings system built into the MCF framework. It is not merely a UI page or a single database table.

The framework provides the complete infrastructure required to define settings, store their definitions, store user values, resolve effective values, validate changes, update settings, and reset them to their defaults.

The system is based on **two related database tables**:

```text
mcf_setting_data
       │
       │  Setting Definition
       ▼
mcf_settings
       │
       │  User Value / Override
       ▼
Effective Value
```

The first table contains **stable setting definitions provided by the framework**, while the second contains **values changed by users**.

---

# 2. MCF Settings Components

The system consists of four main parts:

```text
1. Setting Definition
2. mcf_setting_data
3. mcf_settings
4. Settings API / Service Layer
```

The frontend is not the source of setting definitions.

The Backend / Framework defines:

- available settings
- setting type
- default value
- options
- category
- visible roles

The UI consumes this information and renders a dynamic settings interface.

---

# 3. Framework-Provided Migrations

MCF Settings includes **framework-level migrations** for creating its Settings tables.

Projects using MCF Settings do not need to redesign the Settings database structure from scratch.

After the framework is installed and its migrations are executed, the Settings infrastructure is created according to the framework standard.

Conceptually:

```text
MCF Framework
      │
      ├── Migration: mcf_setting_data
      │
      └── Migration: mcf_settings
```

These tables are part of the framework infrastructure.

Therefore, every project using MCF Settings can rely on the same standard Settings architecture.

---

# 4. First Table: mcf_setting_data

`mcf_setting_data` is the **setting-definition table**.

It does not store user selections.

Its responsibility is to answer:

```text
What is this setting?
```

not:

```text
What did this user choose?
```

A setting definition contains information such as:

```text
category
key
name
subtitle
type
options
default value
roles
```

These values describe the setting itself.

---

# 5. Second Table: mcf_settings

`mcf_settings` is the **user-value table**.

Its responsibility is to answer:

```text
What value did this user choose?
```

It does not redefine the setting.

The definition remains in:

```text
mcf_setting_data
```

while the user value is stored in:

```text
mcf_settings
```

---

# 6. Relationship Between the Tables

The logical relationship is:

```text
mcf_setting_data
       │
       │ setting definition
       │
       └───────────────┐
                       │
                       ▼
                mcf_settings
                       │
                       │ user override
                       ▼
                 Effective Value
```

Each user-setting record references a setting definition and the user whose value it represents.

Conceptually:

```text
Setting Definition
        +
User
        +
User Value
```

---

# 7. Why Two Tables?

The separation is intentional.

Setting definitions are **framework definition data**, while user settings are **mutable user data**.

Example:

```text
Setting Definition:

key = items_per_page
type = number
default = 20
```

This definition does not change when a user changes the value.

If a user changes it to:

```text
50
```

the framework does not update:

```text
mcf_setting_data
```

Instead, it stores:

```text
50
```

in:

```text
mcf_settings
```

---

# 8. Static Definition Data vs Mutable User Data

This distinction is fundamental.

## mcf_setting_data

Represents:

```text
Static / Definition Data
```

Examples:

```text
key
name
type
options
default
category
roles
```

These values describe the framework setting and are not specific to one user.

## mcf_settings

Represents:

```text
Mutable User Data
```

Examples:

```text
user_id
setting reference
value
```

These values change when a user changes a setting.

---

# 9. Complete Storage Example

Assume the framework defines:

```text
key = items_per_page
type = number
default = 20
```

The definition exists in:

```text
mcf_setting_data
```

Initially the user has no override:

```text
mcf_setting_data:
    default = 20

mcf_settings:
    no record
```

Therefore:

```text
Effective Value = 20
```

---

# 10. When the User Changes the Value

The user changes:

```text
20
```

to:

```text
50
```

The definition is not modified.

It remains:

```text
mcf_setting_data:
    default = 20
```

The user value is stored in:

```text
mcf_settings:
    value = 50
```

The resulting state is:

```text
Definition Default = 20
User Override      = 50
Effective Value    = 50
```

---

# 11. When No User Value Exists

If there is no user record in `mcf_settings` for the setting:

```text
mcf_settings
    no record
```

the framework falls back to:

```text
mcf_setting_data.default
```

Therefore:

```text
No User Value
      ↓
Use Default
```

Example:

```text
Default = 20
User Override = none
Effective = 20
```

---

# 12. When a User Value Exists

If an override exists:

```text
Default = 20
User Value = 50
```

the effective value is:

```text
50
```

Resolution rule:

```text
User Value exists?
        │
    ┌───┴───┐
   Yes      No
    │        │
    ▼        ▼
 User      Default
 Value      Value
```

---

# 13. Why Not Store the Default for Every User?

Because that creates unnecessary duplication.

It is not necessary to create:

```text
items_per_page = 20
```

for every user who has never changed the setting.

Instead:

```text
Definition:
default = 20

User:
no override
```

The framework automatically resolves the effective value to:

```text
20
```

Therefore `mcf_settings` contains only actual user customizations.

---

# 14. Reset

When a user resets a setting, the framework does not need to write the default value into `mcf_settings`.

It removes the override.

Before reset:

```text
mcf_setting_data:
default = 20

mcf_settings:
value = 50
```

After reset:

```text
mcf_setting_data:
default = 20

mcf_settings:
no record
```

The effective value becomes:

```text
20
```

Therefore:

```text
Reset = Delete User Override
```

not:

```text
Reset = Save Default as User Value
```

---

# 15. Reset All

Reset All removes the user's setting overrides from:

```text
mcf_settings
```

It does not delete definitions from:

```text
mcf_setting_data
```

It also does not modify:

```text
default values
options
types
categories
```

After the overrides are removed, all effective values fall back to their defaults.

---

# 16. SettingData

Setting definitions in MCF use `SettingData`.

Conceptually:

```php
new SettingData(
    category: ...,
    key: ...,
    name: ...,
    subtitle: ...,
    type: ...,
    options: ...,
    defaultValue: ...,
    roles: ...,
)
```

---

# 17. Category

Categories organize settings.

Core categories:

```text
general
appearance
notification
```

In code:

```php
SettingCategory::GENERAL
SettingCategory::APPEARANCE
SettingCategory::NOTIFICATION
```

Categories do not define storage behavior. They organize and group settings.

---

# 18. Key

`key` is the stable technical identifier.

Example:

```text
notifications_enabled
```

It should be:

- unique
- stable
- language-independent
- suitable for programmatic use

The display name should not be used as the technical key.

---

# 19. Name

`name` is the display name.

Example:

```text
Notifications Enabled
```

It can be localized.

---

# 20. Subtitle

Optional explanatory text.

Example:

```text
Enable or disable system notifications.
```

It can be:

```php
null
```

---

# 21. Type

The type defines the value shape and validation rules.

Supported types:

```text
boolean
select
radio
multiselect
text
number
textarea
```

---

# 22. Boolean

Values:

```php
true
false
```

Example:

```php
new SettingData(
    category: SettingCategory::GENERAL,
    key: 'feature_enabled',
    name: 'Feature Enabled',
    subtitle: 'Enable or disable the feature.',
    type: SettingType::BOOLEAN,
    options: null,
    defaultValue: true,
    roles: null,
)
```

---

# 23. Select

Single-value selection.

```php
options: [
    'list' => 'List',
    'grid' => 'Grid',
],
defaultValue: 'list',
```

Stored value:

```text
list
```

not:

```text
List
```

---

# 24. Radio

Single selection using radio buttons.

```php
options: [
    'small' => 'Small',
    'medium' => 'Medium',
    'large' => 'Large',
],
defaultValue: 'medium',
```

---

# 25. Multiselect

Multiple selections.

```php
options: [
    'website' => 'Website',
    'email' => 'Email',
],
defaultValue: [
    'website',
],
```

The value is:

```php
['website']
```

or:

```php
['website', 'email']
```

Every selected value must exist in `options`.

---

# 26. Text

For short strings.

```php
type: SettingType::TEXT,
defaultValue: 'Example',
```

The value must be a string.

---

# 27. Number

For numeric values.

```php
type: SettingType::NUMBER,
defaultValue: 20,
```

---

# 28. Textarea

For longer text.

```php
type: SettingType::TEXTAREA,
defaultValue: '',
```

---

# 29. Options

Options are used by:

```text
select
radio
multiselect
```

Example:

```php
[
    'first' => 'First',
    'second' => 'Second',
]
```

There is a distinction between:

```text
Option Key
```

and:

```text
Display Label
```

The stored value is:

```text
first
```

while the user sees:

```text
First
```

---

# 30. Localization

The framework does not need to store translated display text as user data.

Example:

```php
name: 'Notifications Enabled'
```

The application can translate the name through its localization system.

Option keys remain stable:

```text
website
email
```

while their display labels can be translated.

---

# 31. Roles

A setting can specify which roles can see it:

```php
roles: [1, 3]
```

or:

```php
roles: null
```

`null` means all roles.

Important:

```text
roles = Visibility
```

not:

```text
roles = Authorization
```

The application's authorization system remains separate.

---

# 32. Dynamic UI

The framework can expose:

```text
category
key
name
subtitle
type
options
default_value
value
```

Example:

```json
{
    "category": "general",
    "key": "feature_enabled",
    "name": "Feature Enabled",
    "subtitle": "Enable or disable the feature.",
    "type": "boolean",
    "options": null,
    "default_value": true,
    "value": false
}
```

The UI can map the type to a control:

```text
boolean     -> Toggle / Checkbox
select      -> Select
radio       -> Radio Group
multiselect -> Multi Select
text        -> Text Input
number      -> Number Input
textarea    -> Textarea
```

The UI therefore does not need hard-coded business logic for each setting.

---

# 33. Read Flow

When the Settings page is opened:

```text
1. Load Setting Definitions
        ↓
2. Resolve Current User
        ↓
3. Load User Overrides
        ↓
4. Match Overrides with Definitions
        ↓
5. Resolve Effective Values
        ↓
6. Return Settings Data
        ↓
7. Render Dynamic UI
```

For each setting:

```text
Override exists
    → Override

No Override
    → Default
```

---

# 34. Save Flow

When a user changes a setting:

```text
User changes setting
        ↓
Identify Setting by key
        ↓
Load Definition
        ↓
Validate value against type/options
        ↓
Save or update User Override
        ↓
Return success
```

The save operation never changes the definition.

---

# 35. Reset Flow

```text
User requests reset
        ↓
Identify setting/user
        ↓
Delete User Override
        ↓
Effective Value becomes Default
```

---

# 36. Static Definition Data Must Not Change Per User

This is one of the most important rules.

If the definition is:

```text
key = items_per_page
type = number
default = 20
```

and a user selects:

```text
50
```

the result must be:

```text
default = 20
user value = 50
```

not:

```text
default = 50
```

The definition remains stable.

---

# 37. Multiple Users Example

Assume:

```text
Definition:
default = 20
```

User 1:

```text
override = 50
```

User 2:

```text
override = 100
```

User 3:

```text
no override
```

Effective values:

```text
User 1 → 50
User 2 → 100
User 3 → 20
```

The shared definition remains:

```text
default = 20
```

This is the reason for separating the two tables.

---

# 38. Validation

Values are validated according to the definition.

### Boolean

```text
true / false
```

### Select / Radio

The value must exist in `options`.

### Multiselect

Must be an array, and every item must exist in `options`.

### Text / Textarea

Must be a string.

### Number

Must be numeric.

---

# 39. Manual / Runtime Settings

A Settings page may also contain settings that are not backed by the MCF Settings tables.

For example, a runtime setting may use:

```text
Session
Browser
Request Context
```

Such settings are not part of:

```text
mcf_setting_data
mcf_settings
```

and are not included in DB-backed Settings Reset.

They can still be displayed alongside framework-backed settings, while using their own persistence mechanism.

---

# 40. Adding a New Framework Setting

The process is:

```text
1. Define SettingData
        ↓
2. Register Definition
        ↓
3. Framework exposes the setting
        ↓
4. User can read it
        ↓
5. User can change it
        ↓
6. Value is validated
        ↓
7. User Override is stored
```

Example:

```php
new SettingData(
    category: SettingCategory::GENERAL,
    key: 'items_per_page',
    name: 'Items Per Page',
    subtitle: 'Number of items displayed per page.',
    type: SettingType::NUMBER,
    options: null,
    defaultValue: 20,
    roles: null,
)
```

---

# 41. Complete End-to-End Example

## Definition

```text
key:
default_view

type:
select

options:
list
grid

default:
list
```

The definition exists in:

```text
mcf_setting_data
```

Initially:

```text
mcf_settings
    no record
```

Effective value:

```text
list
```

The user selects:

```text
grid
```

An override is created or updated in:

```text
mcf_settings
    user = current user
    value = grid
```

Effective value:

```text
grid
```

The user performs Reset:

```text
mcf_settings
    override deleted
```

Effective value returns to:

```text
list
```

The definition never changed.

---

# 42. What Remains Stable?

The setting definition is the source of truth for:

```text
key
name
subtitle
type
options
defaultValue
category
roles
```

These should not become different for different users.

The value that differs between users is:

```text
User Override
```

---

# 43. What Changes?

Only user-specific customization changes in:

```text
mcf_settings
```

Example:

```text
User A → 50
User B → 100
User C → no override
```

while:

```text
Setting Definition
Default = 20
```

remains shared.

---

# 44. Final Relationship

```text
                 MCF Framework
                      │
                      ▼
             Setting Definition
                      │
                      ▼
              mcf_setting_data
                      │
            ┌─────────┴─────────┐
            │                   │
         User A              User B
            │                   │
            ▼                   ▼
     mcf_settings         mcf_settings
       Override              Override
            │                   │
            ▼                   ▼
       Effective A         Effective B
```

The default is the fallback whenever no user override exists.

---

# 45. Design Principles

### Definition First

`mcf_setting_data` is the source of truth for the setting definition.

### User Override

`mcf_settings` stores user customization only.

### Stable Definition

A user change never modifies the definition.

### Default as Fallback

No override means the default is used.

### No Unnecessary Duplication

A user row is not created merely to store the default.

### Reset by Removing Override

Reset removes the override and restores the default.

### Type-Safe Values

The setting type defines the accepted value.

### Stable Keys

Keys are technical, stable, and language-independent.

### Localization Outside User Data

Display text can be localized without changing user values.

### Visibility Is Not Authorization

Roles control visibility, not access control.

### Framework-Level Infrastructure

The Settings tables and their migrations are part of the MCF framework infrastructure, not business tables for a specific project.

---

# 46. Summary

MCF Settings is based on:

```text
mcf_setting_data
    =
Setting Definitions / Framework Data

mcf_settings
    =
User Overrides / Mutable Data
```

The effective value is:

```text
User Override
      ↓
if exists
      ↓
Use User Value

otherwise
      ↓
Use Default Value
```

The framework provides the migrations and core database structure for both tables.

Settings are defined through `SettingData` and can use:

```text
boolean
select
radio
multiselect
text
number
textarea
```

along with:

```text
categories
options
defaults
roles
localization
validation
reset
dynamic UI
```

The fundamental rule is:

```text
Definition is stable.
User values are mutable.
User changes never modify the Definition.
Reset removes the User Override and restores the Default.
```
