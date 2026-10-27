---
description: "Every Cortex model is final, uses HasUlids and declares an explicit $fillable (never $guarded = [])."
tags: [cortex, models, eloquent, ulid]
kind: convention
---
# Cortex Models

Complements [[models]]: Cortex adds three rules to every model.

## Rules

- `final` class.
- `use HasUlids` on every model: ULIDs avoid id collisions on export/import between environments and don't leak row counts; they pair with the slug as public identity. See [[ulid-keys]] for the matching columns.
- An explicit `$fillable` listing the mass-assignable columns; never `$guarded = []`.

```php
final class VirtualAgentModel extends Model
{
    use HasUlids;

    protected $fillable = ['name', 'slug', 'description', ...];
}
```
