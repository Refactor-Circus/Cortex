---
description: "Cortex's base Http\\Request always asks the Gate, as the signed-in user or as a guest, instead of following the shared runtime's authorization switch, because Cortex's bundled policies allow guests."
tags: [cortex, http, requests, authorization]
kind: convention
---
# HTTP Requests

Complements [[request-persist]] and [[authorization]]: Cortex's base request decides how the policy check is asked.

## Base request

- Every HTTP request extends `RefactorCircus\Cortex\Http\Request`, which extends Keystone's `Http\Requests\Request`.
- Its `allows()` always asks the Gate, as the signed-in user or as a guest (`Gate::forUser($this->user())`), rather than following the shared runtime's `authorization` switch.
- Why: Cortex's bundled policies allow guests, so the route middleware stays the gate until a host application registers stricter policies; skipping the Gate would ignore those policies.
- Never bypass `allows()` in a request; the MCP base `Mcp\Request` asks the Gate the same way, so both surfaces decide alike.
