---
description: "Cortex views use only x-atrium components and Atrium-safelisted utilities (no CSS, style blocks or component namespace), checked by StylesTest; audit-trail for history."
tags: [cortex, atrium, views, frontend]
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

## History

- `<x-atrium::audit-trail source="cortex" />` on the virtual agents list, and `:subject="$model"` on the virtual agent form and the override partial (only once the override exists). It renders nothing until an audit log is installed.
- The versions partial stays: versions are publishable content, not history.
