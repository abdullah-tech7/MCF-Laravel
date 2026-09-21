# MCF Architecture

MCF (Modular Code Framework) is a feature-oriented application
architecture built on top of Laravel.

MCF keeps Laravel as the underlying application framework while
providing a structured application root, shared framework components,
Modules and Workflows, generated application resources, and conventions
for how those parts work together.

The MCF architecture is broader than the `Modules` directory. `app/MCF`
is the installed MCF root and contains the framework components,
application Modules, documentation, and the framework files that connect
the MCF structure to Laravel.

> MCF does not replace Laravel. It provides an organized application
> architecture on top of Laravel.

------------------------------------------------------------------------

## Architecture Overview

After MCF installation, the main MCF structure is located under:

``` text
app/
└── MCF/
    ├── AccessControl/
    ├── Audit/
    ├── Authentication/
    ├── Base/
    ├── Language/
    ├── Mail/
    ├── Middleware/
    ├── Modules/
    ├── Notification/
    ├── Realtime/
    ├── Result/
    ├── Settings/
    ├── Sms/
    ├── Storage/
    ├── z_Guide/
    └── mcf_routes.php
```

Each part has a different architectural purpose.

The most important distinction is:

``` text
app/MCF
├── Framework components
├── Application features
├── Documentation
└── MCF integration files
```

`Modules` are therefore a major part of MCF, but they are not the entire
MCF architecture.

------------------------------------------------------------------------

# Architecture Goals

MCF is designed to:

-   Organize application code around business capabilities.
-   Keep feature-specific resources together.
-   Provide reusable framework-level components.
-   Reduce unnecessary coupling.
-   Separate responsibilities clearly.
-   Reduce repetitive work through generators.
-   Keep Laravel compatibility.
-   Provide predictable conventions.
-   Support applications as they grow.
-   Make framework behavior configurable instead of requiring developers
    to delete unused framework directories.

------------------------------------------------------------------------

# The MCF Root

The `app/MCF` directory is the central root of the installed MCF
architecture.

It contains four broad categories:

``` text
app/MCF
│
├── Framework Components
│
├── Modules
│
├── Documentation
│
└── MCF Integration Files
```

## 

------------------------------------------------------------------------

# MCF Framework Integrity Rule

The `app/MCF` directory is part of the installed MCF framework and
application foundation.

**Do not delete MCF classes, framework directories, or connected
Workflow files simply because a feature is not currently being used.**

MCF components are connected and some classes may be used directly or
indirectly by other components.

The recommended approach is:

``` text
Keep
Configure
Disable when supported
Customize through the provided settings
```

Feature usage is optional where the architecture allows it. The
framework structure itself should remain intact.

If a component is not needed, configure or disable it where supported
instead of deleting its classes.

# Framework Components

These provide reusable infrastructure for the application:

``` text
AccessControl
Audit
Authentication
Base
Language
Mail
Middleware
Notification
Realtime
Result
Settings
Sms
Storage
```

##  {#-1}

------------------------------------------------------------------------

# Realtime

MCF Realtime provides a simple application-level realtime API without
requiring every Blade View to implement its own polling logic.

The application-facing API is:

``` javascript
MCF.realtime('notifications', {
    onUpdate: function (state) {
        // Update the UI from the received state.
    }
});
```

The runtime is managed by MCF and handles:

``` text
Polling
Request scheduling
Duplicate channel handling
Error retry backoff
Visibility handling
State change detection
```

The default polling interval is:

``` text
15000 ms
```

The developer may override it when necessary:

``` javascript
MCF.realtime('notifications', {
    interval: 5000,

    onUpdate: function (state) {
        // ...
    }
});
```

The interval is therefore optional. The runtime provides the default.

A Realtime Channel is registered by the MCF framework. Application code
registers the channel\'s behavior and consumes its state; it should not
reimplement the polling runtime in every View.

The intended separation is:

``` text
MCF Realtime Runtime
        ↓
   Channel / State
        ↓
Application View
```

The View is responsible for presentation, while MCF owns the runtime
behavior.

The Realtime runtime is loaded through MCF\'s server-side framework
integration. Developers should not manually duplicate the runtime script
in every Blade View.

# Modules

`Modules` contains the application\'s feature-oriented structure.

### Documentation

`z_Guide` contains the MCF documentation installed with the framework.

### Integration Files

Files such as:

``` text
.mcf-installed
mcf_routes.php
```

