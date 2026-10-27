---
description: Version rows are never edited; an updating() model hook throws, new content is a new row with the next version number, backed by a unique compound index.
tags: [cortex, models, versions, immutability]
kind: architectural
---
# Immutable Versions

Version rows are never edited: new content is a new row with the next version number.

## The hook

- Each version model throws from an `updating()` hook, so the invariant holds even against future code or tinker.
- Why model-level: agents depend on specific versions, and silently editing published content would change agent behavior invisibly.

```php
protected static function booted(): void
{
    self::updating(function (): never {
        throw new LogicException('Versions are immutable. Create a new version instead.');
    });
}
```

## Schema

- A unique compound index on parent key plus `version`; version numbers are assigned sequentially by the create action.

## New versioned entity

- Same shape: immutable rows, the hook and the unique compound index.
