---
description: "Cortex tables use ULID primary keys (ulid('id')->primary()) and foreignUlid() foreign keys, so ids don't collide on export/import between environments or leak row counts."
tags: [cortex, migrations, ulid, keys]
kind: convention
---
# ULID Keys

Complements [[migrations]]: Cortex keys every table with ULIDs.

## Rules

- Primary keys are `$table->ulid('id')->primary()`.
- Foreign keys are `foreignUlid(...)->constrained(...)->cascadeOnDelete()`.
- Why: ULIDs avoid id collisions on export/import between environments and don't leak row counts; they pair with the slug as public identity ([[stored-identifiers]]).