support the installed MCF structure and its integration with the Laravel
application.

------------------------------------------------------------------------

# Framework Components

The framework-level directories under `app/MCF` provide functionality
that can be shared by multiple features.

``` text
app/MCF
├── AccessControl
├── Audit
├── Authentication
├── Base
├── Language
├── Mail
├── Middleware
├── Notification
├── Result
├── Settings
└── Sms
```

These components should not be treated as individual application
features.

For example:

-   Authentication provides authentication infrastructure.
-   Audit provides auditing infrastructure.
-   AccessControl provides access-related infrastructure.
-   Base provides common base classes.
-   Mail provides MCF mail functionality.
-   Notification provides notification infrastructure.
-   Result provides standardized result handling.
-   Sms provides SMS-related functionality.
-   Storage provides provider-independent file storage infrastructure.

Each component has its own detailed documentation.

------------------------------------------------------------------------

# Storage

MCF Storage provides a provider-independent file storage abstraction for
application features.

The purpose of MCF Storage is to keep application Modules independent
from a concrete physical storage implementation while still providing a
consistent API for file operations.

The main concepts are:

``` text
McfStorage
StorageReference
StorageRecord
McfFileData
McfStorageResult
McfStorageMultiResult
StorageRegistry
StorageProvider
```

The architectural separation is:

``` text
Application
    ↓
McfStorage
    ├── StorageRegistry
    │       ↓
    │   MCF storage records
    │
    └── StorageProvider
            ↓
        Physical storage
```

The registry stores information about the file, while the provider
performs the physical storage operation.

## StorageReference

`StorageReference` is the internal identity of a stored file.

The original filename is not used as the physical storage identity.

For example:

``` text
Original name:
invoice.pdf

Storage reference:
20260818023227059301.pdf
```

The original name remains part of the storage record and is used for
user-facing downloads.

## StorageRecord

`StorageRecord` represents the MCF registry information for a file.

It can contain:

-   Reference.
-   Original name.
-   Extension.
-   Type.
-   MIME type.
-   Size.
-   Folder.
-   Provider.
-   Storage root.
-   Access policy.
-   Creation and update timestamps.

A record can be retrieved with:

``` php
$result = McfStorage::find($reference);
```

For bulk operations, MCF can retrieve records through a bulk lookup
instead of repeatedly querying individual references.

## StorageProvider

`StorageProvider` is the contract implemented by a physical storage
backend.

A provider is responsible for:

-   Uploading files.
-   Generating public URLs.
-   Generating temporary URLs.
-   Downloading files.
-   Deleting files.
-   Checking file existence.
-   Returning provider metadata.

A provider may use Laravel Filesystem, S3, or another storage backend.

The application does not need to know which backend is being used.

## Multiple Providers

MCF Storage supports multiple providers through the same abstraction:

``` text
                    McfStorage
                        |
                StorageProvider
                  /     |      \
                 v      v       v
             Laravel    S3    Custom
```

A storage record identifies information such as:

``` text
provider
storage_root
folder
reference
```

This allows different files to use different storage backends while the
application continues to use the same MCF Storage API.

Adding a new provider requires implementing the `StorageProvider`
contract and registering it with the provider resolution mechanism.

No application-level storage API needs to change.

## Public and Protected Storage

MCF Storage supports two storage access states:

``` text
public
protected
```

A public file can use a permanent public source.

A protected file uses a temporary source with a limited lifetime.

Protected access is a storage access policy. It is not a replacement for
application authorization.

Application-level permissions remain the responsibility of the
appropriate access-control layer.

## Single Operations

MCF Storage supports individual operations such as:

``` php
McfStorage::upload($data);
McfStorage::find($reference);
McfStorage::view($reference);
McfStorage::download($reference);
McfStorage::metadata($reference);
McfStorage::exists($reference);
McfStorage::delete($reference);
McfStorage::makePublic($reference);
McfStorage::makeProtected($reference);
```

## Multi Operations

MCF Storage also supports bulk operations:

``` php
McfStorage::uploadMany($dataList);
McfStorage::downloadMany($references);
McfStorage::deleteMany($references);
```

The recommended application behavior is:

``` text
0 selected
    → no operation

1 selected
    → single operation

2+ selected
    → multi operation
```

Bulk workflows use bulk registry lookup where appropriate to avoid
unnecessary repeated database queries.

## Download Names

The physical storage identity is the reference, but downloads use the
original filename.

