---
description: Structured MCP responses wrap payloads in a data envelope, built from the shared HTTP resources; empty lists ship as data []; errors use Response::error.
tags: [cortex, mcp, responses, resources]
kind: convention
---
# MCP Response Shape

Every structured MCP response wraps its payload in a `data` envelope, built from the same resource the HTTP API uses ([[resources]]).

## Envelope

- Lists and single items both answer `{ "data": ... }`.
- The envelope is mandatory for lists: an empty list ships as `{ "data": [] }`, never an empty structured response, which throws.
- Some older single-item responses return the bare resource with no envelope. That is legacy: wrap it in `data` when touched, don't copy it.

```php
return (new VirtualAgentResource($agent))->toStructuredResponse();
return VirtualAgentResource::collection($page)->toStructuredResponse();
```

## Errors

- Errors use `Response::error('...')`. The base request maps `ModelNotFoundException` to `Not found.`; see [[mcp-model-binding]].
