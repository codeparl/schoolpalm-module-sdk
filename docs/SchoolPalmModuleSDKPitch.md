# SchoolPalm Module SDK — Pitch Documentation (Concept Overview)

## What it is
SchoolPalm **Module SDK** is a framework that makes it easy to plug new feature modules into the SchoolPalm platform.

A “module” can provide:
- UI screens (pages/components)
- backend actions (create/edit/list operations)
- data operations, workflows, and permissions

The SDK standardizes how modules are described and how they run, so each new module can integrate cleanly without rebuilding the platform every time.

---

## The core idea
In a modular product, the main challenge isn’t writing features—it’s **integration**:
- How does the platform *discover* modules?
- How does it *route requests* to the correct module?
- How does it keep module execution consistent?
- How does it avoid breaking the platform when modules evolve?

The Module SDK answers these questions by providing:
- a standardized module “contract” (metadata + conventions)
- a runtime execution path (route → module → action)
- a controlled installation/registration process
- integration with a module registry system shared with the platform

---

## Architecture (high-level)
### 1) Module description (metadata)
Each module is described using a manifest (a JSON file) that includes things like:
- module identity (key/name)
- module capabilities (what actions it supports)
- wiring information (what classes/providers it exposes)
- optional UI/menu/entry details

This turns “a folder of code” into something the platform can understand.

### 2) Module registration and discovery
The SDK works with a shared registry system (provided by **module-bridge**) to:
- load module namespaces
- register module providers automatically
- build platform-wide metadata/relations used for module coordination

This means modules become discoverable without hardcoding module locations.

### 3) Runtime execution pipeline
When a user navigates to a module route:
1. Laravel receives a request containing route segments like **portal**, **module**, and **action**.
2. The SDK resolves which module should run.
3. The platform maps the requested **action** into the correct module execution method.
4. The module returns results (JSON for AJAX calls, normal responses for page rendering).

This creates a consistent execution model across all modules.

### 4) UI integration through Inertia
The SDK also supports a unified UI rendering approach (commonly via Inertia) so modules can supply:
- dashboards/pages
- interactive experiences

The platform hosts module UIs in a standardized way.

---

## Why the SDK is needed
Without an SDK, each module must reinvent integration:
- custom routing logic
- custom bootstrapping/registration
- inconsistent action naming/execution
- manual wiring for providers, menus, and dashboards

That increases maintenance cost and makes the platform fragile as the number of modules grows.

The SDK reduces that risk by:
- ensuring every module follows the same runtime conventions
- enabling automatic discovery/registration
- providing predictable behavior across module teams
- making module installation repeatable and safe

---

## How module-bridge fits in
The **module-bridge** layer is the platform’s registry and wiring backbone.

The Module SDK depends on it to:
- register modules (namespaces/providers) dynamically
- load snapshots/execution paths when needed
- build a merged relations registry used by higher-level platform logic

So, conceptually:
- **module-bridge** = “platform wiring & discovery layer”
- **module-sdk** = “module creation + module execution layer”

---

## The value proposition
The result is a scalable modular platform:
- faster module delivery (standard tooling)
- cleaner integration (consistent runtime model)
- safer evolution (schema validation + predictable installation)
- reduced platform customization per module

---

## One-sentence pitch
**SchoolPalm Module SDK is the standardized framework that turns module code into a discoverable, installable, runtime-executing feature—integrated with the platform through module-bridge so new modules can be added without platform fragmentation.**