Therefore:

``` text
Physical storage:
20260818023227059301.pdf

User download:
invoice.pdf
```

For multiple downloads, MCF creates a ZIP archive and preserves the
original filenames inside the archive. Duplicate original filenames are
resolved safely.

## Results

Single storage operations return:

``` php
McfStorageResult
```

Bulk storage operations return:

``` php
McfStorageMultiResult
```

This keeps storage failures and operation status consistent with the
rest of the MCF Result architecture.

## Provider Independence

Application Modules should use:

``` php
McfStorage::upload($data);
```

rather than coupling themselves directly to a concrete storage
implementation.

The intended dependency direction is:

``` text
Workflow / Service
        ↓
   McfStorage
        ↓
StorageRegistry + StorageProvider
```

This allows the physical storage backend to change without requiring
application features to be rewritten.

------------------------------------------------------------------------

# Modules

`Modules` is the feature-oriented part of the MCF architecture.

``` text
app/MCF/Modules
```

A typical installed application may contain:

``` text
Modules
├── Shared
│   └── Layout
└── User
    ├── Auth
    ├── Profile
    └── UserManagement
```

A Module groups related business capabilities.

A Workflow inside a Module represents a specific feature or business
capability.

Therefore:

``` text
MCF
└── Modules
    └── Module
        └── Workflow
```

Modules and Workflows are only one part of the larger `app/MCF`
architecture.

------------------------------------------------------------------------

# Workflows

A Workflow represents a business capability or feature.

Examples:

-   Auth
-   Profile
-   User Management
-   Checkout
-   Product Catalog
-   Reports

A Workflow should represent a meaningful application capability, not
simply a database table.

Example:

``` text
User
├── Auth
├── Profile
└── UserManagement
```

A Workflow contains the resources that belong specifically to that
feature.

------------------------------------------------------------------------

# Workflow Structure

A Workflow may contain:

``` text
User
└── Auth
    ├── Backend
    ├── Lang
    └── Views
```

The exact contents depend on the feature.

For example, the Backend may contain:

``` text
Backend
├── AuthController.php
├── AuthRoutes.php
├── AuthService.php
└── Request
    └── LoginRequest.php
```

Not every Workflow needs every type of resource.

------------------------------------------------------------------------

# Backend

The `Backend` directory contains the server-side implementation of a
Workflow.

Typical resources include:

-   Controller.
-   Routes.
-   Services.
-   Requests.
-   Other feature-specific backend classes.

The Backend is feature-specific. Shared framework functionality belongs
under the appropriate MCF framework component instead of being
duplicated inside every Workflow.

------------------------------------------------------------------------

# Controllers

Controllers handle HTTP communication.

Typical responsibilities are:

-   Receive HTTP requests.
-   Delegate application logic.
-   Return responses.

Controllers should remain focused on HTTP interaction.

Business logic should be placed in the appropriate Service or
application component.

------------------------------------------------------------------------

# Services

Services contain application and business logic.

Typical responsibilities include:

-   Business rules.
-   Data operations.
-   Workflow coordination.
-   Domain/application operations.

Services and Controllers have different responsibilities and should not
become substitutes for one another.

------------------------------------------------------------------------

# Requests

Requests contain validation and request-related authorization.

MCF provides:

``` bash
php artisan mcf:make:request {module} {workflow} {request}
```

Example:

``` bash
php artisan mcf:make:request User Auth Login
```

The Request is created inside the Workflow\'s `Request` directory.

If the directory does not exist, MCF creates it.

The current architecture uses endpoint-specific Requests rather than the
old shared Workflow Request concept.

A Request may also define a dedicated Data class for validated input.

Example:

``` php
final class LoginRequest extends MfcRequest
{
    protected function dataClass(): ?string
    {
        return LoginData::class;
    }
}
```

------------------------------------------------------------------------

# Endpoints

An Endpoint represents a complete HTTP feature path.

MCF provides:

``` bash
php artisan mcf:endpoint:create
```

Depending on the selected options, endpoint creation can generate and
connect:

-   A Controller method.
-   A Route.
-   A View.
-   A Request specific to the Endpoint.

When an Endpoint uses a Request, that Request is connected directly to
the Endpoint\'s Controller method.

Endpoint generation is treated as a complete operation rather than a
collection of unrelated partial operations.

------------------------------------------------------------------------

# Routes

Each Workflow owns its route file.

Example:

