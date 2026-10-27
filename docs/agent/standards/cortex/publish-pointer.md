---
description: "Publishable entities point at their live version with an FK-free published_version_id column; integrity is enforced at publish with firstOrFail(), and publish/unpublish invalidates PublicationCache."
tags: [cortex, migrations, publishing, versions]
kind: architectural
---
# Publish Pointer

A publishable entity points at its live version with a `published_version_id` column that deliberately has no foreign key.

## Why no FK

- The parent table and its versions table reference each other; a foreign key would deadlock creation order. The migration says so in a comment.

```php
// No FK: circular reference with the versions table; integrity
// is enforced when publishing.
$table->ulid('published_version_id')->nullable()->index();
```

## Integrity at the seam

- The publish action resolves the version with `firstOrFail()` before assigning it.
- Publishing and unpublishing invalidate `PublicationCache` for the entity; see [[publication-cache]].
- Unpublished is a `null` pointer; the resource serializes `published_version` only `whenLoaded`.

## New publishable entity

- Copy the shape: pointer column, migration comment, publish action, cache invalidation.
