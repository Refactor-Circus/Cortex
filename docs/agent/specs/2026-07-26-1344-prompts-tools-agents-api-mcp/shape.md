# Prompts, Tools, Agents (API + MCP) — Shaping Notes

## Scope

API-first + MCP-first package (`refactor-circus/cortex`) to manage prompts (with immutable versioning and a published pointer), tools, and agents/subagents on top of `laravel/ai`, with full API↔MCP tool parity. Includes execution: run an agent via API endpoint and MCP tool.

## Decisions

1. **DB agents + code tools** — tools are PHP classes registered in a `ToolRegistry` (config `cortex.tools` + runtime registration); agents are DB records referencing a prompt (pinned version or latest published), tool names, sub-agents, and provider/model/settings. A runtime factory builds a laravel/ai agent from the record.
2. **Management + execution** — CRUD for prompts/versions/agents, read-only tool listing, plus run-agent via `POST /cortex/agents/{slug}/run` and `RunAgentTool`.
3. **Immutable prompt versions + published pointer** — `cortex_prompts` + `cortex_prompt_versions`; editing content creates a new version; publish moves `published_version_id`.
4. **Shared validation rules per operation** — single static `rules()` on each Action consumed by both the HTTP FormRequest and the MCP request (deliberate departure from mono, which duplicates rules).
5. **Fail-closed defaults** — MCP web + local registration disabled by default; API routes ship with `['api']` middleware; README documents auth hardening.
6. **Attach/detach folded into create/update** with whole-list sync semantics (`tools`, `sub_agents`, `prompt` + `prompt_version`) — 16 operations instead of 22+.

## Context

- **Visuals:** None
- **References:** `~/Herd/mono` (FormRequest `persist()` → Action → Resource pattern, mirrored MCP layer); vendored `laravel/ai` v0.8.1 + `laravel/mcp` v0.8.2 sources (see references.md)
- **Product alignment:** N/A — `agent-os/product/` does not exist

## Standards Applied

- `agent-os/standards/index.yml` is an empty stub — no repo standards to inject. Package conventions from CLAUDE.md apply (Laravel-native package APIs, focused deps, tests on observable behavior).