``` text
Auth
└── Backend
    └── AuthRoutes.php
```

MCF also provides:

``` text
app/MCF/mcf_routes.php
```

This file belongs to the MCF route integration layer.

It is separate from the individual Workflow route files.

The architecture therefore has two different route concepts:

``` text
Workflow
└── Backend
    └── WorkflowRoutes.php
```

for feature-specific routes, and:

``` text
app/MCF/mcf_routes.php
```

for the MCF-level route integration.

MCF discovers and registers the appropriate Workflow routes through its
framework integration.

------------------------------------------------------------------------

# Views

A Workflow may contain its own Blade views:

``` text
Views
├── index.blade.php
├── create.blade.php
└── edit.blade.php
```

Views stay close to the feature that owns them.

MCF registers Workflow view namespaces according to its conventions.

------------------------------------------------------------------------

# Language Resources

A Workflow may contain its own language resources:

``` text
Lang
```

This keeps translations close to the feature that uses them.

MCF handles the registration of Workflow language resources according to
its framework conventions.

------------------------------------------------------------------------

# Base Classes

MCF provides common application base classes under:

``` text
app/MCF/Base
```

Examples include:

``` text
MfcController
MfcPolicy
MfcRequest
MfcService
```

Generated application classes can inherit from these classes to use
common MCF behavior and conventions.

------------------------------------------------------------------------

# Settings

MCF Settings is a framework-level configuration and user-preference system.
It provides infrastructure for defining settings, storing their definitions,
resolving effective values, validating changes, updating user overrides, and
resetting them to their defaults.

The Settings architecture separates the stable definition of a setting from
the mutable value selected by a user.

``` text
Setting Definition
      ↓
mcf_setting_data
      │
      │ definition
      ▼
mcf_settings
      │
      │ user override
      ▼
Effective Value
```

## Setting Definition

`mcf_setting_data` is the definition side of the Settings architecture.

It describes what a setting is, rather than what a particular user selected.

A definition can contain:

``` text
category
key
name
subtitle
type
options
default value
roles
```

Definitions are represented through `SettingData`.

The `key` is the stable technical identifier. It should be unique,
language-independent, and suitable for programmatic use.

Display names and subtitles are presentation data and can be localized.

## User Overrides

`mcf_settings` stores user-specific setting values.

It does not redefine the setting.

``` text
mcf_setting_data
    = Stable Setting Definition

mcf_settings
    = User Override
```

A user does not need a row in `mcf_settings` when using the default value.

For example:

``` text
Definition:
    default = 20

User:
    no override

Effective:
    20
```

If the user changes the value:

``` text
Definition Default = 20
User Override      = 50
Effective Value    = 50
```

The definition remains unchanged. Different users can therefore have different
overrides while sharing the same setting definition.

## Effective Value

The effective value is resolved using:

``` text
User Override exists?
       │
   ┌───┴───┐
  Yes      No
   │        │
   ▼        ▼
 User     Default
 Value     Value
```

The definition's default is the fallback value when no user override exists.

## Reset

Resetting a setting removes the user's override.

``` text
User Override
      ↓
Delete Override
      ↓
Definition Default
```

Reset All removes user overrides from `mcf_settings` without deleting setting
definitions or changing their defaults, options, types, or categories.

Therefore:

``` text
Reset = Remove User Override
```

not:

``` text
Reset = Save Default as User Value
```

## Setting Types

MCF Settings supports:

``` text
boolean
select
radio
multiselect
text
number
textarea
```

For `select`, `radio`, and `multiselect`, the stored value is the option key,
while the display label is presentation data.

Example:

``` php
[
    'website' => 'Website',
    'email' => 'Email',
]
```

The stored value is:

``` text
website
```

not:

``` text
Website
```

## Validation

Setting values are validated according to their definition:

``` text
boolean      → true / false
select       → value must exist in options
radio        → value must exist in options
multiselect  → array; every value must exist in options
text         → string
textarea     → string
number       → numeric value
```

## Roles

A setting may specify roles that can see it:

``` php
roles: [1, 3]
```

or:

``` php
roles: null
```

In the Settings architecture, `roles` defines **visibility**.

It does not replace MCF Access Control or application authorization.

## Dynamic UI

Settings are designed so the application UI can be generated from the
definition instead of hard-coding every setting:

``` text
boolean     → Toggle / Checkbox
select      → Select
radio       → Radio Group
multiselect → Multi Select
text        → Text Input
number      → Number Input
textarea    → Textarea
```

