---
description: "Support\\PublicationCache caches published content only (flexible() on Redis with cortex.cache.fresh/stale, else rememberForever), pinned by cortex.cache.store; every publish, unpublish or delete must forget the key."
tags: [cortex, cache, publishing, config]
kind: convention
---
# Publication Cache

`Support\PublicationCache` caches only published content: data that changes solely when someone publishes.

## Stores

- Redis available: `flexible()` stale-while-revalidate with the `cortex.cache.fresh` / `cortex.cache.stale` windows.
- Otherwise: `rememberForever()` until explicit invalidation.
- `cortex.cache.store` pins the store. Tests pin `array`, because the Redis probe would otherwise find a local Redis and leak state.

## Obligations

- Every action that changes publication state (publish, unpublish, delete) must `forget()` the entity's key; with the non-Redis store the cache never expires published content on its own.
- Keys come from the cache's own helpers (`virtualAgentKey()`, `toolDescriptionsKey()`, ...), never hand-built strings.
- Never use it for anything that changes outside the publish flow; that is what makes `rememberForever()` safe.
