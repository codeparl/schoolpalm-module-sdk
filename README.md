# SchoolPalm Module SDK (module-sdk) — Purpose & Integration with module-bridge

## Overview
**SchoolPalm Module SDK** is the official package used to **scaffold, validate, register, and run SchoolPalm modules** inside a Laravel application.

At runtime, requests are routed through the SDK, the requested module is resolved/initialized, and the module executes an **action** (e.g. `view-list`, `create`, `edit`).

The SDK integrates with **`schoolpalm/module-bridge`** to:
- **register module namespaces/providers** dynamically from a registry file
- build and expose a **module relation registry** (used for resolving pipelines/relations between modules)
- provide shared utilities/config used by both packages

> In this document, the term **module** refers to a SchoolPalm module created with the SDK scaffolding tools and executed via the SDK runtime.

---

## What problem it solves
The SDK provides:
1. **Module lifecycle tooling** (CLI):
   - create a module manifest (`sp:make-m`)
   - scaffold folder structure from manifest (`sp:gen-s`)
   - validate module manifests against a JSON schema (`sp:validate-m` via `ManifestValidator`)
   - install module assets/route wiring (`sp:install`)
2. **Runtime execution layer**:
   - Laravel HTTP endpoints that resolve a module and call the correct action
   - Inertia rendering for module dashboards
3. **Action dispatch**:
   - translate route `action` → method name `runX()` and/or delegate to `Actions` classes

---

## Repository layout (key entrypoints)
Within `src/` the most relevant parts are:
- `ModuleSDKServiceProvider.php` — SDK service provider; merges config and registers console commands
- `src/routes/vendor_routes.php` — dynamic route definition for module execution
- `src/http/ModuleController.php` — runtime resolver + action execution + dashboard rendering
- `src/http/SDKController.php` — additional controller endpoints (module host/API entrypoint)
- `src/core/ActionResolver.php` — maps `action` → `runX()` and delegates to `Actions/*` classes
- `src/manifest/*` — `ManifestFactory`, `ManifestValidator`
- `src/console/*` — CLI commands (scaffold, validate, install, etc.)

---

## module-bridge integration (core mechanism)
### Module registration
Integration is primarily done through:
- `SchoolPalm\ModuleBridge\Providers\ModuleRegistrar`

The SDK service provider calls:
- `ModuleRegistrar::registerSnapshots(...)`
- `ModuleRegistrar::registerModules(...)`
- `ModuleRegistrar::bootRelations(...)`

These are invoked inside:
`src/ModuleSDKServiceProvider.php`:
- merge `sdk.php` config from SDK itself and from module-bridge helper/config
- register module autoloaders and providers from the registry file(s)
- build a merged module relation registry

### What ModuleRegistrar does
Inside `vendor/schoolpalm/module-bridge/src/Providers/ModuleRegistrar.php` the behavior is:

1. **Register normal modules** (`registerModules($registryPath, $context)`)
   - Loads registry JSON or PHP registry
   - Normalizes entries to a flat list (SDK context is handled differently than production context)
   - For each module:
     - registers an **autoload function** for its namespace → base path
     - registers all **service providers** found under `{$namespace}\\Providers`

2. **Register snapshots** (`registerSnapshots($registryPath)`)
   - Similar to normal modules, but uses snapshot registry fields like `execution_path`
   - Registers snapshot providers and autoloaders from the snapshot execution path

3. **Boot relations** (`bootRelations($modulesRegistryPath, $snapshotsRegistryPath, $context)`)
   - Loads both module and snapshot registries
   - Builds a merged relation registry using `RelationRegistryBuilder`
   - Stores the merged registry in the container key `module.relations`

### How the SDK consumes the registered modules
At runtime, the SDK controllers rely on module/entry resolution via the SDK/container + module-bridge registries.
Key usage points:
- `ModuleController` uses `ModuleRegistry` and a `ModuleResolver` to locate module logic.
- `SDKController` uses `CreatedRegistry` and/or `ModuleResolver` for module entry execution.

