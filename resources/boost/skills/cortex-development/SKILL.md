---
name: cortex-development
description: >
  Configure and apply the Cortex package in Laravel applications: DB-backed
  virtual agents with versioned prompts, registered concrete (class-based)
  agents with overridable prompts and toolsets, a tool registry with
  versioned description overrides, and an MCP server registry with versioned
  instruction overrides and tool list overrides, exposed through a JSON API
  (described by openapi.json and a typed TypeScript client) and an MCP
  server, with Atrium v2 as the optional UI.
license: MIT
metadata:
  author: Jay Fletcher
---

# Cortex

Use this skill when a Laravel application needs to integrate the `refactor-circus/cortex` package to configure or run virtual agents (DB records with immutably versioned prompts), manage concrete agents defined in code, and register AI tools, all built on the Laravel AI SDK.

## Primary Goal

- apply the `refactor-circus/cortex` package's public API in the smallest correct way

## Workflow

### 1. Install and migrate

```bash
composer require refactor-circus/cortex
php artisan vendor:publish --tag="cortex-migrations"
php artisan migrate
```

Publish config only when route prefix, middleware, MCP transports, or config-registered tools must change:

```bash
php artisan vendor:publish --tag="cortex-config"
```

### 2. Secure the surface before enabling it

The API routes and MCP server manage **and execute** agents. MCP transports are disabled by default and the routes ship on `api` middleware only. Always add auth middleware to the routes and the MCP web transport before production exposure; Atrium v2 (step 3) calls the same routes, so it passes the same middleware:

```php
// config/cortex.php
'routes' => ['enabled' => true, 'prefix' => 'cortex', 'middleware' => ['api', 'auth:sanctum']],
'mcp' => [
    'web' => ['enabled' => true, 'route' => 'mcp/cortex', 'middleware' => ['auth:sanctum']],
    'local' => ['enabled' => true, 'handle' => 'cortex'],   // php artisan mcp:start cortex
],
```

Every API endpoint and MCP tool that touches a model is also checked through the Gate against `cortex.policies`, as the signed-in user or as a guest. Cortex records have no owner, so the bundled policies allow everything and the middleware stays the gate. To restrict something, extend the bundled policy (e.g. `RefactorCircus\Cortex\Domains\VirtualAgent\Policies\VirtualAgentPolicy`), override the ability (`update`, `delete`, `publish`, `run`, ...) and point the model at it in `cortex.policies`. Version policies defer to their virtual agent or override: `view` to read, `update` to add or publish. Type the user parameter as non-nullable to refuse guests.

### 3. Add a UI with Atrium v2 (optional)

Cortex's PHP is API and MCP only: no views, screens or Blade directives. For screens, install Atrium v2 (`refactor-circus/atrium`): Cortex ships its Atrium front-end module (JavaScript built to `dist/atrium/`, declared in `composer.json` `extra.atrium`), which Atrium's page loads and which manages virtual agents, concrete agents, runs, tools, MCP servers and owners' redirect domains by calling Cortex's JSON API through `@refactor-circus/cortex-client`. Configure its Cortex integration on Atrium's side (`atrium.integrations.cortex.*`, see Atrium's README), keep `cortex.routes.enabled` on, and make sure Atrium's users pass `cortex.routes.middleware`; every call is checked against `cortex.policies` like any other API call.

### 4. Register tools

Tools implement `Laravel\Ai\Contracts\Tool` (`description()`, `handle()`, `schema()`) or extend `Laravel\Mcp\Server\Tool` (wrapped for agent use automatically). String config keys set the registered name; unkeyed entries derive it from the tool:

```php
// config/cortex.php
'tools' => [
    'search' => \App\Ai\Tools\SearchTool::class,
    \App\Mcp\Tools\LookupTool::class,
],

// or at runtime (e.g. in a service provider):
use RefactorCircus\Cortex\Facades\Cortex;
Cortex::tools()->register('search', \App\Ai\Tools\SearchTool::class);
```

