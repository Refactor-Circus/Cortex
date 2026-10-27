---
description: "MCP schema fields always carry ->description() stating defaults and behavior, shared fragments live in Concerns traits, and the #[Description] attribute is the fallback for versioned tool descriptions."
tags: [cortex, mcp, schemas, tools]
kind: convention
---
# MCP Schemas

Every MCP schema field has a `->description()`: it is the only documentation the calling model sees.

## Field descriptions

- State defaults and behavior ("Defaults to true.", "Replaces the whole list."); the model can't read the code.

```php
'slug' => $schema->string()->description('Unique identifier (letters, numbers, dashes, underscores).')->required(),
```

## Shared fragments

- A schema fragment used by two or more tools goes into a `Concerns` trait, so the tools never drift.

## Tool descriptions

- The `#[Description('...')]` attribute is the code-declared fallback. A published Cortex tool-description version overrides it at runtime (`HasVersionedDescription`).
- Keep the attribute accurate anyway: it is what ships when nothing is published.