---

## Module lifecycle

### 1) Create module (manifest)
Command:
- `php artisan sp:make-m {name?}` → `MakeModuleCommand`

Flow:
1. Ask for module name (human readable) and choose a minimum role.
2. Build `module_key` using configured `vendor` and a slug of the name.
3. Generate the initial `manifest.json` using `ManifestFactory::make($data)`.
4. Validate the manifest against the schema via `ManifestValidator::validate(...)`.
5. Create module directory under the configured module path.
6. Write:
   - `manifest.json`
   - `pipeline.json` from `stubs/dev-pipeline.json`
7. Register the module in the **created registry** via `CreatedRegistry::register($data)`.

**ManifestValidator / schema validation**
- schema file comes from `ModulePaths::schemaPath()`
- normalization occurs prior to validation to ensure missing defaults and object/array shapes align with schema expectations.

### 2) Scaffold module folder structure
Command:
- `php artisan sp:gen-s` → `GenerateModuleCommand`

Flow:
1. Select a module from the created registry (`chooseModule()`, defined in `ModuleCommandBase`).
2. Read `manifest.json` from the module path.
3. Use `ModuleScaffold` to generate folders and starter files.

### 3) Validate module
Command:
- `php artisan sp:validate-m` → `ValidateModuleCommand`

The validator enforces JSON schema correctness.

### 4) Install module (routes wiring)
Command:
- `php artisan sp:install` → `installModuleCommand`

Current behavior in the repo:
- Calls `ModuleInstaller::make()->ensureInstallPaths()->installRoutes()`
- Copies vendor module routes into the application’s routes.

> Note: The code you provided for `installModuleCommand.php` only shows route installation; other steps might be part of `ModuleInstaller`.

---

## Runtime request flow (how actions run)

### Route definition
The SDK registers a catch-all module route in:
`src/routes/vendor_routes.php`

```php
Route::prefix('/')->group(function () {
    Route::match(
        ['get','post','patch','delete','put'],
        '{portal}/{module?}/{action?}/{id?}',
        [ModuleController::class, 'handle']
    );
});
```

Route segments:
- `portal` — tenant/portal/root context (e.g. `admin`, `teacher`, `student`)
- `module` — which module to execute
- `action` — which action within the module
- `id` — optional record identifier

### ModuleController: resolve → execute → return
In `src/http/ModuleController.php`:

1. **Constructor**
   - Reads module segment from route using `Helper::getRouteSegment('module')`
   - Normalizes it via `Helper::normalizeModuleName(...)`

2. **resolveModule()** (lazy)
   - Builds a `context` array from route segments:
     - `portal`, `moduleName`, `action`, `id`
   - Creates a `BaseModule($context)`
   - Injects a `ModuleResolver` built from `ModuleRegistry::class->all()`
   - Stores the module instance for reuse

3. **handle(Request $request)**
   - If module is missing: calls `dashboard()`
   - Otherwise:
     - `$module->performAction()`
     - if AJAX or JSON requested, returns JSON; else returns the normal response

4. **dashboard()**
   - Resolves module (or creates a temporary `BaseModule`)
   - Uses Inertia to render the dashboard Vue component:
     - `$module->getResolver()->resolveDashboard()`

---

## Action dispatch (mapping action → method)
Action dispatch is implemented in `src/core/ActionResolver.php`.

Key concepts:
- `portal`, `moduleName`, `action`, `id` are derived from the route segments in the constructor.
- `rootDir = app_path('SDK')`
- `loadActions()` loads PHP classes from an `Actions` directory

### action → runX()
`getMethodName($prefix = 'run')` transforms the action string.

Example:
- action: `view-list` → method: `runViewList`
- action: `add-student` → method: `runAddStudent`

### handleActions()
`handleActions()`:
1. Computes `$method = $this->getMethodName()`
2. If the method exists on the current object, it is called.
3. Otherwise it iterates over loaded action classes in `$this->actions`.
4. If still not found, aborts with `403: No action defined.`