Tag tools to group them for tool lists and agent tool pickers: `Cortex::tools()->register($name, $class, ['catalog'])`, a config entry `'lookup' => ['class' => LookupTool::class, 'tags' => ['catalog']]`, or namespace patterns under `cortex.tool_tags.namespaces` (default `App\\Domains\\{tag}\\` and `App\\Modules\\{tag}\\`, `{tag}` = one namespace segment, kebab-cased). Packages registering their own tools should tag them with the package name. Filter with `GET /cortex/tools?tag=` or the `list-tools-tool` `tag` argument.

To let a tool's description be overridden at runtime (versioned + published like agent prompts), extend `RefactorCircus\Cortex\Domains\Tool\Support\Tool` or use the `RefactorCircus\Cortex\Domains\Tool\Concerns\HasVersionedDescription` trait. Manage overrides through the `/cortex/tools/{tool}/description` endpoints.

MCP server *instructions* work the same way. Cortex's own server is always registered as `cortex`; register app servers under `cortex.mcp.servers` config (string keys name them; unkeyed entries derive the name from `#[Name]` or the class basename) or at runtime with `Cortex::servers()->register('support', \App\Mcp\SupportServer::class)`. For published overrides to be served to MCP clients, the server must extend `RefactorCircus\Cortex\Domains\ConcreteServer\Support\Server` or use the `RefactorCircus\Cortex\Domains\ConcreteServer\Concerns\HasCortexOverrides` trait. The old `Domains\McpServer\Support\Server` and `Concerns\HasVersionedInstructions` names still load as deprecated aliases (removed next major); write new code against `Domains\ConcreteServer`.

