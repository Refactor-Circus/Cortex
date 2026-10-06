# Atrium screens use Atrium's components and styles only

- Atrium owns every component and style of the suite. Cortex ships no `resources/css`, no compiled stylesheet, no `Atrium::css()` / `Atrium::stylesheet()` call and no component namespace.
- Views are built from `x-atrium::*` components plus the layout utilities Atrium safelists. No `<style>` block and no `style` attribute. If a class is missing, use the closest safelisted one and ask for the class in Atrium.
- `tests/Feature/Ui/StylesTest.php` asserts `AtriumStyles::missingClasses()` and `AtriumStyles::inlineStyles()` are empty for `resources/views`.
- Generic pieces come from Atrium: `search-input` for in-place searches, `chip` for tag filters (`:href` + `:active` for linked filters, `x-bind:aria-pressed` for Alpine ones), `form.checkbox bare` for an unlabelled or externally labelled box, `flash :keys="['prompt', 'agent']"` for the status and the first prompt or agent error, `description-list` for label/value pairs.
- History: `<x-atrium::audit-trail source="cortex" />` on the virtual agents list, and `:subject="$model"` on the virtual agent form and the override partial (only once the override exists). It renders nothing until an audit log is installed. The versions partial stays: versions are publishable content, not history.
- Keep `data-testid` hooks when swapping markup for a component; components pass attributes through.