### Delegation to Actions classes
`loadActions()`:
- sets `$actionPath = $this->rootDir . '/Actions'`
- uses `$actionNamespace = 'App\\SDK\\Actions'`
- instantiates each action class found in that directory

`__call($method, $args)` also delegates to these action instances.

---

## SDKController: module host and module API entry
`src/http/SDKController.php` provides additional endpoints:

### handle($module, $action = null)
This route is separate from the dynamic `{portal}/{module}/{action}/{id}` route.
Main steps:
1. Uses `CreatedRegistry::get($moduleKey)` to find an installed/generated module.
2. Verifies a built JS bundle exists under:
   - `public/build/modules/{root}/{module}.js`
3. Returns a view called `shell` with module and port metadata.

### moduleHost($context, $module, $action = null)
Builds an Inertia render payload (`ModuleHost`) with a module URL.
- In local dev: points to `http://localhost:{port}/index.html`
- In non-local: points to an internal route `/modules/{context}/{module}/{action}`

### moduleApi()
Executes a module’s main entrypoint class:
1. Extracts module path segment with `Helper::getPathSegment('module')`
2. Checks module exists in `CreatedRegistry`
3. Resolves the module main class using `ModuleResolver`
4. Creates an instance and calls:
   - `$entry->init($resolver->module)`
   - `$entry->performAction()`

---

## Manifests: how metadata affects runtime
The manifest is created/normalized via:
- `ManifestFactory::make($data)`
- `ManifestValidator::validate($manifest, ...)`

Important manifest fields for understanding behavior:
- `module_key` — unique identifier used by registries
- `namespace` — base namespace for autoloading and provider discovery
- `level` + `is_common` — affects namespace/layout (multi-level modules)
- `entry.provider` — provider class used as module entry
- `menus` — default menu entries for UI integration
- `actions` — declared actions (scaffolding may use this)
- `dependencies` — backend/frontend dependencies
- `migrations` and `models` — used by installers/scaffolders

The SDK also generates default events/listeners and default provides/contracts if not provided.

---

## Configuration
The SDK publishes `config/sdk.php` to the host app.

`ModuleSDKServiceProvider`:
- merges `ModulePaths::configPath()` into Laravel config key `sdk`
- merges `BridgeHelper::configPath()` into `sdk` as well
- merges snapshot/module registrations

Key config in `config/sdk.php` includes:
- `modules_path`
- `install_path`
- `vendor`
- `namespace`
- `defaults` for manifest generation
- `modules cache key`

---

## Console commands (quick reference)
From the service provider, these commands are registered:
- `sp:make-m` → create module manifest/directory and register it
- `sp:validate-m` → validate manifest against JSON schema
- `sp:gen-s` → scaffold module structure
- `sp:stub` and related commands → generate stubs/templates
- `sp:install` → install module routes
- `sp:install` + should/should-run variants → (additional lifecycle)

---

## Purpose statement (short)
**Purpose of the SchoolPalm Module SDK**: provide an opinionated framework to build SchoolPalm modules—manifest-first creation, schema validation, scaffolding, route installation, and runtime action execution.

**Purpose of the integration with module-bridge**: ensure modules are discoverable and loadable at runtime by registering namespaces/autoloaders/providers from registries and exposing a relations registry used by module resolution.

---

## Notes / terminology consistency
- `portal`: current portal/root context from routes
- `module`/`moduleName`: requested module key/name from routes
- `action`: requested action slug from routes (mapped to `run{Studly(action)}`)
- `id`: optional entity identifier
- `Actions/` directory: where module action classes are loaded from

---

## Files referenced (for quick navigation)
- `src/ModuleSDKServiceProvider.php`
- `src/routes/vendor_routes.php`
- `src/http/ModuleController.php`
- `src/http/SDKController.php`
- `src/core/ActionResolver.php`
- `src/Manifest/ManifestFactory.php`
- `src/Manifest/ManifestValidator.php`
- `vendor/schoolpalm/module-bridge/src/Providers/ModuleRegistrar.php`