A registered server's *tool list* can be overridden too: `PUT /cortex/servers/{server}/tools` `{tools: [...]}` or the `update-server-tools-tool` MCP tool stores a list of names (a code tool's `name()` first, otherwise a registered Cortex tool name; the code tool wins when both match), and clients get exactly that list in that order (`[]` serves no tools). `DELETE /cortex/servers/{server}/tools` or `clear-server-tools-tool` restores the code tools. The `cortex` server cannot be overridden. Stale entries are skipped and reported in `missing_tools`. Both check the `updateTools` ability, which the bundled policy allows for guests: before exposing the routes, give `McpInstructionModel` a policy whose `updateTools` returns false for guests (and anyone you do not trust to put any registered tool in front of that server's clients). The tool override needs the `tools` column migration (`vendor:publish --tag="cortex-migrations"`, `migrate`).

### 5. Register concrete agents

Class-based agents extend `RefactorCircus\Cortex\Domains\ConcreteAgent\Support\Agent` and declare `defaultInstructions()` and `defaultTools()`:

```php
use RefactorCircus\Cortex\Domains\ConcreteAgent\Support\Agent;

class TriageAgent extends Agent
{
    public function defaultInstructions(): string
    {
        return 'Decide whether the report duplicates an open issue.';
    }

    public function defaultTools(): iterable
    {
        return [new \App\Ai\Tools\SearchIssues];
    }
}
```

Register under `cortex.agents` config (string keys name them; unkeyed entries use the kebab-cased class basename) or with `Cortex::agents()->register('triage', TriageAgent::class)`. The agent then runs everywhere with the published Cortex prompt override and toolset override when they exist. The toolset override picks from the class's own tools and registered Cortex tools and stores registered names; unresolvable names are skipped at run time. Add `#[RefactorCircus\Cortex\Domains\ConcreteAgent\Support\LockedTools]` to keep the code toolset: Cortex then rejects toolset overrides (only `null` is accepted) and ignores saved ones, while the prompt stays overridable. Use the `RefactorCircus\Cortex\Domains\ConcreteAgent\Concerns\HasCortexOverrides` trait if the class cannot extend the base. Registered agents without it can still be listed, run and used as sub-agents, but ignore overrides.

### 6. Manage agents via the API (or MCP tools)

- `POST /cortex/virtual-agents` — `{name, slug, instructions, provider?, model?, settings?, tools?, sub_agents?, concrete_sub_agents?}`. `instructions` becomes prompt version 1, published. `tools` are registered tool names; `sub_agents` are virtual agent slugs; `concrete_sub_agents` are registered concrete agent names. The lists use sync semantics (send the full desired list). Circular sub-agent references are rejected.
- `PATCH /cortex/virtual-agents/{slug}` — same fields; changed `instructions` are saved as a new published version.
- `GET /cortex/virtual-agents` `?page=&per_page=` — paginated (`per_page` 1-100, default 15), as are the version lists.
- `GET|POST /cortex/virtual-agents/{slug}/versions`, `GET .../versions/{version}`, `POST .../versions/{version}/publish` — prompt versions (rollback = publish an older version).
- `POST /cortex/virtual-agents/{slug}/run` `{input}` — execute; returns `{text, usage}`.
- `GET /cortex/concrete-agents`, `GET /cortex/concrete-agents/{agent}` — registered concrete agents with live and code-declared prompt and tools.
- `GET|POST /cortex/concrete-agents/{agent}/versions`, `POST .../versions/{version}/publish` — prompt override versions.
- `PUT /cortex/concrete-agents/{agent}/tools` `{tools: [...] | null}` — set or clear the toolset override; `DELETE /cortex/concrete-agents/{agent}/override` removes both overrides.
- `POST /cortex/concrete-agents/{agent}/run` `{input}` — execute; returns `{text, usage}`.
- `GET /cortex/search?q=` (route `cortex.search.index`, MCP `search-agents-tool`) — up to 5 virtual agents (name or slug contains `q`) then up to 5 concrete agents (name contains `q`, case-insensitive), as `{type: virtual_agent|concrete_agent, key, name}`; `key` is the slug or registered name. Needs `viewAny` on either agent model; each result is kept only when the caller may `view` it.
- `GET /cortex/runnable-agents` (route `cortex.runnable-agents.index`, MCP `list-runnable-agents-tool`) — every virtual then concrete agent the caller may `run`, same item shape, unpaginated.
- `GET /cortex/settings-summary` (route `cortex.settings-summary.show`, MCP `show-settings-summary-tool`) — `{providers: [{name, models}], tool_count, agent_count, server_count, cache_enabled}`; asks no policy, like `GET /cortex/providers`.
- `GET /cortex/providers` — providers with models and default model. Curate via `cortex.providers` config (authoritative when non-empty); otherwise every text-capable laravel/ai provider is offered.
- `GET /cortex/tools` — registered tools with JSON schemas.
- `GET|DELETE /cortex/tools/{tool}/description`, `GET|POST .../description/versions`, `POST .../versions/{version}/publish` — versioned tool description overrides.
- `GET /cortex/servers` — registered MCP servers with their effective instructions.
- `GET|DELETE /cortex/servers/{server}/instructions`, `GET|POST .../instructions/versions`, `POST .../versions/{version}/publish` — versioned server instruction overrides (rollback = publish an older version). The delete keeps a tool override (answering 200 with the instructions resource when one is kept, 204 otherwise).
- `PUT|DELETE /cortex/servers/{server}/tools` — set (`{tools: [...]}`) or clear a server's tool override; needs `updateTools`.
- `GET /cortex/history` — Cortex's audit history, newest first (route `cortex.history.index`, from refactor-circus/keystone); answers 404 until an audit log such as refactor-circus/keen is installed.

The MCP server (`RefactorCircus\Cortex\Mcp\CortexServer`) exposes the virtual agent, concrete agent, tool-list, server-instruction, server-tool and redirect-domain operations as 34 MCP tools, including `search-agents-tool`, `list-runnable-agents-tool`, `show-settings-summary-tool`, `update-server-tools-tool`, `clear-server-tools-tool` and `list-cortex-history-tool` (offered only while an audit log is installed and `cortex.audit.enabled` is on); the provider and tool-description endpoints are HTTP-only. Structured answers match the JSON API: the record under `data` (lists add `meta` when paginated), and every such tool declares an `outputSchema()`. A guest the policies refuse gets 401 over HTTP, a signed-in user 403; missing records answer `{"message": "Not found."}`.

For a JavaScript or TypeScript front end, use the committed `openapi.json` (every `cortex.*` route, paths relative to `cortex.routes.prefix`) or the typed client in the package's `client/` folder, `@refactor-circus/cortex-client` (TypeScript source, not on npm): `createCortexClient({ baseUrl: 'https://app.test/cortex', headers, fetch, credentials })` returns an openapi-fetch `Client<paths>`, e.g. `client.GET('/search', { params: { query: { q } } })`. Cortex maintainers regenerate both with `composer client:generate` (needs Node); tests fail while either is stale.

From code, call the Actions with their typed input (`RefactorCircus\Keystone\Data\Data` classes in each domain's `Data` namespace), never arrays: `app(CreateVirtualAgentAction::class)->execute(new CreateVirtualAgentData(name: 'Support', slug: 'support-agent', instructions: '...'))`, or `CreateVirtualAgentData::from($input)` to validate untrusted input first. `UpdateVirtualAgentData` changes only the fields given. Cortex stands on `refactor-circus/keystone`: its event contracts live in `RefactorCircus\Keystone\Contracts`.

Published agent prompts, concrete agent overrides, description overrides, and server instruction overrides are cached (`cortex.cache` config: Redis preferred with stale-while-revalidate, other stores cache until publish invalidates; disable with `'cache' => ['enabled' => false]`).

### 7. Run agents from code

```php
use RefactorCircus\Cortex\Facades\Cortex;

$response = Cortex::runVirtualAgent('coordinator', 'Summarize the open tickets.');
$response->text;

Cortex::virtualAgent('coordinator')->stream('...'); // full laravel/ai agent
Cortex::runConcreteAgent('triage', '...');
```

Provider/model/settings fall back to the app's `config/ai.php` defaults when unset on a virtual agent.

### 8. React to changes

- Model events: one class per Eloquent hook per model, e.g. `RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionCreatedEvent` (`$event->version`).
- Action events: a start and a finish event per action, e.g. `VirtualAgentVersionPublishingActionEvent` then `VirtualAgentVersionPublishedActionEvent` (`$event->agent`), or `VirtualAgentRunningActionEvent` then `VirtualAgentRanActionEvent` (`$event->agent`, `$event->data->input`, `$event->response`). Each action writes inside one database transaction; finish events fire after commit and only on success.
- Listen to `RefactorCircus\Keystone\Contracts\ActionFinishedEvent` or `ModelLifecycleEvent` to see a whole family. In tests, fake only the events you assert on: `Event::fake([VirtualAgentVersionPublishedActionEvent::class])`.

### 9. Test the integration

```php
use RefactorCircus\Cortex\Domains\VirtualAgent\Support\DbAgent;

DbAgent::fake(['Canned response.']);      // virtual agents
TriageAgent::fake(['Canned triage.']);    // concrete agents fake through their own class
// ...run the agent via API, MCP, or Cortex::runVirtualAgent()...
DbAgent::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, 'tickets'));
```

Always fake — unfaked runs require a configured AI provider.

## Rules, References, and Templates

Read before executing:

- `config/cortex.php` — routes, MCP transports, cache, providers, tools, concrete agents, policies, tables (`cortex.tables`: rename before migrating; read through `RefactorCircus\Cortex\Support\Tables::name()`, never literal `cortex_*` names)
- `docs/policies.md` — which ability each endpoint and MCP tool checks
- `docs/events.md` — every action with its start and finish events
- `README.md` — full route table and payload examples
- `openapi.json` — the machine-readable API description the TypeScript client is generated from

## Examples

- Wire a support agent: register a `search` tool, `POST /cortex/virtual-agents` with `{"name": "Support", "slug": "support-agent", "instructions": "...", "tools": ["search"]}`, then `Cortex::runVirtualAgent('support-agent', $question)`.
- Safe prompt iteration: `POST /cortex/virtual-agents/support-agent/versions` with new content (unpublished), verify, then publish it.
- Tune a code agent without a deploy: register it under `cortex.agents`, then publish a prompt override version or `PUT .../tools` for it; `DELETE .../override` returns it to its code declarations.

## Anti-patterns

- do not document package internals here; keep the skill focused on adoption in Laravel apps
- do not edit prompt version content — versions are immutable; create a new version and publish it (concrete agent, tool description and server instruction override versions work the same way)
- do not enable the MCP web transport or expose the routes without auth middleware
- do not look for Cortex views, screens or UI config: Cortex v2's PHP has none; its screens are the Atrium module in `atrium/` (built to `dist/atrium/`), loaded by Atrium v2
- do not attach tool class names to agents — attach the registered tool *names* from the registry
- do not leave `updateTools` open to guests in production, and do not register Cortex's own management tools in the tool registry unless every client of the servers you override may manage Cortex
