<div align="center">
    <h1>Cortex</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/jayi/cortex"><img src="https://img.shields.io/packagist/v/jayi/cortex.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/jayi/cortex"><img src="https://img.shields.io/packagist/php-v/jayi/cortex.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/jayi/cortex"><img src="https://badge.laravel.cloud/badge/jayi/cortex?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/jayi/cortex/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/jayi/cortex/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/jayi/cortex"><img src="https://img.shields.io/packagist/dt/jayi/cortex.svg?style=flat-square" alt="Total Downloads"></a>
</p>

AI orchestration for Laravel. Cortex manages **virtual agents, concrete agents, tools, and MCP servers** on top of the [Laravel AI SDK](https://laravel.com/docs/ai-sdk), exposed through a REST API, a prebuilt dashboard, and an [MCP](https://laravel.com/docs/mcp) server mirroring the same operations.

- **Virtual agents** are database records that combine their own versioned prompt, registered tools, provider/model settings, and other agents (virtual or concrete) as sub-agents. Prompt content is immutable per version and a published pointer decides what runs; roll back by publishing an older version.
- **Concrete agents** are classes in your code. Register them with Cortex to list, run and attach them as sub-agents, and override their prompt (versioned, publishable) and toolset without a deploy.
- **Tools** are PHP classes implementing `Laravel\Ai\Contracts\Tool` or extending `Laravel\Mcp\Server\Tool` (wrapped automatically), registered by name in the Cortex tool registry. Their descriptions can be overridden at runtime with versioned, publishable content.
- **MCP servers** registered with Cortex get the same treatment for their instructions: versioned, publishable overrides that replace the code-declared instructions served to MCP clients, manageable over HTTP, MCP, and the dashboard.

Run either kind via the API, the dashboard, the MCP server, or the `Cortex` facade.

## Installation

```bash
composer require jayi/cortex
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="cortex-migrations"
php artisan migrate
```

Publish the config file to customize routes, the dashboard, MCP transports, and tools:

```bash
php artisan vendor:publish --tag="cortex-config"
```

The dashboard renders through Atrium, so publish its assets too:

```bash
php artisan vendor:publish --tag="atrium-assets"
```

## Configuration

```php
return [
    'routes' => [
        'prefix' => 'cortex',
        'middleware' => ['api'],
    ],
    'ui' => [
        'enabled' => true, // register Cortex with the Atrium dashboard
    ],
    'mcp' => [
        'web' => ['enabled' => false, 'route' => 'mcp/cortex', 'middleware' => []],
        'local' => ['enabled' => false, 'handle' => 'cortex'],
        'servers' => [
            // 'support' => \App\Mcp\SupportServer::class,
        ],
    ],
    'cache' => [
        'enabled' => true,
        'store' => null,
        'fresh' => 300,
        'stale' => 86400,
    ],
    'providers' => [
        // 'anthropic' => ['claude-sonnet-5-5', 'claude-opus-5-5'],
    ],
    'tools' => [
        // 'search' => \App\Ai\Tools\SearchTool::class,
        // \App\Mcp\Tools\LookupTool::class,
    ],
    'agents' => [
        // 'triage' => \App\Ai\Agents\TriageAgent::class,
    ],
    'policies' => [
        // VirtualAgentModel::class => VirtualAgentPolicy::class, ... one entry per Cortex model
    ],
];
```

> [!WARNING]
> The API routes, dashboard, and MCP server manage **and execute** agents. The MCP transports are disabled by default and the API carries only the `api` middleware group. Before exposing them in production, add authentication — e.g. `'middleware' => ['api', 'auth:sanctum']` for the routes and `'middleware' => ['auth:sanctum']` for the MCP web transport. The dashboard is guarded by Atrium's `viewAtrium` gate; define it as shown under [Dashboard](#dashboard).

## Authorization

Every API endpoint and MCP tool that touches a model checks it through the Gate, using the policies in `cortex.policies`. It checks as the signed-in user, or as a guest when nobody is signed in. Listing or creating checks `viewAny` or `create` against the model class. Reading, changing, deleting, publishing or running checks `view`, `update`, `delete`, `publish` or `run` against the record.

Cortex records have no owner, so the bundled policies allow everything and your middleware stays the gate, as before. Version policies defer to their virtual agent or override through the Gate: reading a version needs `view` on it, adding or publishing one needs `update`. Point a model at your own policy class in `cortex.policies` to restrict it.

**Full guide:** [Policies](docs/policies.md). It covers what each endpoint and MCP tool checks, and how to replace a policy.

## Dashboard

Cortex renders its dashboard through [Atrium](https://github.com/jayi/atrium), which it requires. Install Atrium's gate and Cortex appears in the sidebar automatically:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('viewAtrium', fn ($user) => $user->is_admin);
```

The dashboard covers virtual agents with their prompt versions, concrete agents with their prompt and toolset overrides, a run playground, the tool registry (filterable by tag) with a versioned description editor, and the MCP server registry with a versioned instructions editor.

Atrium owns the path, the middleware and the authorization gate, so there is nothing to configure here beyond the single switch:

```php
// config/cortex.php
'ui' => ['enabled' => true],
```

Setting it to `false` removes Cortex from the dashboard and leaves the JSON API serving.

Each page, action and control is checked against the same policies as the API: a navigation item, button, form or card shows only when its action would be allowed, and the action answers `403` otherwise. Views ask with `@cortexCan('update', $agent)`, the same check the controllers make. See [Policies](docs/policies.md#the-dashboard).

The screens follow Atrium's screen conventions: actions are icon buttons with their label as tooltip, states (override or code, published, locked) are status dots with a `data-status` attribute, and every navigation item has an icon. `JayI\Cortex\Atrium\Badges` maps each state to its colour.

To switch Cortex in the dashboard on and off as a whole - navigation, search, settings and pages, which answer `404` while it is off - install [jayi/pennantplus](https://github.com/jayjfletcher/PennantPlus). `JayI\Cortex\Atrium\Features\CortexSupportFeature` is on until its global value is set, and only its global value counts:

```php
use JayI\Cortex\Atrium\Features\CortexSupportFeature;
use Laravel\Pennant\Feature;

Feature::for(null)->deactivate(CortexSupportFeature::class);
```

The features checked are listed in `cortex.atrium.features`; point it at a subclass or your own feature names, or empty it to always show Cortex. Without jayi/pennantplus the class cannot load and is skipped, so nothing is checked.

> **Authentication.** The pages are server-rendered under Atrium's path (`/atrium/cortex/...` by default) behind its `web` middleware and `viewAtrium` gate, so they authenticate the way the rest of your application does. Without a `viewAtrium` gate, Atrium allows only the `local` environment.

## Registering Tools

Tools implement `Laravel\Ai\Contracts\Tool` or extend `Laravel\Mcp\Server\Tool` — MCP tools are wrapped for agent use automatically. Register them in `config/cortex.php` under `tools` (string keys set the registered name; unkeyed entries derive it from the tool itself), or at runtime:

```php
use JayI\Cortex\Facades\Cortex;

Cortex::tools()->register('search', \App\Ai\Tools\SearchTool::class);
```

### Tool Tags

Tags group tools on the dashboard's tool list and in the agent tool pickers, which filter by tag, name and selection. A tool gets the tags given when it is registered plus one per matching namespace pattern:

```php
// config/cortex.php
'tools' => [
    'lookup' => ['class' => \App\Mcp\Tools\LookupTool::class, 'tags' => ['catalog']],
],

'tool_tags' => [
    // `{tag}` is one namespace segment: App\Domains\Order\Mcp\ShowOrderTool is tagged `order`.
    'namespaces' => ['App\\Domains\\{tag}\\', 'App\\Modules\\{tag}\\'],
],

// or at runtime:
Cortex::tools()->register('search', \App\Ai\Tools\SearchTool::class, ['catalog']);
```

Tags are kebab-cased. `GET /cortex/tools?tag=catalog` and the `list-tools-tool` MCP tool's `tag` argument list only the tools carrying a tag, and every listed tool includes its `tags`.

### Tool Description Overrides

A tool's code-declared description can be overridden without a deploy: each tool has an optional, immutably versioned description with a published pointer — same model as agent prompts. Manage overrides from the dashboard or the API (`/cortex/tools/{tool}/description`). Extend `JayI\Cortex\Domains\Tool\Support\Tool` (or use the `JayI\Cortex\Domains\Tool\Concerns\HasVersionedDescription` trait on an existing MCP tool) so the tool also serves its published override when used directly outside Cortex.

## Concrete Agents

A concrete agent is a `Laravel\Ai\Contracts\Agent` class. Extend `JayI\Cortex\Domains\ConcreteAgent\Support\Agent` and declare the prompt and toolset in `defaultInstructions()` and `defaultTools()`:

```php
use JayI\Cortex\Domains\ConcreteAgent\Support\Agent;

class TriageAgent extends Agent
{
    public function __construct(private SearchIssues $search) {}

    public function defaultInstructions(): string
    {
        return 'Decide whether the report duplicates an open issue.';
    }

    public function defaultTools(): iterable
    {
        return [$this->search];
    }
}
```

Register it in `config/cortex.php` under `agents` (string keys set the registered name; unkeyed entries use the kebab-cased class basename), or at runtime:

```php
Cortex::agents()->register('triage', \App\Ai\Agents\TriageAgent::class);
```

Wherever the agent runs — your own `TriageAgent::make()->prompt(...)`, the API, the dashboard or MCP — it uses the published Cortex overrides when they exist and its code declarations otherwise:

- **Prompt:** versioned and publishable, like tool descriptions. Removing the override restores the code prompt.
- **Tools:** a replacement list picked from the class's own tools (by the name the model sees) and the registered Cortex tools (by registered name). Clearing it restores the code toolset. Names that no longer resolve are skipped at run time.

Mark an agent `#[JayI\Cortex\Domains\ConcreteAgent\Support\LockedTools]` when its safety depends on the exact tools it holds. Cortex still manages its prompt, but rejects toolset overrides (clearing one is still allowed) and ignores any saved earlier. The dashboard shows its toolset as locked, and the API reports `tools_overridable: false`.

Agents that cannot change their base class can use the `JayI\Cortex\Domains\ConcreteAgent\Concerns\HasCortexOverrides` trait instead. Registered agents without it are still listed, runnable and usable as sub-agents, but ignore overrides.

## API

Everything is available over the REST API (prefix `cortex` by default):

| Method | URI | Purpose |
| --- | --- | --- |
| GET/POST | `/cortex/virtual-agents` | List / create virtual agents (create stores the prompt as version 1, published) |
| GET/PATCH/DELETE | `/cortex/virtual-agents/{slug}` | Show / update / delete |
| POST | `/cortex/virtual-agents/{slug}/run` | Run with `{"input": "..."}` — returns `{text, usage}` |
| GET/POST | `/cortex/virtual-agents/{slug}/versions` | List / create immutable prompt versions |
| GET | `/cortex/virtual-agents/{slug}/versions/{version}` | Show a version |
| POST | `/cortex/virtual-agents/{slug}/versions/{version}/publish` | Publish a version |
| GET | `/cortex/concrete-agents` | List registered concrete agents with their live prompt and tools |
| GET | `/cortex/concrete-agents/{agent}` | Show one, with its code defaults and overrides |
| POST | `/cortex/concrete-agents/{agent}/run` | Run with `{"input": "..."}` — returns `{text, usage}` |
| PUT | `/cortex/concrete-agents/{agent}/tools` | Set the toolset override with `{"tools": [...]}`, or clear it with `{"tools": null}` |
| DELETE | `/cortex/concrete-agents/{agent}/override` | Remove the prompt and toolset overrides |
| GET/POST | `/cortex/concrete-agents/{agent}/versions` | List / create immutable prompt override versions |
| POST | `/cortex/concrete-agents/{agent}/versions/{version}/publish` | Publish a prompt override version |
| GET | `/cortex/providers` | List providers with their models and default model |
| GET | `/cortex/tools?tag=` | List registered tools with their schemas and tags, optionally one tag's |
| GET/DELETE | `/cortex/tools/{tool}/description` | Show / remove the description override |
| GET/POST | `/cortex/tools/{tool}/description/versions` | List / create immutable override versions |
| POST | `/cortex/tools/{tool}/description/versions/{version}/publish` | Publish an override version |
| GET | `/cortex/servers` | List registered MCP servers with their effective instructions |
| GET/DELETE | `/cortex/servers/{server}/instructions` | Show / remove the instruction override |
| GET/POST | `/cortex/servers/{server}/instructions/versions` | List / create immutable override versions |
| POST | `/cortex/servers/{server}/instructions/versions/{version}/publish` | Publish an override version |

Virtual agent create/update payloads accept `instructions` (the prompt; required on create), `tools` (registered tool names), `sub_agents` (virtual agent slugs) and `concrete_sub_agents` (registered concrete agent names). Updating with changed `instructions` saves them as a new published version; unchanged instructions leave the history alone. The lists use sync semantics — send the desired end state. Circular sub-agent references are rejected. Sub-agents are offered to the parent under their slug or registered class name.

```json
{
    "name": "Coordinator",
    "slug": "coordinator",
    "instructions": "Coordinate the support team.",
    "provider": "anthropic",
    "model": "claude-sonnet-5-5",
    "settings": {"temperature": 0.3, "max_steps": 10},
    "tools": ["search"],
    "sub_agents": ["researcher"],
    "concrete_sub_agents": ["triage"]
}
```

## Running Agents from Code

```php
use JayI\Cortex\Facades\Cortex;

$response = Cortex::runVirtualAgent('coordinator', 'Summarize the open tickets.');
$response = Cortex::runConcreteAgent('triage', 'The export button does nothing.');

$response->text;

// Or build the laravel/ai agent yourself:
Cortex::virtualAgent('coordinator')->stream('...');
Cortex::concreteAgent('triage')->prompt('...');
```

Providers, models, and settings fall back to your app's `config/ai.php` defaults when not set on a virtual agent.

## Providers

The dashboard's virtual agent form and `GET /cortex/providers` offer the same provider and model list. By default every text-capable provider configured for laravel/ai is offered, along with the models it declares (default, smartest, cheapest). Set `cortex.providers` to curate the list — it becomes authoritative when non-empty, with the first model of each provider used as its default:

```php
'providers' => [
    'anthropic' => ['claude-sonnet-5-5', 'claude-opus-5-5'],
],
```

## Publication Cache

Published virtual agent prompts, tool description overrides, MCP server instruction overrides and concrete agent overrides are cached so agent runs, tool listings, and MCP handshakes don't hit the database on every request; publishing invalidates explicitly. When Redis is available it is preferred and read via `Cache::flexible()` using the `cache.fresh`/`cache.stale` windows (stale-while-revalidate); any other store caches until invalidation. Pin a store with `cache.store`, or set `cache.enabled` to `false` to read from the database on every pull.

## MCP Server

The `CortexServer` exposes the virtual agent, concrete agent, tool, and server-instruction operations as MCP tools (25 tools: virtual agent CRUD + run + prompt versions + publish, concrete agent list/show/run + prompt versions + publish + tools + remove overrides, list tools, server instructions + versions + publish). The provider and tool-description endpoints are HTTP-only. Enable a transport in the config:

```php
'mcp' => [
    'web' => ['enabled' => true, 'route' => 'mcp/cortex', 'middleware' => ['auth:sanctum']],
    'local' => ['enabled' => true, 'handle' => 'cortex'],
],
```

The web transport serves streamable HTTP at `/mcp/cortex`; the local transport is started with `php artisan mcp:start cortex` and inspectable with `php artisan mcp:inspector cortex`. Alternatively, keep both disabled and register the server yourself in `routes/ai.php`:

```php
use JayI\Cortex\Mcp\CortexServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/cortex', CortexServer::class)->middleware(['auth:sanctum']);
```

### Server Instruction Overrides

An MCP server's code-declared instructions (the `#[Instructions]` attribute or `$instructions` property) can be overridden without a deploy: each registered server has an optional, immutably versioned instruction override with a published pointer — same model as agent prompts and tool descriptions. Manage overrides from the dashboard, the API (`/cortex/servers/{server}/instructions`), or the MCP tools.

Cortex's own server is always registered as `cortex`. Register additional servers in `config/cortex.php` under `mcp.servers` (string keys set the registered name; unkeyed entries derive it from the server's `#[Name]` attribute or class basename), or at runtime:

```php
use JayI\Cortex\Facades\Cortex;

Cortex::servers()->register('support', \App\Mcp\SupportServer::class);
```

For the published override to actually be served to MCP clients, the server class must extend `JayI\Cortex\Domains\McpServer\Support\Server` (or use the `JayI\Cortex\Domains\McpServer\Concerns\HasVersionedInstructions` trait if it cannot change its base class). Unregistered servers, and servers with no published version, keep serving their code-declared instructions.

## Events

- **Model events:** every Eloquent hook of every Cortex model fires its own class, such as `VirtualAgentCreatingEvent`, `VirtualAgentVersionSavedEvent` or `ConcreteAgentOverrideDeletedEvent`.
- **Action events:** every action fires a start and a finish event, such as `VirtualAgentVersionPublishingActionEvent` and `VirtualAgentVersionPublishedActionEvent`, or `VirtualAgentRunningActionEvent` and `VirtualAgentRanActionEvent`. The start event fires before the work. The finish event fires after the transaction commits, and only on success.
- **Listening to a whole family:** listen to `ModelLifecycleEvent`, `ActionStartingEvent` or `ActionFinishedEvent` (in `JayI\Cortex\Contracts`) to receive every event of that family.

**Full guide:** [Events](docs/events.md). It lists every action with its two events and what they carry.

## Testing Your Integration

Fake agent responses with the Laravel AI SDK's testing helpers. Virtual agents all run through `JayI\Cortex\Domains\VirtualAgent\Support\DbAgent`; concrete agents are faked through their own class:

```php
use JayI\Cortex\Domains\VirtualAgent\Support\DbAgent;

DbAgent::fake(['Canned response.']);
TriageAgent::fake(['Canned triage.']);

DbAgent::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, 'tickets'));
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Cortex! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Jay Fletcher](https://github.com/jayi)
- [All Contributors](../../contributors)

## License

Cortex is open-sourced software licensed under the [MIT license](LICENSE.md).