The UI consumes the definition, current value, default value, and options and
renders the appropriate control.

## Database Architecture

MCF Settings includes framework-level database migrations for:

``` text
mcf_setting_data
mcf_settings
```

These tables belong to the MCF framework infrastructure.

The architectural separation is intentional:

``` text
mcf_setting_data
    = Framework Setting Definitions

mcf_settings
    = Mutable User Overrides
```

Setting definitions are shared framework data. User overrides are mutable
user-specific data.

The fundamental rule is:

``` text
Definition is stable.
User values are mutable.
User changes never modify the Definition.
Reset removes the User Override and restores the Default.
```

## Settings Flow

Reading a setting follows:

``` text
Load Definition
      ↓
Resolve Current User
      ↓
Load User Override
      ↓
Resolve Effective Value
      ↓
Return Setting Data
```

Saving follows:

``` text
Identify Setting by key
      ↓
Load Definition
      ↓
Validate Value
      ↓
Create / Update User Override
      ↓
Return Result
```

The save operation changes the user's override, not the setting definition.


# MCF Configuration and Optional Components

MCF provides configuration mechanisms for its framework components. MCF
Settings is the framework-level system for settings that require definitions,
defaults, user-specific overrides, validation, and reset behavior.

When a component supports enabling or disabling, the preferred approach
is to use its settings rather than deleting its directory.

The principle is:

``` text
Disable a component when it is not required.
Do not delete framework components simply because they are unused.
```

This is especially important because MCF components can be
interconnected.

The detailed documentation for each component explains its available
settings and behavior.

------------------------------------------------------------------------

# Installed Modules and Workflows

The installer provides an initial application structure.

For example:

``` text
app/MCF/Modules
├── Shared
│   └── Layout
└── User
    ├── Auth
    ├── Profile
    └── UserManagement
```

These provide a starting point for the application.

Developers can modify application-specific Modules and Workflows
according to project requirements.

The `Shared/Layout` Workflow is intended to provide a reusable
application layout and is recommended to remain installed unless the
developer has a clear replacement.

------------------------------------------------------------------------

------------------------------------------------------------------------

# Queue and Jobs

MCF supports Laravel Queue and Jobs for background work.

Typical uses include:

``` text
Email delivery
Notifications
Long-running processing
External service calls
Background application tasks
```

The architectural relationship is:

``` text
Workflow / Service
        ↓
      Job
        ↓
Laravel Queue
        ↓
Queue Worker
```

The distinction is:

``` text
Queue = execution mechanism
Job   = unit of background work
Workflow / Service = application behavior
```

MCF integrates with Laravel\'s existing Queue system rather than
creating a separate queue engine.

Queue support is intended for work that should not block the current
HTTP request.

### Example Modules and Workflows

The installed MCF application includes a small working example structure
intended for learning, testing, and customization.

The default examples contain two Modules, with three Workflows in each:

``` text
Modules/
├── Shared/
│   ├── Layout/
│   ├── RealtimeTest/
│   └── StorageTest/
│
└── User/
    ├── Auth/
    ├── Profile/
    └── UserManagement/
```

These examples are intentionally simple.

They demonstrate how MCF components can be used together, including
authentication, access control, audit, notifications, mail, storage,
realtime, language, results, middleware, and other framework services.

The example Workflows are useful for:

-   Learning the MCF structure.
-   Understanding how Controller, Routes, Service, Request, and Views
    fit together.
-   Testing framework components.
-   Using a working reference when creating application features.
-   Customizing the examples for a real project.

`RealtimeTest` and `StorageTest` are test/example Workflows and may be
customized as needed.

`Shared/Layout` is more structural. It provides the shared layout
pattern used by Views and Workflow generation, so it is recommended to
keep it unless the project has a deliberate replacement.

# Database and MCF Architecture

The MCF architecture also includes database structures used by its
components.

MCF provides:

-   Database migrations.
-   Initial seed data.
-   Tables required by MCF components and the initial application
    structure.

The database is not physically located under `app/MCF`; it remains part
of the Laravel application\'s database structure.

The developer controls the application\'s database connection through
`.env`:

``` env
DB_CONNECTION=...
DB_DATABASE=...
```

After configuring the database:

``` bash
php artisan migrate --seed
```

MCF database tables can be extended according to application
requirements.

For MCF Settings, the framework provides the two related tables:

