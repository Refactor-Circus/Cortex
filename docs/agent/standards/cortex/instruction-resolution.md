---
description: "AgentFactory resolves a virtual agent's instructions from its published version through PublicationCache; null (unpublished) is never cached and throws VirtualAgentNotPublishedException; publishing actions invalidate the key."
tags: [cortex, agents, publishing, cache]
kind: convention
---
# Instruction Resolution

`AgentFactory` reads an agent's instructions from its published version through `PublicationCache`, and an agent with nothing published fails loudly.

## Order

- Published version: read through `PublicationCache::remember()` with the cache's own key helper (`virtualAgentKey()`).
- Nothing published: `VirtualAgentNotPublishedException`.

```php
$published = $this->cache->remember(
    $this->cache->virtualAgentKey((string) $agent->getKey()),
    fn (): ?string => $agent->publishedVersion?->content,
);
```

## Rules

- `null` (unpublished) is never cached, so publishing takes effect immediately without waiting for expiry.
- Publishing actions invalidate the key explicitly; anything else that touches publication state must do the same. See [[publication-cache]].
