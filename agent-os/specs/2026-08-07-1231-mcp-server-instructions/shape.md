# MCP Server Instructions — Shaping Notes

## Scope

Versioned instruction management for MCP servers, mirroring the existing tool-description management feature: DB-backed override versions with a publish pointer, HTTP API, MCP management tools (full parity — unlike tool descriptions, which are HTTP-only), and a new "Servers" section in the packaged UI. Published instructions override the code-declared `#[Instructions]` attribute at runtime; removing the override falls back to code.

## Decisions

- **Server identity: registry pattern.** No `McpServer` model or per-server config existed — the package ships one hardcoded server (`CortexServer`) whose instructions are a static `#[Instructions]` attribute. New `McpServerRegistry` mirrors `ToolRegistry`: singleton, string-name keys, built-in `cortex` server registered first, then `config('cortex.mcp.servers')`, plus runtime registration via `Cortex::servers()->register()` so consumer apps' own servers become overridable. DB keys off `cortex_mcp_instructions.server` (unique string), exactly like `cortex_tool_descriptions.tool`.
- **Full HTTP↔MCP parity.** Six MCP tools (list servers, show instructions, list versions, create version with publish flag, publish version, remove override), each with mcp-http-parity tests. This exceeds the tool-description feature (HTTP-only) deliberately, per user choice.
- **UI: new Servers nav section.** Sidebar entry after Tools; servers index page + per-server instructions editor cloned from `ToolDescription.vue`.
- **Runtime hook (vendor-verified).** `Laravel\Mcp\Server::createContext()` is the single instructions resolution point; `ServerContext::$instructions` is a promoted public non-readonly property. A `HasVersionedInstructions` trait overrides `createContext()`, calls parent, and mutates `$context->instructions` when an override is published. New abstract `JayI\Cortex\Domains\McpServer\Support\Server` base carries the trait (mirror of `JayI\Cortex\Domains\Tool\Support\Tool` + `HasVersionedDescription`).
- **Registry never instantiates server classes** — the `Server` constructor requires a `Transport`; default instructions are read via reflection (attribute walk, then property default).
- **Rollback = publish an older version** (no separate endpoint), versions immutable, no FK on the publish pointer — all per existing standards.

## Context

- **Visuals:** None
- **References:** Tool-description feature (API/UI shape), prompt-version MCP tools (MCP shape) — see references.md
- **Product alignment:** N/A (no agent-os/product/)

## Standards Applied

See standards.md — the load-bearing ones: `database/publish-pointer` (pointer column + publish action + cache invalidation shape), `database/immutable-versions` (versioned-entity shape), `backend/mcp-*` (tool/request/response/schema conventions), `testing/mcp-http-parity` (every MCP mutation tool asserts payload parity with HTTP).