``` text
mcf_setting_data
    → setting definitions

mcf_settings
    → user-specific overrides
```

The definition table and user-value table have different responsibilities and
should remain logically separated.

Because framework components may depend on these tables, developers
should understand the dependencies before removing or replacing MCF
database structures.

------------------------------------------------------------------------

### Database and Migration Rules

Laravel\'s core database foundation is required.

MCF feature migrations are optional according to the components used by
the project. However, applying the provided migrations initially is
recommended so developers can understand the intended MCF data model
before customizing it.

``` bash
php artisan migrate --seed
```

After understanding the model, migrations can be customized for the
application\'s requirements.

If the `users` table is customized, especially when a column used by MCF
Authentication is removed or renamed, the related authentication
settings and user-resolution logic must also be updated.

The dependency should remain consistent:

``` text
Database schema
      ↕
User Model
      ↕
McfAuth / User Settings
      ↕
Application
```

Removing a User column without updating the corresponding MCF
Authentication configuration can break authentication behavior.

# MCF Installation Marker

MCF creates:

``` text
app/MCF/.mcf-installed
```

This file acts as an installation marker for the MCF installation
process.

It allows the installer to recognize that MCF has already been installed
in the application and prevents accidental repeated installation.

It is an internal installation file and should not be treated as an
application feature.

------------------------------------------------------------------------

# MCF Documentation

The installed framework contains:

``` text
app/MCF/z_Guide
```

This directory contains the documentation available to the developer
after installation.

The guide covers:

-   Getting started.
-   Architecture.
-   Folder structure.
-   CLI commands.
-   Best practices.
-   Individual MCF components.
-   Generators.
-   Modules and Workflows.
-   Routes.
-   Database.
-   Authentication.
-   Audit.
-   Mail.
-   Notifications.
-   SMS.
-   Settings.
-   Storage.
-   Other MCF systems.

Start with:

``` text
README.md
```

Then use the detailed documentation for the component being used.

------------------------------------------------------------------------

# Shared vs Feature-Specific Code

The architecture distinguishes framework-level functionality from
application features.

``` text
app/MCF
│
├── Shared framework functionality
│   ├── Authentication
│   ├── Audit
│   ├── AccessControl
│   ├── Mail
│   ├── Notification
│   ├── Realtime
│   ├── Settings
│   ├── Storage
│   └── ...
│
└── Modules
    └── Application features
        └── Workflows
```

Use a Workflow for code belonging to a specific business capability.

Use the framework components for functionality intentionally shared
across features.

------------------------------------------------------------------------

# Feature Isolation

Feature-specific code should remain close to the feature that owns it.

Example:

``` text
User
└── Auth
    ├── Backend
    ├── Lang
    └── Views
```

This makes features easier to locate, maintain, test, and extend.

Feature isolation does not mean that Workflows cannot use shared MCF
services.

------------------------------------------------------------------------

# Application Startup

When the application starts, MCF initializes its framework integration.

Depending on the component, this can include:

-   Loading MCF configuration.
-   Registering MCF services.
-   Initializing Settings infrastructure where enabled.
-   Discovering Modules and Workflows.
-   Registering Workflow routes.
-   Registering Workflow views.
-   Registering Workflow language resources.
-   Initializing other MCF infrastructure.

The purpose of automatic discovery is to avoid unnecessary manual
registration of every feature.

------------------------------------------------------------------------

# Automatic Discovery

MCF uses conventions and the installed structure to discover application
resources.

The primary feature root is:

``` text
app/MCF/Modules
```

while the complete MCF root remains:

``` text
app/MCF
```

This distinction is important:

``` text
app/MCF
    = complete MCF architecture

app/MCF/Modules
    = application feature architecture inside MCF
```

------------------------------------------------------------------------

------------------------------------------------------------------------

# MCF APIs in Blade

MCF exposes its main authentication and access APIs directly to Blade.

Developers can use:

``` blade
McfAuth::check()
McfAuth::user()
McfAuth::id()

McfAccess::can('permission')
```

Example:

``` blade
@if (McfAuth::check())
    {{ McfAuth::user()->name }}
@endif

@if (McfAccess::can('users.view'))
    <a href="{{ route('users.index') }}">
        {{ __('Users') }}
    </a>
@endif
```

For MCF Views, prefer `McfAuth` and `McfAccess` as the application\'s
public authentication and access APIs instead of coupling the View
directly to Laravel authentication/authorization implementation details.

