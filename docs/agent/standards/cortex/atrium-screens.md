---
description: "Cortex views use only x-atrium components and Atrium-safelisted utilities (no CSS, style blocks or component namespace), checked by StylesTest; every page declares a standard Atrium screen through Screens::declare() and renders its tabs and slots, with no history or audit components."
tags: [cortex, atrium, views, frontend, screens]
kind: convention
---
# Atrium Screens

Cortex screens are built only from Atrium's components and styles.

## No styles of its own

- Atrium owns every component and style in the suite. Cortex ships no `resources/css`, no compiled stylesheet, no `Atrium::css()` / `Atrium::stylesheet()` call and no component namespace.
- Views use `x-atrium::*` components plus the layout utilities Atrium safelists. No `<style>` block and no `style` attribute. If a class is missing, use the closest safelisted one and ask for the class in Atrium.
- `tests/Feature/Ui/StylesTest.php` asserts `AtriumStyles::missingClasses()` and `AtriumStyles::inlineStyles()` are empty for `resources/views`.

## Components to use

- `search-input` for in-place searches.
- `chip` for tag filters (`:href` + `:active` for linked filters, `x-bind:aria-pressed` for Alpine ones).
- `form.checkbox bare` for an unlabelled or externally labelled box.
- `flash :keys="['prompt', 'agent']"` for the status and the first prompt or agent error.
- `description-list` for label/value pairs.
- Keep `data-testid` hooks when swapping markup for a component; components pass attributes through.

## Declared screens

- Every Cortex page with a controller view declares itself with `RefactorCircus\Cortex\Atrium\Screens::declare($key, $kind, $record, $subject)` and resolves it with Atrium's `ScreenExtensions::resolve()` only after the controller's own authorization (on `run.store`, before the agent runs). The controller passes the result to the view as `resolved`.
- Keys are `cortex.{screen}`: `cortex.virtual-agents`, `cortex.virtual-agent-create`, `cortex.virtual-agent`, `cortex.concrete-agents`, `cortex.concrete-agent`, `cortex.run`, `cortex.tools`, `cortex.tool-description`, `cortex.servers`, `cortex.server-instructions` and `cortex.redirect-domains`. A new page adds its own key; a released key never changes, since other packages target it.
- Each is a standard screen (`Screen::standard()`): `header.actions`, `before` and `after`, plus `aside` on a detail screen or the row slot `row.actions` on a list screen. Only the synthetic `overview` tab is built in, and it is the default.
- The access closure asks an extension's `permission()` with `ScreenAccess::allowsFor($request->user(), $permission, $record ?? $subject)`: the screen's record, the row inside a row slot, or else the subject class (`VirtualAgentModel`, `ConcreteAgentOverrideModel`, `ToolDescriptionModel`, `McpInstructionModel` or `RedirectDomainModel` on the list and create screens). A screen with neither, such as `cortex.run`, grants no permission.
- A row is a model: the row's own record, or the unsaved override `ScreenAccess::concreteAgent()`, `toolDescription()` or `serverInstruction()` returns. A row that is no model, such as a configured redirect domain, gets no row slot.
- Layout follows Atrium's own screens: `header.actions` in `x-atrium::page-header`'s actions after the page's own (and no actions slot while both are empty), then `x-atrium::screen-tabs`. An open registered tab's `x-atrium::screen-tab-panel` replaces the page content. Otherwise `before` and `after` render around the content, `aside` in a right-hand column (`data-testid="screen-aside"`) only while it has visible sections, and `row.actions` at the end of each row's last cell. With nothing registered the markup is the page's own.
- No history slot, no audit subject and no history or audit components: History is Keen's, attached to every screen by wildcard. The versions partial stays: versions are publishable content, not history.
