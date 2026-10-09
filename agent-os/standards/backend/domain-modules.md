# Domain Modules

Cortex mirrors the `mono` application's domain-module layout: code lives in self-contained modules under `src/Domains/{Domain}/`, namespace `RefactorCircus\Cortex\Domains\{Domain}`.

## Layout

```
src/Domains/{Domain}/
├── {Domain}ServiceProvider.php   # extends RefactorCircus\Keystone\Support\ServiceProvider
├── routes.php                    # JSON API routes, loaded inside the shared cortex group
├── Models/                       # {Entity}Model.php
├── Policies/ Resources/          # beside Models/ (model resources)
├── Actions/ Events/              # actions, action events and model lifecycle events
├── Http/{Controllers,Requests,Resources}/   # Http/Resources only for payloads with no model
├── Mcp/{Tools,Requests}/
├── Services/                     # registries and override lookups bound in the container
└── Concerns/ Exceptions/ Support/ # only when used
```

- Domains: `VirtualAgent` (virtual agents, their versions, runtime and providers), `ConcreteAgent` (class-based agents, their registry and overrides), `Tool` (tool registry and description overrides), `McpServer` (MCP server registry and instruction overrides), `RedirectDomain` (stored OAuth redirect domains and the dynamic client registration controller that accepts them). Create a subdirectory only when it holds something.
- Package-wide code stays at the top level: `Cortex`, `CortexServiceProvider`, `Facades\Cortex`, the base `Http\Request` and `Mcp\Request`, `Mcp\CortexServer`, `Mcp\Tools\ListCortexHistoryTool`, `Http\Resources\AgentRunResource`, and `Support\` (`PublicationCache`, `Policies\Policy`).
- The event contracts, `DispatchesModelEvents` and the provider bases come from `refactor-circus/keystone` (`RefactorCircus\Keystone\Contracts\*`, `RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents`, `RefactorCircus\Keystone\Support\{ServiceProvider,PackageServiceProvider}`).
- The Atrium screens span every domain, so they live in `src/Atrium` (`CortexPlugin`, `ScreenAccess`, `RunnableAgents`, `Badges`, `Http\Controllers\*UiController`, `Features\CortexSupportFeature`).
- Migrations and factories stay in `database/`, views in `resources/views`, translations in `lang`. There is one config file, `config/cortex.php`; domains read from it.

## Registration

- `CortexServiceProvider` (the class in `extra.laravel.providers`) extends Keystone's `PackageServiceProvider`. It merges the config, registers the package (`definition()`, `registerPackage()`), registers `RefactorCircus\Cortex\Domains\DomainServiceProvider`, and keeps cross-cutting wiring: the `Cortex` singleton, policies, the Atrium plugin and the Cortex MCP server through the base helpers, the history route (`loadHistoryRoutes()`), views, translations and publish tags.
- `DomainServiceProvider` lists every domain provider in a private `$providers` array and registers them in a loop.
- A domain provider binds its own services, keeps its morph aliases and loads its `routes.php` with `loadApiRoutesFrom()`, which skips the routes unless `cortex.routes.enabled` is true.

## Naming

| Type | Pattern | Example |
|------|---------|---------|
| Model | `{Entity}Model` | `Domains\VirtualAgent\Models\VirtualAgentModel` |
| Action | `{Verb}{Entity}Action` | `CreateVirtualAgentAction` |
| Action event | `{Entity}{Verb}ActionEvent` | `VirtualAgentCreatedActionEvent` |
| Model event | `{Entity}{Hook}Event` | `VirtualAgentVersionCreatedEvent` |
| Controller | `{Entity}Controller` | `VirtualAgentController` |
| Request | `{Verb}{Entity}Request` | `StoreVirtualAgentRequest` |
| MCP tool | `{Verb}{Entity}Tool` | `CreateVirtualAgentTool` |

## Stored identifiers

- Each model keeps its previous class name (`RefactorCircus\Cortex\Models\{Entity}`) as its morph alias, through `keepMorphAliases()` in its domain provider, so polymorphic columns and audit records written under the old name still resolve.
- `CortexSupportFeature` keeps its Pennant stored name, `RefactorCircus\Cortex\Features\CortexSupportFeature`, with Pennant's `#[Name]` attribute.
- Tools, servers and agents are stored by their registered names, never their class names.