------------------------------------------------------------------------

# MCF Registration and Automatic Discovery

MCF uses its service provider and application integration layer to
register framework behavior automatically.

Developers should not manually register every MCF component in each
Blade View or feature.

Framework-level registration can include:

-   Service registration.
-   Module and Workflow discovery.
-   Workflow route registration.
-   Workflow view registration.
-   Language resource registration.
-   Realtime channel registration.
-   Other framework infrastructure.

Application feature usage remains explicit where appropriate, while
framework wiring is centralized.

This keeps the application entry points clean and prevents repeated
manual setup across Views and Modules.

# Request Lifecycle

A typical MCF HTTP request can follow:

``` text
HTTP Request
    ↓
Route
    ↓
Controller
    ↓
Request / Validation
    ↓
Business Logic / Service
    ↓
Response
```

Middleware, authorization, audit, notifications, and other MCF systems
may participate depending on the feature and configuration.

Not every request uses every component.

------------------------------------------------------------------------

# Extensibility

MCF is designed to be extended.

Projects can:

-   Add Modules.
-   Add Workflows.
-   Add Endpoints.
-   Add Requests.
-   Extend MCF Base Classes.
-   Add application-specific Services.
-   Integrate Laravel packages.
-   Add project-specific functionality.

The framework provides conventions without preventing the application
from implementing its own domain requirements.

------------------------------------------------------------------------

# Laravel Compatibility

MCF is built on Laravel and continues to use Laravel\'s ecosystem.

Applications can use Laravel functionality such as:

-   Eloquent.
-   Blade.
-   Validation.
-   Middleware.
-   Policies.
-   Events.
-   Queues.
-   Notifications.
-   Mail.
-   Cache.
-   Storage.
-   Broadcasting.
-   Scheduling.

Laravel packages can also be used when compatible with the
application\'s Laravel version.

------------------------------------------------------------------------

# Scalability

As an application grows, new business capabilities can be added as
Workflows inside Modules.

``` text
app/MCF/Modules
└── Module
    └── Workflow
        ├── Backend
        ├── Lang
        └── Views
```

At the same time, shared framework functionality remains under
`app/MCF`.

This keeps the architecture predictable as the project grows.

------------------------------------------------------------------------

------------------------------------------------------------------------

# Optional Feature Usage

MCF is modular in usage, not in framework integrity.

A project may use only the components it needs:

``` text
Authentication
AccessControl
Audit
Notification
Mail
Settings
Storage
Realtime
Queue / Jobs
...
```

Not every project needs every feature.

However, unused MCF components should remain installed unless the
framework explicitly provides a supported removal mechanism.

This allows a project to enable a feature later without reconstructing
the framework structure and avoids breaking dependencies between
components.

# Design Principles

MCF follows these principles:

-   Business capability first.
-   Feature isolation.
-   Clear separation of responsibilities.
-   Shared infrastructure for shared concerns.
-   Self-contained feature resources.
-   Convention over unnecessary configuration.
-   Automatic discovery where appropriate.
-   Laravel compatibility.
-   Extensibility.
-   Predictable structure.
-   Long-term maintainability.
-   Prefer configuration and disabling over deletion of framework
    components.
-   Keep framework components intact even when their application-level
    usage is optional.
-   Keep realtime runtime behavior inside MCF rather than duplicating
    polling logic in Views.
-   Keep background execution on Laravel Queue / Jobs.
-   Expose authentication and access through MCF public APIs in Views.
-   Keep setting definitions separate from user-specific setting overrides.
-   Treat setting defaults as fallbacks rather than duplicating them per user.

------------------------------------------------------------------------

# Summary

MCF is not only a Module and Workflow structure.

The complete architecture is rooted at:

``` text
app/MCF
```

and contains:

``` text
Framework Components
        +
Settings Infrastructure
        +
Modules / Workflows
        +
Documentation
        +
MCF Integration Files
```

Modules and Workflows organize application features, while the
framework-level directories provide shared infrastructure used across
those features. Settings is part of that shared infrastructure and separates
framework setting definitions from mutable user-specific overrides.

MCF also integrates routes, views, language resources, realtime
channels, queue/jobs, database structures, configuration, installation
state, and framework services into the Laravel application.

Laravel remains the underlying application framework. MCF provides a
consistent feature-oriented application architecture on top of it.

For the complete details of each part, use the individual documentation
files included with MCF.
