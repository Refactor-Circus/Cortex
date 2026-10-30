# Release Notes

## [Unreleased](https://github.com/Refactor-Circus/Cortex/compare/v0.1.0...main)

## [v0.1.0](https://github.com/Refactor-Circus/Cortex/commits/v0.1.0) - 2026-10-30

### Upgrade notes (security-relevant)

- **New routes and MCP tools that change an MCP server's tool list are live as soon as you upgrade.** `PUT /cortex/servers/{server}/tools`, `DELETE /cortex/servers/{server}/tools`, the `update-server-tools-tool` and `clear-server-tools-tool` MCP tools and the dashboard's tool editor check the new `updateTools` ability, and the bundled `McpInstructionPolicy` allows it **for guests too**, like every other bundled ability. The routes share the existing server routes' group and middleware, with no switch of their own. Whoever passes `updateTools` can put any registered Cortex tool (`Laravel\Ai` tools included) on a registered server, in front of every MCP client of that server; no per-tool ability is checked. Before deploying, point `McpInstructionModel` in `cortex.policies` at a policy whose `updateTools` denies by default (snippet in the README, "Who may change a server's tools") or make sure your route middleware keeps guests out. Outside the `local` environment Cortex logs a warning at boot, once per worker process, naming the servers whose tools guests may change.
- A host policy for `McpInstructionModel` that has no `updateTools` method denies it (the Gate's default): the tool routes answer 403 to a signed-in user and 401 to a guest, the MCP tools refuse and the dashboard shows the tools read-only. Add the method to use the feature.
- **Run the new migration.** Tool overrides live in a new nullable `tools` column on `cortex_mcp_instructions` (`2026_10_23_000002_add_tools_to_cortex_mcp_instructions`). Publish the migrations with `php artisan vendor:publish --tag="cortex-migrations"` and run `php artisan migrate`. If you already published Cortex's migrations, copy just that file from `vendor/refactor-circus/cortex/database/migrations` instead of publishing the tag again, which re-stamps every migration and makes `migrate` fail on existing tables; the migration skips a column that already exists. Until it runs, servers serve their code tools and Cortex logs a rate-limited warning naming the migration. The column check is remembered per process, so restart Octane and queue workers after migrating. Rolling the migration back drops the column and every stored tool override.
- **Run `composer dump-autoload -o`** after updating (and regenerate any pre-built or classmap-authoritative `vendor/`): the deprecated aliases of the renamed classes load through a new `autoload.files` entry, and without it the old class names are not found.
- **Drain your queues and re-register listeners** before upgrading: events, actions, Data objects, requests and exceptions of the old `Domains\McpServer` namespace moved without aliases (see Breaking).

### Upgrade notes (Atrium screens and owned redirect domains)

- **Grouped merge.** This change ships together with refactor-circus/atrium (the screen-extensions registry, which also removes Atrium's audit-trail components), refactor-circus/keen and refactor-circus/roster. Merge and upgrade all four at once: with only some of them upgraded, Roster's MCP tab disappears.
- **Atrium is suggested, not required.** `refactor-circus/atrium` moved from `require` to `require-dev`, and is listed under `suggest`. Apps that use the Cortex dashboard or its redirect-domain screens must run `composer require refactor-circus/atrium` themselves. Without Atrium the dashboard and those screens are absent, with no error and no log entry, and the JSON API and MCP keep working. A new Composer `conflict` refuses Atrium versions older than the screen registry (`<0.1.0`), so a mismatched install fails at `composer update`.
- **New config key `cortex.atrium.redirect_domains.screens`.** It puts the redirect-domain tab (`cortex.mcp`) or section (`cortex.redirect-domains`) on other packages' screens. It defaults to `['roster.organization' => 'tab', 'roster.user' => 'aside']`. A config published before this release has no `redirect_domains` key under `atrium` and gets that default; set it to `[]` to show the domains on no screen. A malformed entry throws `InvalidArgumentException` at boot in `local` and `testing`, and is reported and skipped in every other environment.
- **Owner fields on the dashboard's redirect-domain routes.** `atrium.cortex.redirect-domains.store` and `atrium.cortex.redirect-domains.destroy` accept optional `owner_type` (a morph alias or model class that `RedirectDomains::owner()` resolves) and `owner_id`. Send both or neither; one alone is a validation error. With an owner, the routes redirect to the previous page when it is on the same scheme, host and port, and to the redirect-domains screen otherwise. Without an owner nothing changes.
- **Owned redirect domains ask the owner (behaviour change).** For a domain with an owner, the bundled `RedirectDomainPolicy` now allows `create` and `delete` only to a signed-in user who may `update` the owner (`$user->can('update', $owner)`). This applies on the dashboard, over the JSON API and over MCP:
  - Guests and users without `update` on the owner are refused: 401 for a guest and 403 for a signed-in user over the JSON API, and refused over MCP. Nothing is written or removed.
  - A domain whose owner row no longer exists cannot be deleted through the bundled policy.
  - An owner model with no policy and no `update` ability denies it. Apps that used owned domains without an owner policy must add one, or point `RedirectDomainModel` at their own class in `cortex.policies`; an app that already does is unaffected.
  - Domains without an owner are unchanged, and `viewAny` still allows everyone.
- **History UI removed.** Cortex's pages no longer render Atrium's audit-trail component, and the **Audit log** link in Cortex's sidebar group is gone. History is now provided by refactor-circus/keen, which adds it to Cortex's declared Atrium screens (`cortex.*` keys). `GET /cortex/history` and `list-cortex-history-tool` are unchanged.
- **Roster's MCP tab is now Cortex's.** The tab on Roster's organization page changes from `?tab=mcp` to `?tab=cortex.mcp`. Old `?tab=mcp` links open the page's default tab. Roster's own redirect-domain route names are gone; the forms post to Cortex's `atrium.cortex.redirect-domains.*` routes.

### Breaking

- **The `McpServer` domain is now `ConcreteServer`.** Every class moved from `RefactorCircus\Cortex\Domains\McpServer\...` to `RefactorCircus\Cortex\Domains\ConcreteServer\...` with its name unchanged, and `McpServerServiceProvider` is now `ConcreteServerServiceProvider`. Table names, route URIs and names, MCP tool names, config keys and registered server names are unchanged. Polymorphic columns and audit entries keep storing the old model class names, which a morph map resolves to the new models; a host that calls `Relation::enforceMorphMap()` or `Relation::morphMap([...], false)` must keep the two `Domains\McpServer\Models\...` keys, and must not map the two models under another alias. Cortex logs a warning after boot when either key is lost or re-mapped.
- **Events, actions, Data objects, requests and exceptions moved without aliases.** Only the classes listed under Deprecated keep their old names. A listener registered by an old event class name (`Event::listen`, a subscriber, a `$listen` map) stops hearing the event without an error, a `catch` of an old exception stops matching, a type-hint or binding of an old action breaks, and queued jobs or listeners serialized with an old class name fail to unserialize. Re-register listeners by the new class names and drain queues before upgrading. Asking for one of these old names logs a warning naming its new class, then fails as class not found.
- **`DELETE /cortex/servers/{server}/instructions` and `delete-server-instructions-tool` keep a tool override.** They delete the instruction versions and the published pointer, and remove the override row only when it holds no tool override. When there is none the answer is as before: 204 and a text answer over MCP. When the server has a tool override the row stays and the tools are still served, and the route now answers **200 with the server's instructions resource** (`tools_overridden: true`), the MCP tool that resource as structured content. Clear the tools with the new `DELETE /cortex/servers/{server}/tools`. The versions are deleted one model at a time, so each fires its own `McpInstructionVersionDeleting/DeletedEvent` (at least once: they fire inside the transaction, so a rolled-back delete has already fired them) and a long history means as many deletes in one transaction. With refactor-circus/keen installed the delete writes one `mcp_instruction.deleted` audit entry; the per-version deletes show only as model events.

- Moved to the Refactor Circus organisation: the package is now `refactor-circus/cortex` with the PHP namespace `RefactorCircus\Cortex` (it was `jayi/cortex` and `JayI\Cortex`). Update `composer.json` requirements and `use` statements. Old class names are not kept as aliases, so stored values written under them - polymorphic `*_type` columns, audit subjects, Pennant feature names - need updating to the new names.
- **Actions take their input as Data objects instead of arrays.** Every Action that took an input array, a toolset list, a run input, a tag or a page now takes a typed, immutable `{Action}Data` from its domain's `Data` namespace, built on refactor-circus/keystone's `Data`: `CreateVirtualAgentData`, `UpdateVirtualAgentData`, `CreateVirtualAgentVersionData`, `ListVirtualAgentsData`, `ListVirtualAgentVersionsData`, `RunVirtualAgentData`, `CreateConcreteAgentVersionData`, `UpdateConcreteAgentToolsData`, `RunConcreteAgentData`, `CreateToolDescriptionVersionData`, `ListToolsData`, `CreateMcpInstructionVersionData` and `CreateRedirectDomainData`. Build one from untrusted input with `XData::from($input)`, which validates it (throwing `ValidationException`) and casts each snake_case key to its typed field; from input already validated against `XData::rules()` with `XData::fromValidated($validated)`; from your own values with `new XData(...)`, e.g. `app(CreateRedirectDomainAction::class)->execute(new CreateRedirectDomainData('claude.ai'), $owner)`. `Action::rules()` moved to `XData::rules()` (`UpdateConcreteAgentToolsData::rules($agent)` keeps its agent argument), and Actions without input lost their empty `rules()`. `UpdateVirtualAgentData` leaves fields out as `Keystone\Data\Missing`, so a field set to null stays apart from one not sent. Action events that carried the input (`VirtualAgentCreatingActionEvent::$data`, `ConcreteAgentToolsUpdatingActionEvent::$tools`, `VirtualAgentsListingActionEvent::$page`, `ToolsListingActionEvent::$tag`, the running and ran events' `$input`, ...) carry the Data object as `$data` or `$filters`.
- **Structured MCP answers carry the resource under `data`.** Tools that answer with one record (show, create, update, publish, run) now answer `{"data": {...}}`, as the JSON API does, instead of the bare record; lists keep their `data` envelope, and the paginated virtual agent and version lists add a `meta` with the page.

### Deprecated

- The old names of `Support\Server`, `Concerns\HasVersionedInstructions`, `Models\McpInstructionModel`, `Models\McpInstructionVersionModel`, `Policies\McpInstructionPolicy` and `Policies\McpInstructionVersionPolicy` under `RefactorCircus\Cortex\Domains\McpServer\` still load, as aliases of the `ConcreteServer` classes registered lazily (only when an old name is asked for) through `src/Domains/ConcreteServer/shims.php` in `autoload.files`. They are removed in the next major release; move your references to `RefactorCircus\Cortex\Domains\ConcreteServer\...`.
- `cortex.policies` keys naming an old model class are moved to the new class, keeping your policy, with a deprecation warning per key; when both keys are listed the new one wins. Key the entries by the new model classes.

### Changed

- **MCP output schemas.** Every tool that answers with structured content declares an `outputSchema()`, built from its resource's new `schema()` (fields, types and nullability), so agents know the shape before calling. Every resource extends refactor-circus/keystone's `JsonResource` and answers MCP calls with `toStructuredResponse()`.
- Every write action (deleting and publishing overrides, prompts, tool descriptions and server instructions, the concrete agent toolset, adding and removing redirect domains) runs inside a database transaction, and the publication cache is cleared only once the write commits: a rolled-back write keeps the cache and fires no finished event, and a reader can no longer re-cache the old content between the clear and the commit.
- The virtual agent and version lists take `per_page` (1-100, default 15) beside `page`, over the JSON API, the MCP tools and the dashboard, from one `pagination()` definition (refactor-circus/keystone's `Pagination`).
- Guests refused by a policy get **401** from the JSON API instead of 403, as across the suite (refactor-circus/keystone's request base); signed-in users still get 403.
- Missing records on Cortex's routes answer a plain `{"message": "Not found."}` 404, as its MCP tools do, instead of Laravel's message naming the model.
- New `cortex.audit.enabled` config key (default true): set it to false to keep Cortex out of the audit log while refactor-circus/keen is installed. The history MCP tool is offered only while Cortex audits; otherwise it is not in the tool catalog at all (it answered with an error).

### Added

- **Concrete server tool overrides.** A registered MCP server's tool list can be replaced without a deploy, picked from its code-declared tools and the registered Cortex tools: `PUT /cortex/servers/{server}/tools` (`cortex.servers.tools.update`) and the `update-server-tools-tool` MCP tool set it, `DELETE /cortex/servers/{server}/tools` (`cortex.servers.tools.destroy`) and `clear-server-tools-tool` clear it, and the server screen has a checklist editor with a "restore code tools" button. Actions `UpdateConcreteServerToolsAction` and `ClearConcreteServerToolsAction` (Data `UpdateConcreteServerToolsData`, `ClearConcreteServerToolsData`; events `ConcreteServerTools{Updating,Updated,Clearing,Cleared}ActionEvent`). Clients are served exactly the stored list, in the stored order; `[]` serves no tools. Servers need `Support\Server` or the `HasCortexOverrides` trait (`HasVersionedInstructions` is the same trait under its old name). The `cortex` server cannot be overridden. Stale, failing and ambiguous entries are skipped or resolved to the code tool, logged, and reported in the new `missing_tools` / `ambiguous_tools` fields beside `tools` and `tools_overridden` on the server list and the instructions show. See the README, "Overriding a Concrete Server's Tools".
- New `cortex.mcp.warning_ttl` config key (default 3600 seconds): how long Cortex stays quiet about a server override it cannot serve as saved once it has logged it, per server and entry, across requests.
- `McpServerRegistry::defaultTools()` and `supportsToolOverrides()`.
- **Configurable table names**: every table, the `virtual_agent_sub_agents` pivot included, is named under the new `cortex.tables` config key and read by the migrations, models, validation rules and queries through `Support\Tables`. Keys a published config leaves out keep their default. Every model already has a bundled policy in `cortex.policies`; an arch test now keeps it that way.
- **OAuth redirect domains** managed at runtime instead of only through `mcp.redirect_domains` (`MCP_REDIRECT_DOMAINS`). Cortex keeps a `cortex_redirect_domains` table (new migration) and replaces laravel/mcp's dynamic client registration controller with one that also accepts every stored origin. Domains may belong to any model (an organization, a user) or to no one, and are managed from the new **Redirect domains** dashboard screen (the compiled list of config, global, organization and user domains, filterable by source, where global domains are added), the `/cortex/redirect-domains` API and the `list-redirect-domains-tool`, `create-redirect-domain-tool` and `delete-redirect-domain-tool` MCP tools. Hosts and redirect URLs are stored as their origin; only config can allow `*`. Switch it off with `cortex.redirect_domains.enabled`. `RedirectDomainModel` gets a policy in `cortex.policies` that allows everything, like the other bundled policies.
- The package's section in Atrium's sidebar rail has its own icon (`cpu-chip`) and a fixed place in the rail.
- An **Audit log** link in the package's sidebar group, opening its own audit log in Atrium (`/atrium/history/cortex`), shown while an audit log (refactor-circus/keen) is installed and to those who may read the package's history.

### Breaking

- Cortex stands on [refactor-circus/keystone](https://github.com/Refactor-Circus/Keystone), the shared runtime of the Refactor Circus suite, which it now requires. The package-local copies are gone; update imports:
  - `RefactorCircus\Cortex\Contracts\{ActionStartingEvent,ActionFinishedEvent,ModelLifecycleEvent}` → `RefactorCircus\Keystone\Contracts\...`, so one listener hears every package of the suite
  - `RefactorCircus\Cortex\Support\Models\Concerns\DispatchesModelEvents` → `RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents`
  - `RefactorCircus\Cortex\Support\ServiceProvider` → `RefactorCircus\Keystone\Support\ServiceProvider`; `CortexServiceProvider` extends `RefactorCircus\Keystone\Support\PackageServiceProvider` and registers Cortex with the `PackageRegistry` as `cortex`
  - `RefactorCircus\Cortex\Http\Request`, `RefactorCircus\Cortex\Mcp\Request` and `RefactorCircus\Cortex\Support\Policies\Policy` stay, extending Keystone's bases. They keep asking the Gate as the signed-in user or as a guest, so authorization is unchanged. MCP calls are now marked with the `mcp` surface.
  - `CortexServer` extends `RefactorCircus\Keystone\Mcp\Server`, lists its tools in a public `TOOLS` constant, and Cortex's own MCP tools extend `RefactorCircus\Keystone\Mcp\Tool`. Both still serve published instruction and description overrides. `Domains\McpServer\Support\Server`, `Domains\Tool\Support\Tool` and their `HasVersionedInstructions` / `HasVersionedDescription` traits remain the bases for an application's own servers and tools.
- The JSON API loads only while the new `cortex.routes.enabled` key is true. It defaults to true, also for a published config file that predates the key.

- The package is reorganised into domain modules (`src/Domains/VirtualAgent`, `ConcreteAgent`, `Tool`, `McpServer`), mirroring the mono application's layout. Classes move namespaces and the models gain a `Model` suffix; there are no aliases for the old class names, so update imports and `cortex.policies` keys. Config keys, route names and paths, MCP tool names, publish tags, views, translations, tables and model event class names are unchanged. Each model keeps its old class name as its morph alias, so values stored under it still resolve, and `CortexSupportFeature` keeps its Pennant stored name (`RefactorCircus\Cortex\Features\CortexSupportFeature`). The JSON API routes now load from each domain (`routes/cortex.php` is gone). Old → new:
  - `RefactorCircus\Cortex\Actions\Concerns\ResolvesVirtualAgentReferences` → `RefactorCircus\Cortex\Domains\VirtualAgent\Concerns\ResolvesVirtualAgentReferences`
  - `RefactorCircus\Cortex\Actions\CreateConcreteAgentVersionAction` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\CreateConcreteAgentVersionAction`
  - `RefactorCircus\Cortex\Actions\CreateMcpInstructionVersionAction` → `RefactorCircus\Cortex\Domains\McpServer\Actions\CreateMcpInstructionVersionAction`
  - `RefactorCircus\Cortex\Actions\CreateToolDescriptionVersionAction` → `RefactorCircus\Cortex\Domains\Tool\Actions\CreateToolDescriptionVersionAction`
  - `RefactorCircus\Cortex\Actions\CreateVirtualAgentAction` → `RefactorCircus\Cortex\Domains\VirtualAgent\Actions\CreateVirtualAgentAction`
  - `RefactorCircus\Cortex\Actions\CreateVirtualAgentVersionAction` → `RefactorCircus\Cortex\Domains\VirtualAgent\Actions\CreateVirtualAgentVersionAction`
  - `RefactorCircus\Cortex\Actions\DeleteConcreteAgentOverrideAction` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\DeleteConcreteAgentOverrideAction`
  - `RefactorCircus\Cortex\Actions\DeleteMcpInstructionAction` → `RefactorCircus\Cortex\Domains\McpServer\Actions\DeleteMcpInstructionAction`
  - `RefactorCircus\Cortex\Actions\DeleteToolDescriptionAction` → `RefactorCircus\Cortex\Domains\Tool\Actions\DeleteToolDescriptionAction`
  - `RefactorCircus\Cortex\Actions\DeleteVirtualAgentAction` → `RefactorCircus\Cortex\Domains\VirtualAgent\Actions\DeleteVirtualAgentAction`
  - `RefactorCircus\Cortex\Actions\ListConcreteAgentVersionsAction` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\ListConcreteAgentVersionsAction`
  - `RefactorCircus\Cortex\Actions\ListConcreteAgentsAction` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\ListConcreteAgentsAction`
  - `RefactorCircus\Cortex\Actions\ListMcpInstructionVersionsAction` → `RefactorCircus\Cortex\Domains\McpServer\Actions\ListMcpInstructionVersionsAction`
  - `RefactorCircus\Cortex\Actions\ListMcpServersAction` → `RefactorCircus\Cortex\Domains\McpServer\Actions\ListMcpServersAction`
  - `RefactorCircus\Cortex\Actions\ListProvidersAction` → `RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ListProvidersAction`
  - `RefactorCircus\Cortex\Actions\ListToolDescriptionVersionsAction` → `RefactorCircus\Cortex\Domains\Tool\Actions\ListToolDescriptionVersionsAction`
  - `RefactorCircus\Cortex\Actions\ListToolsAction` → `RefactorCircus\Cortex\Domains\Tool\Actions\ListToolsAction`
  - `RefactorCircus\Cortex\Actions\ListVirtualAgentVersionsAction` → `RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ListVirtualAgentVersionsAction`
  - `RefactorCircus\Cortex\Actions\ListVirtualAgentsAction` → `RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ListVirtualAgentsAction`
  - `RefactorCircus\Cortex\Actions\PublishConcreteAgentVersionAction` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\PublishConcreteAgentVersionAction`
  - `RefactorCircus\Cortex\Actions\PublishMcpInstructionVersionAction` → `RefactorCircus\Cortex\Domains\McpServer\Actions\PublishMcpInstructionVersionAction`
  - `RefactorCircus\Cortex\Actions\PublishToolDescriptionVersionAction` → `RefactorCircus\Cortex\Domains\Tool\Actions\PublishToolDescriptionVersionAction`
  - `RefactorCircus\Cortex\Actions\PublishVirtualAgentVersionAction` → `RefactorCircus\Cortex\Domains\VirtualAgent\Actions\PublishVirtualAgentVersionAction`
  - `RefactorCircus\Cortex\Actions\RunConcreteAgentAction` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\RunConcreteAgentAction`
  - `RefactorCircus\Cortex\Actions\RunVirtualAgentAction` → `RefactorCircus\Cortex\Domains\VirtualAgent\Actions\RunVirtualAgentAction`
  - `RefactorCircus\Cortex\Actions\ShowConcreteAgentAction` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\ShowConcreteAgentAction`
  - `RefactorCircus\Cortex\Actions\ShowMcpInstructionAction` → `RefactorCircus\Cortex\Domains\McpServer\Actions\ShowMcpInstructionAction`
  - `RefactorCircus\Cortex\Actions\ShowToolDescriptionAction` → `RefactorCircus\Cortex\Domains\Tool\Actions\ShowToolDescriptionAction`
  - `RefactorCircus\Cortex\Actions\ShowVirtualAgentAction` → `RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ShowVirtualAgentAction`
  - `RefactorCircus\Cortex\Actions\ShowVirtualAgentVersionAction` → `RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ShowVirtualAgentVersionAction`
  - `RefactorCircus\Cortex\Actions\UpdateConcreteAgentToolsAction` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\UpdateConcreteAgentToolsAction`
  - `RefactorCircus\Cortex\Actions\UpdateVirtualAgentAction` → `RefactorCircus\Cortex\Domains\VirtualAgent\Actions\UpdateVirtualAgentAction`
  - `RefactorCircus\Cortex\Agents\Agent` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Support\Agent`
  - `RefactorCircus\Cortex\Agents\AgentRegistry` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry`
  - `RefactorCircus\Cortex\Agents\Attributes\LockedTools` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Support\LockedTools`
  - `RefactorCircus\Cortex\Agents\Concerns\HasCortexOverrides` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Concerns\HasCortexOverrides`
  - `RefactorCircus\Cortex\Agents\ConcreteAgentOverrides` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Services\ConcreteAgentOverrides`
  - `RefactorCircus\Cortex\Exceptions\AgentNotFoundException` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Exceptions\AgentNotFoundException`
  - `RefactorCircus\Cortex\Exceptions\CircularAgentReferenceException` → `RefactorCircus\Cortex\Domains\VirtualAgent\Exceptions\CircularAgentReferenceException`
  - `RefactorCircus\Cortex\Exceptions\McpServerNotFoundException` → `RefactorCircus\Cortex\Domains\McpServer\Exceptions\McpServerNotFoundException`
  - `RefactorCircus\Cortex\Exceptions\ToolNotFoundException` → `RefactorCircus\Cortex\Domains\Tool\Exceptions\ToolNotFoundException`
  - `RefactorCircus\Cortex\Exceptions\VirtualAgentNotPublishedException` → `RefactorCircus\Cortex\Domains\VirtualAgent\Exceptions\VirtualAgentNotPublishedException`
  - `RefactorCircus\Cortex\Features\CortexSupportFeature` → `RefactorCircus\Cortex\Atrium\Features\CortexSupportFeature`
  - `RefactorCircus\Cortex\Http\Controllers\ConcreteAgentController` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Controllers\ConcreteAgentController`
  - `RefactorCircus\Cortex\Http\Controllers\ConcreteAgentVersionController` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Controllers\ConcreteAgentVersionController`
  - `RefactorCircus\Cortex\Http\Controllers\McpInstructionController` → `RefactorCircus\Cortex\Domains\McpServer\Http\Controllers\McpInstructionController`
  - `RefactorCircus\Cortex\Http\Controllers\McpServerController` → `RefactorCircus\Cortex\Domains\McpServer\Http\Controllers\McpServerController`
  - `RefactorCircus\Cortex\Http\Controllers\ProviderController` → `RefactorCircus\Cortex\Domains\VirtualAgent\Http\Controllers\ProviderController`
  - `RefactorCircus\Cortex\Http\Controllers\ToolController` → `RefactorCircus\Cortex\Domains\Tool\Http\Controllers\ToolController`
  - `RefactorCircus\Cortex\Http\Controllers\ToolDescriptionController` → `RefactorCircus\Cortex\Domains\Tool\Http\Controllers\ToolDescriptionController`
  - `RefactorCircus\Cortex\Http\Controllers\VirtualAgentController` → `RefactorCircus\Cortex\Domains\VirtualAgent\Http\Controllers\VirtualAgentController`
  - `RefactorCircus\Cortex\Http\Controllers\VirtualAgentRunController` → `RefactorCircus\Cortex\Domains\VirtualAgent\Http\Controllers\VirtualAgentRunController`
  - `RefactorCircus\Cortex\Http\Controllers\VirtualAgentVersionController` → `RefactorCircus\Cortex\Domains\VirtualAgent\Http\Controllers\VirtualAgentVersionController`
  - `RefactorCircus\Cortex\Http\Requests\ConcreteAgentRequest` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests\ConcreteAgentRequest`
  - `RefactorCircus\Cortex\Http\Requests\DeleteConcreteAgentOverrideRequest` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests\DeleteConcreteAgentOverrideRequest`
  - `RefactorCircus\Cortex\Http\Requests\DeleteMcpInstructionRequest` → `RefactorCircus\Cortex\Domains\McpServer\Http\Requests\DeleteMcpInstructionRequest`
  - `RefactorCircus\Cortex\Http\Requests\DeleteToolDescriptionRequest` → `RefactorCircus\Cortex\Domains\Tool\Http\Requests\DeleteToolDescriptionRequest`
  - `RefactorCircus\Cortex\Http\Requests\DeleteVirtualAgentRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\DeleteVirtualAgentRequest`
  - `RefactorCircus\Cortex\Http\Requests\IndexConcreteAgentVersionsRequest` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests\IndexConcreteAgentVersionsRequest`
  - `RefactorCircus\Cortex\Http\Requests\IndexConcreteAgentsRequest` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests\IndexConcreteAgentsRequest`
  - `RefactorCircus\Cortex\Http\Requests\IndexMcpInstructionVersionsRequest` → `RefactorCircus\Cortex\Domains\McpServer\Http\Requests\IndexMcpInstructionVersionsRequest`
  - `RefactorCircus\Cortex\Http\Requests\IndexMcpServersRequest` → `RefactorCircus\Cortex\Domains\McpServer\Http\Requests\IndexMcpServersRequest`
  - `RefactorCircus\Cortex\Http\Requests\IndexProvidersRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\IndexProvidersRequest`
  - `RefactorCircus\Cortex\Http\Requests\IndexToolDescriptionVersionsRequest` → `RefactorCircus\Cortex\Domains\Tool\Http\Requests\IndexToolDescriptionVersionsRequest`
  - `RefactorCircus\Cortex\Http\Requests\IndexToolsRequest` → `RefactorCircus\Cortex\Domains\Tool\Http\Requests\IndexToolsRequest`
  - `RefactorCircus\Cortex\Http\Requests\IndexVirtualAgentVersionsRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\IndexVirtualAgentVersionsRequest`
  - `RefactorCircus\Cortex\Http\Requests\IndexVirtualAgentsRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\IndexVirtualAgentsRequest`
  - `RefactorCircus\Cortex\Http\Requests\McpInstructionRequest` → `RefactorCircus\Cortex\Domains\McpServer\Http\Requests\McpInstructionRequest`
  - `RefactorCircus\Cortex\Http\Requests\PublishConcreteAgentVersionRequest` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests\PublishConcreteAgentVersionRequest`
  - `RefactorCircus\Cortex\Http\Requests\PublishMcpInstructionVersionRequest` → `RefactorCircus\Cortex\Domains\McpServer\Http\Requests\PublishMcpInstructionVersionRequest`
  - `RefactorCircus\Cortex\Http\Requests\PublishToolDescriptionVersionRequest` → `RefactorCircus\Cortex\Domains\Tool\Http\Requests\PublishToolDescriptionVersionRequest`
  - `RefactorCircus\Cortex\Http\Requests\PublishVirtualAgentVersionRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\PublishVirtualAgentVersionRequest`
  - `RefactorCircus\Cortex\Http\Requests\RunConcreteAgentRequest` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests\RunConcreteAgentRequest`
  - `RefactorCircus\Cortex\Http\Requests\RunVirtualAgentRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\RunVirtualAgentRequest`
  - `RefactorCircus\Cortex\Http\Requests\ShowConcreteAgentRequest` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests\ShowConcreteAgentRequest`
  - `RefactorCircus\Cortex\Http\Requests\ShowMcpInstructionRequest` → `RefactorCircus\Cortex\Domains\McpServer\Http\Requests\ShowMcpInstructionRequest`
  - `RefactorCircus\Cortex\Http\Requests\ShowToolDescriptionRequest` → `RefactorCircus\Cortex\Domains\Tool\Http\Requests\ShowToolDescriptionRequest`
  - `RefactorCircus\Cortex\Http\Requests\ShowVirtualAgentRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\ShowVirtualAgentRequest`
  - `RefactorCircus\Cortex\Http\Requests\ShowVirtualAgentVersionRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\ShowVirtualAgentVersionRequest`
  - `RefactorCircus\Cortex\Http\Requests\StoreConcreteAgentVersionRequest` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests\StoreConcreteAgentVersionRequest`
  - `RefactorCircus\Cortex\Http\Requests\StoreMcpInstructionVersionRequest` → `RefactorCircus\Cortex\Domains\McpServer\Http\Requests\StoreMcpInstructionVersionRequest`
  - `RefactorCircus\Cortex\Http\Requests\StoreToolDescriptionVersionRequest` → `RefactorCircus\Cortex\Domains\Tool\Http\Requests\StoreToolDescriptionVersionRequest`
  - `RefactorCircus\Cortex\Http\Requests\StoreVirtualAgentRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\StoreVirtualAgentRequest`
  - `RefactorCircus\Cortex\Http\Requests\StoreVirtualAgentVersionRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\StoreVirtualAgentVersionRequest`
  - `RefactorCircus\Cortex\Http\Requests\ToolDescriptionRequest` → `RefactorCircus\Cortex\Domains\Tool\Http\Requests\ToolDescriptionRequest`
  - `RefactorCircus\Cortex\Http\Requests\UpdateConcreteAgentToolsRequest` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests\UpdateConcreteAgentToolsRequest`
  - `RefactorCircus\Cortex\Http\Requests\UpdateVirtualAgentRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\UpdateVirtualAgentRequest`
  - `RefactorCircus\Cortex\Http\Requests\VirtualAgentRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\VirtualAgentRequest`
  - `RefactorCircus\Cortex\Http\Resources\ConcreteAgentOverrideResource` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Resources\ConcreteAgentOverrideResource`
  - `RefactorCircus\Cortex\Http\Resources\ConcreteAgentOverrideVersionResource` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Resources\ConcreteAgentOverrideVersionResource`
  - `RefactorCircus\Cortex\Http\Resources\ConcreteAgentResource` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Resources\ConcreteAgentResource`
  - `RefactorCircus\Cortex\Http\Resources\McpInstructionResource` → `RefactorCircus\Cortex\Domains\McpServer\Resources\McpInstructionResource`
  - `RefactorCircus\Cortex\Http\Resources\McpInstructionVersionResource` → `RefactorCircus\Cortex\Domains\McpServer\Resources\McpInstructionVersionResource`
  - `RefactorCircus\Cortex\Http\Resources\McpServerResource` → `RefactorCircus\Cortex\Domains\McpServer\Http\Resources\McpServerResource`
  - `RefactorCircus\Cortex\Http\Resources\ToolDescriptionResource` → `RefactorCircus\Cortex\Domains\Tool\Resources\ToolDescriptionResource`
  - `RefactorCircus\Cortex\Http\Resources\ToolDescriptionVersionResource` → `RefactorCircus\Cortex\Domains\Tool\Resources\ToolDescriptionVersionResource`
  - `RefactorCircus\Cortex\Http\Resources\ToolResource` → `RefactorCircus\Cortex\Domains\Tool\Http\Resources\ToolResource`
  - `RefactorCircus\Cortex\Http\Resources\VirtualAgentResource` → `RefactorCircus\Cortex\Domains\VirtualAgent\Resources\VirtualAgentResource`
  - `RefactorCircus\Cortex\Http\Resources\VirtualAgentVersionResource` → `RefactorCircus\Cortex\Domains\VirtualAgent\Resources\VirtualAgentVersionResource`
  - `RefactorCircus\Cortex\Http\Ui\Concerns\AuthorizesScreens` → `RefactorCircus\Cortex\Atrium\Http\Controllers\Concerns\AuthorizesScreens`
  - `RefactorCircus\Cortex\Http\Ui\ConcreteAgentUiController` → `RefactorCircus\Cortex\Atrium\Http\Controllers\ConcreteAgentUiController`
  - `RefactorCircus\Cortex\Http\Ui\McpInstructionUiController` → `RefactorCircus\Cortex\Atrium\Http\Controllers\McpInstructionUiController`
  - `RefactorCircus\Cortex\Http\Ui\RunAgentUiController` → `RefactorCircus\Cortex\Atrium\Http\Controllers\RunAgentUiController`
  - `RefactorCircus\Cortex\Http\Ui\RunnableAgents` → `RefactorCircus\Cortex\Atrium\RunnableAgents`
  - `RefactorCircus\Cortex\Http\Ui\ScreenAccess` → `RefactorCircus\Cortex\Atrium\ScreenAccess`
  - `RefactorCircus\Cortex\Http\Ui\ServerUiController` → `RefactorCircus\Cortex\Atrium\Http\Controllers\ServerUiController`
  - `RefactorCircus\Cortex\Http\Ui\ToolDescriptionUiController` → `RefactorCircus\Cortex\Atrium\Http\Controllers\ToolDescriptionUiController`
  - `RefactorCircus\Cortex\Http\Ui\ToolUiController` → `RefactorCircus\Cortex\Atrium\Http\Controllers\ToolUiController`
  - `RefactorCircus\Cortex\Http\Ui\VirtualAgentUiController` → `RefactorCircus\Cortex\Atrium\Http\Controllers\VirtualAgentUiController`
  - `RefactorCircus\Cortex\Http\Ui\VirtualAgentVersionUiController` → `RefactorCircus\Cortex\Atrium\Http\Controllers\VirtualAgentVersionUiController`
  - `RefactorCircus\Cortex\Mcp\Concerns\HasVersionedInstructions` → `RefactorCircus\Cortex\Domains\McpServer\Concerns\HasVersionedInstructions`
  - `RefactorCircus\Cortex\Mcp\McpInstructionOverrides` → `RefactorCircus\Cortex\Domains\McpServer\Services\McpInstructionOverrides`
  - `RefactorCircus\Cortex\Mcp\McpServerRegistry` → `RefactorCircus\Cortex\Domains\McpServer\Services\McpServerRegistry`
  - `RefactorCircus\Cortex\Mcp\Requests\ConcreteAgentMcpRequest` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Requests\ConcreteAgentMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\CreateConcreteAgentVersionMcpRequest` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Requests\CreateConcreteAgentVersionMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\CreateServerInstructionVersionMcpRequest` → `RefactorCircus\Cortex\Domains\McpServer\Mcp\Requests\CreateServerInstructionVersionMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\CreateVirtualAgentMcpRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests\CreateVirtualAgentMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\CreateVirtualAgentVersionMcpRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests\CreateVirtualAgentVersionMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\DeleteConcreteAgentOverrideMcpRequest` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Requests\DeleteConcreteAgentOverrideMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\DeleteServerInstructionsMcpRequest` → `RefactorCircus\Cortex\Domains\McpServer\Mcp\Requests\DeleteServerInstructionsMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\DeleteVirtualAgentMcpRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests\DeleteVirtualAgentMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\ListConcreteAgentVersionsMcpRequest` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Requests\ListConcreteAgentVersionsMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\ListConcreteAgentsMcpRequest` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Requests\ListConcreteAgentsMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\ListServerInstructionVersionsMcpRequest` → `RefactorCircus\Cortex\Domains\McpServer\Mcp\Requests\ListServerInstructionVersionsMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\ListServersMcpRequest` → `RefactorCircus\Cortex\Domains\McpServer\Mcp\Requests\ListServersMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\ListToolsMcpRequest` → `RefactorCircus\Cortex\Domains\Tool\Mcp\Requests\ListToolsMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\ListVirtualAgentVersionsMcpRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests\ListVirtualAgentVersionsMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\ListVirtualAgentsMcpRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests\ListVirtualAgentsMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\PublishConcreteAgentVersionMcpRequest` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Requests\PublishConcreteAgentVersionMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\PublishServerInstructionVersionMcpRequest` → `RefactorCircus\Cortex\Domains\McpServer\Mcp\Requests\PublishServerInstructionVersionMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\PublishVirtualAgentVersionMcpRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests\PublishVirtualAgentVersionMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\RunConcreteAgentMcpRequest` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Requests\RunConcreteAgentMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\RunVirtualAgentMcpRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests\RunVirtualAgentMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\ServerMcpRequest` → `RefactorCircus\Cortex\Domains\McpServer\Mcp\Requests\ServerMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\ShowConcreteAgentMcpRequest` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Requests\ShowConcreteAgentMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\ShowServerInstructionsMcpRequest` → `RefactorCircus\Cortex\Domains\McpServer\Mcp\Requests\ShowServerInstructionsMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\ShowVirtualAgentMcpRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests\ShowVirtualAgentMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\ShowVirtualAgentVersionMcpRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests\ShowVirtualAgentVersionMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\UpdateConcreteAgentToolsMcpRequest` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Requests\UpdateConcreteAgentToolsMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\UpdateVirtualAgentMcpRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests\UpdateVirtualAgentMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Requests\VirtualAgentMcpRequest` → `RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests\VirtualAgentMcpRequest`
  - `RefactorCircus\Cortex\Mcp\Server` → `RefactorCircus\Cortex\Domains\McpServer\Support\Server`
  - `RefactorCircus\Cortex\Mcp\Tools\Concerns\DescribesVirtualAgentPayload` → `RefactorCircus\Cortex\Domains\VirtualAgent\Concerns\DescribesVirtualAgentPayload`
  - `RefactorCircus\Cortex\Mcp\Tools\CreateConcreteAgentVersionTool` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Tools\CreateConcreteAgentVersionTool`
  - `RefactorCircus\Cortex\Mcp\Tools\CreateServerInstructionVersionTool` → `RefactorCircus\Cortex\Domains\McpServer\Mcp\Tools\CreateServerInstructionVersionTool`
  - `RefactorCircus\Cortex\Mcp\Tools\CreateVirtualAgentTool` → `RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\CreateVirtualAgentTool`
  - `RefactorCircus\Cortex\Mcp\Tools\CreateVirtualAgentVersionTool` → `RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\CreateVirtualAgentVersionTool`
  - `RefactorCircus\Cortex\Mcp\Tools\DeleteConcreteAgentOverrideTool` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Tools\DeleteConcreteAgentOverrideTool`
  - `RefactorCircus\Cortex\Mcp\Tools\DeleteServerInstructionsTool` → `RefactorCircus\Cortex\Domains\McpServer\Mcp\Tools\DeleteServerInstructionsTool`
  - `RefactorCircus\Cortex\Mcp\Tools\DeleteVirtualAgentTool` → `RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\DeleteVirtualAgentTool`
  - `RefactorCircus\Cortex\Mcp\Tools\ListConcreteAgentVersionsTool` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Tools\ListConcreteAgentVersionsTool`
  - `RefactorCircus\Cortex\Mcp\Tools\ListConcreteAgentsTool` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Tools\ListConcreteAgentsTool`
  - `RefactorCircus\Cortex\Mcp\Tools\ListServerInstructionVersionsTool` → `RefactorCircus\Cortex\Domains\McpServer\Mcp\Tools\ListServerInstructionVersionsTool`
  - `RefactorCircus\Cortex\Mcp\Tools\ListServersTool` → `RefactorCircus\Cortex\Domains\McpServer\Mcp\Tools\ListServersTool`
  - `RefactorCircus\Cortex\Mcp\Tools\ListToolsTool` → `RefactorCircus\Cortex\Domains\Tool\Mcp\Tools\ListToolsTool`
  - `RefactorCircus\Cortex\Mcp\Tools\ListVirtualAgentVersionsTool` → `RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\ListVirtualAgentVersionsTool`
  - `RefactorCircus\Cortex\Mcp\Tools\ListVirtualAgentsTool` → `RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\ListVirtualAgentsTool`
  - `RefactorCircus\Cortex\Mcp\Tools\PublishConcreteAgentVersionTool` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Tools\PublishConcreteAgentVersionTool`
  - `RefactorCircus\Cortex\Mcp\Tools\PublishServerInstructionVersionTool` → `RefactorCircus\Cortex\Domains\McpServer\Mcp\Tools\PublishServerInstructionVersionTool`
  - `RefactorCircus\Cortex\Mcp\Tools\PublishVirtualAgentVersionTool` → `RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\PublishVirtualAgentVersionTool`
  - `RefactorCircus\Cortex\Mcp\Tools\RunConcreteAgentTool` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Tools\RunConcreteAgentTool`
  - `RefactorCircus\Cortex\Mcp\Tools\RunVirtualAgentTool` → `RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\RunVirtualAgentTool`
  - `RefactorCircus\Cortex\Mcp\Tools\ShowConcreteAgentTool` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Tools\ShowConcreteAgentTool`
  - `RefactorCircus\Cortex\Mcp\Tools\ShowServerInstructionsTool` → `RefactorCircus\Cortex\Domains\McpServer\Mcp\Tools\ShowServerInstructionsTool`
  - `RefactorCircus\Cortex\Mcp\Tools\ShowVirtualAgentTool` → `RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\ShowVirtualAgentTool`
  - `RefactorCircus\Cortex\Mcp\Tools\ShowVirtualAgentVersionTool` → `RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\ShowVirtualAgentVersionTool`
  - `RefactorCircus\Cortex\Mcp\Tools\UpdateConcreteAgentToolsTool` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Tools\UpdateConcreteAgentToolsTool`
  - `RefactorCircus\Cortex\Mcp\Tools\UpdateVirtualAgentTool` → `RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\UpdateVirtualAgentTool`
  - `RefactorCircus\Cortex\Models\Concerns\DispatchesModelEvents` → `RefactorCircus\Cortex\Support\Models\Concerns\DispatchesModelEvents`
  - `RefactorCircus\Cortex\Models\ConcreteAgentOverride` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel`
  - `RefactorCircus\Cortex\Models\ConcreteAgentOverrideVersion` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel`
  - `RefactorCircus\Cortex\Models\McpInstruction` → `RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel`
  - `RefactorCircus\Cortex\Models\McpInstructionVersion` → `RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionVersionModel`
  - `RefactorCircus\Cortex\Models\ToolDescription` → `RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel`
  - `RefactorCircus\Cortex\Models\ToolDescriptionVersion` → `RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel`
  - `RefactorCircus\Cortex\Models\VirtualAgent` → `RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel`
  - `RefactorCircus\Cortex\Models\VirtualAgentVersion` → `RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel`
  - `RefactorCircus\Cortex\Policies\ConcreteAgentOverridePolicy` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Policies\ConcreteAgentOverridePolicy`
  - `RefactorCircus\Cortex\Policies\ConcreteAgentOverrideVersionPolicy` → `RefactorCircus\Cortex\Domains\ConcreteAgent\Policies\ConcreteAgentOverrideVersionPolicy`
  - `RefactorCircus\Cortex\Policies\McpInstructionPolicy` → `RefactorCircus\Cortex\Domains\McpServer\Policies\McpInstructionPolicy`
  - `RefactorCircus\Cortex\Policies\McpInstructionVersionPolicy` → `RefactorCircus\Cortex\Domains\McpServer\Policies\McpInstructionVersionPolicy`
  - `RefactorCircus\Cortex\Policies\Policy` → `RefactorCircus\Cortex\Support\Policies\Policy`
  - `RefactorCircus\Cortex\Policies\ToolDescriptionPolicy` → `RefactorCircus\Cortex\Domains\Tool\Policies\ToolDescriptionPolicy`
  - `RefactorCircus\Cortex\Policies\ToolDescriptionVersionPolicy` → `RefactorCircus\Cortex\Domains\Tool\Policies\ToolDescriptionVersionPolicy`
  - `RefactorCircus\Cortex\Policies\VirtualAgentPolicy` → `RefactorCircus\Cortex\Domains\VirtualAgent\Policies\VirtualAgentPolicy`
  - `RefactorCircus\Cortex\Policies\VirtualAgentVersionPolicy` → `RefactorCircus\Cortex\Domains\VirtualAgent\Policies\VirtualAgentVersionPolicy`
  - `RefactorCircus\Cortex\Runtime\AgentFactory` → `RefactorCircus\Cortex\Domains\VirtualAgent\Services\AgentFactory`
  - `RefactorCircus\Cortex\Runtime\DbAgent` → `RefactorCircus\Cortex\Domains\VirtualAgent\Support\DbAgent`
  - `RefactorCircus\Cortex\Tools\Concerns\HasVersionedDescription` → `RefactorCircus\Cortex\Domains\Tool\Concerns\HasVersionedDescription`
  - `RefactorCircus\Cortex\Tools\DescribedTool` → `RefactorCircus\Cortex\Domains\Tool\Support\DescribedTool`
  - `RefactorCircus\Cortex\Tools\Tool` → `RefactorCircus\Cortex\Domains\Tool\Support\Tool`
  - `RefactorCircus\Cortex\Tools\ToolDescriptionOverrides` → `RefactorCircus\Cortex\Domains\Tool\Services\ToolDescriptionOverrides`
  - `RefactorCircus\Cortex\Tools\ToolName` → `RefactorCircus\Cortex\Domains\Tool\Support\ToolName`
  - `RefactorCircus\Cortex\Tools\ToolRegistry` → `RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry`
  - Events: `RefactorCircus\Cortex\Events\Action\{Name}` and `RefactorCircus\Cortex\Events\Model\{Name}` → `RefactorCircus\Cortex\Domains\{Domain}\Events\{Name}`, names unchanged (the domain is the entity's: `VirtualAgent*` and `Providers*` → `VirtualAgent`, `ConcreteAgent*` → `ConcreteAgent`, `ToolDescription*` and `Tools*` → `Tool`, `McpInstruction*` and `McpServers*` → `McpServer`).
- Requires the domain-module releases of `refactor-circus/atrium` and `refactor-circus/pennantplus` (`RefactorCircus\Atrium\Domains\...`, `RefactorCircus\PennantPlus\Domains\...`).

### Added

- Audit history: `GET /cortex/history` (route `cortex.history.index`, inside the JSON API group) and the `list-cortex-history-tool` MCP tool list Cortex's audit entries, newest first, through refactor-circus/keystone. Both answer "not installed" (404 over HTTP) until an audit log such as refactor-circus/keen is installed.
- Virtual agents: DB-backed agents (`cortex_virtual_agents`) that own their prompt, versioned in place with immutable versions and a published-version pointer (`cortex_virtual_agent_versions`), plus registered tools, provider/model settings, virtual sub-agents (`cortex_virtual_agent_sub_agents`, with cycle protection) and concrete sub-agents. Updating the instructions saves a new published version. All Cortex tables use ULID primary keys.
- Concrete agents: `AgentRegistry` registers class-based `Laravel\Ai\Contracts\Agent` implementations by name via config (`cortex.agents`) or `Cortex::agents()->register()`. Registered agents are listed, runnable and attachable as sub-agents of virtual agents.
- `RefactorCircus\Cortex\Agents\Agent` base class and `HasCortexOverrides` trait: agents declare `defaultInstructions()` and `defaultTools()`, and run with the published Cortex overrides (`cortex_concrete_agent_overrides`, `cortex_concrete_agent_override_versions`) when they exist — a versioned prompt override and a toolset override picked from the class's own tools and registered Cortex tools. `#[LockedTools]` opts an agent out of toolset overrides.
- `DbAgent` implements `CanActAsTool`, so a virtual sub-agent is offered to its parent under its slug and description instead of `DbAgent`.
- `ToolRegistry` for registering `Laravel\Ai\Contracts\Tool` classes by name via config (`cortex.tools`) or `Cortex::tools()->register()`.
- Tool tags: tools are tagged at registration (`Cortex::tools()->register($name, $class, $tags)` or a `['class' => ..., 'tags' => [...]]` config entry) and from namespace patterns (`cortex.tool_tags.namespaces`). The dashboard tool list filters by tag, the virtual and concrete agent tool pickers filter by tag, name and selection, and `GET /cortex/tools` and `list-tools-tool` take a `tag` filter and return each tool's `tags`.
- Agent execution on the Laravel AI SDK: `Cortex::runVirtualAgent()`, `Cortex::runConcreteAgent()`, `Cortex::virtualAgent()`, `Cortex::concreteAgent()`, `POST /cortex/virtual-agents/{slug}/run`, `POST /cortex/concrete-agents/{agent}/run`, and the matching MCP tools.
- REST API under the configurable `cortex` prefix covering virtual agents and their prompt versions, concrete agents and their overrides, tools, and run.
- `CortexServer` MCP server with tools at parity with the API, config-gated web (`Mcp::web`) and local (`Mcp::local`) transports, disabled by default.
- `laravel/ai` (^0.11) and `laravel/mcp` (^1.0) dependencies.
- MCP server instruction management with immutable versioning and a published-version pointer (`cortex_mcp_instructions`, `cortex_mcp_instruction_versions`): published overrides replace a server's code-declared `#[Instructions]` at runtime.
- `McpServerRegistry` for registering MCP server classes by name via config (`cortex.mcp.servers`) or `Cortex::servers()->register()`; Cortex's own server is always registered as `cortex`.
- `RefactorCircus\Cortex\Mcp\Server` base class and `HasVersionedInstructions` trait so any Laravel MCP server can serve its published instruction override.
- REST endpoints under `/cortex/servers` for listing servers and managing instruction overrides, six matching MCP tools on `CortexServer` (25 tools in all), and a Servers section in the dashboard with a versioned instructions editor.

- Model events: every Eloquent hook of every Cortex model dispatches its own class in `RefactorCircus\Cortex\Events\Model` (`{Model}{Hook}Event`, e.g. `VirtualAgentVersionCreatedEvent`) through the `DispatchesModelEvents` trait. All implement `RefactorCircus\Cortex\Contracts\ModelLifecycleEvent`.
- Action events: every action dispatches a start event before its work and a finish event with its result (`RefactorCircus\Cortex\Events\Action`, e.g. `VirtualAgentRunningActionEvent` / `VirtualAgentRanActionEvent`). Start events implement `ActionStartingEvent`. Finish events implement `ActionFinishedEvent`, dispatch after commit and are skipped when the action throws. See `docs/events.md`.
- Policies for every model (`RefactorCircus\Cortex\Policies`), registered with the Gate from the new `cortex.policies` config. Every API endpoint and MCP tool that touches a model now authorizes through them, as the signed-in user or as a guest. The bundled policies allow everything, since Cortex records have no owner, so existing behaviour is unchanged. Version policies defer to their virtual agent or override through the Gate. See `docs/policies.md`.

- Dashboard permission gates: every Atrium page, action and control is checked against the `cortex.policies` policies exactly as the JSON API and MCP tools check them, through `RefactorCircus\Cortex\Http\Ui\ScreenAccess` and the new `@cortexCan` Blade conditional. Navigation items, buttons, forms and cards are shown only when their action would be allowed, the run page offers only the agents the user may `run`, and search returns only what the searcher may view.
- `RefactorCircus\Cortex\Features\CortexSupportFeature` (needs `refactor-circus/pennantplus`, suggested) and the `cortex.atrium.features` config: switch Cortex in Atrium on and off as a whole. Feature classes that cannot be loaded are skipped.
- `RefactorCircus\Cortex\Atrium\Badges` maps each dashboard state to its Atrium colour.
- Per-record history on the dashboard: `<x-atrium::audit-trail>` shows Cortex's audit entries on the virtual agents list, and each virtual agent's, tool description's, MCP server instruction's and concrete agent override's own entries on its screen. Nothing renders until an audit log such as refactor-circus/keen is installed.

### Changed

- Requires PHP 8.5 (it was 8.4). Dependency constraints are raised to their latest releases, including Laravel 13.35, Testbench 11.3 and Pest 5.3; CI tests PHP 8.5 only.
- The Atrium screens use Atrium's components only: searches are `x-atrium::search-input`, tag filters are `x-atrium::chip`s (linked on the tool list, Alpine-driven in the tool pickers), the picker's "selected only" box is a bare `x-atrium::form.checkbox`, and the status and first prompt or agent error come from `<x-atrium::flash :keys="['prompt', 'agent']" />`.
- `CortexPlugin::features()` uses Atrium's `featuresFromConfig()`, and the plugin's `key()` / `label()` come from Atrium's base derivation (still `cortex` / `Cortex`).
- The Run agent navigation item and page follow `viewAny` on either kind of agent, so building the dashboard navigation no longer queries every agent; the page lists the agents the user may run, or says there are none.
- The Atrium screens follow Atrium's screen conventions: actions, tabs and back links are icon buttons (the label is the tooltip and accessible name), states are status dots carrying `data-status`, and every navigation item has a Heroicons icon. Tool tag filters are linked chips. Requires `refactor-circus/atrium` at f5eb488 or later.
- The dashboard pages now authorize: before, they ran actions without asking the policies the API asks.

- The dashboard is server-rendered Blade built on `refactor-circus/atrium`, which Cortex now requires, replacing the Vue 3 SPA. Cortex registers an Atrium plugin with navigation, routes under `/atrium/cortex/...`, search over virtual and concrete agents, and a settings panel. Atrium owns the path, middleware and `viewAtrium` gate, so the `ui.auth` config, the `UiTokenResolver` contract and the `/cortex/ui` route are gone; `ui.enabled` remains as the switch.
- Requires `laravel/framework` instead of `illuminate/support`, since the package uses form requests, events and queues from the framework.

### Removed

- `resources/css/atrium.css` and its registration through Atrium's style hook: Cortex ships no styles, and every class its views use comes from Atrium's stylesheet.
- The `cortex::ui.partials.status` view, replaced by Atrium's `flash` component.
- The TypeScript SDK (`@refactor-circus/cortex-sdk`, `sdk/`), its npm workspace and `sdk:generate`/`sdk:build` scripts, and the `dedoc/scramble` dev dependency that exported its OpenAPI spec.
- The `cortex-assets` publish tag and the empty `public/` directory it published. The dashboard is Blade rendered through Atrium and ships no assets of its own.
- The skeleton `cortex:placeholder` Artisan command and the `cortex::messages.placeholder` translation.

### Fixed

- Creating a version of a virtual agent prompt, concrete agent override, tool description or MCP server instruction no longer fails on PostgreSQL. The next version number was read with `SELECT MAX(version) ... FOR UPDATE`, which PostgreSQL rejects for aggregates; the actions now lock the parent row and read the maximum plainly.
- Agents calling an MCP tool whose `handle()` type-hints its own `Laravel\Mcp\Request` subclass now pass their arguments to it. Previously laravel/ai's `McpServerTool` bound the arguments only as the base request, so such tools, including Cortex's own MCP tools, received an empty request and failed validation.

### Performance

- Building a virtual agent loads its sub-agent graph a level at a time (one pivot query per level) and the published prompts of the whole graph in one query, and builds each virtual agent once: a sub-agent shared by several parents is reused instead of being rebuilt for every path to it.
- Listing and showing concrete agents builds each agent once (new `AgentRegistry::describe($name)`), and whether an agent class reads overrides or locks its tools is remembered per class.
- New `ToolRegistry::summaries(?string $tag)` lists tools by name, class and tags without building any tool; the Tools screen and the virtual agent form use it. A tool's tags are worked out once until the next `register()` or a change to `cortex.tool_tags.namespaces`.
- The Redirect domains screen shows the configured domains in their own block and pages the stored ones (25 a page, the source filter applied in the query, owners loaded for the page only). `ListRedirectDomainsAction::execute()` takes an optional `ListRedirectDomainsData` (`source`, `paged`, `perPage`, `page`; set by code, never read from input) and answers a page when `paged` is set; `RedirectDomainsListingActionEvent` carries it as `$filters`. The JSON API and MCP list are unchanged.
- The virtual agent dashboard list no longer loads prompt content or sub-agents: `ListVirtualAgentsData` has a `summary` flag (never read from input) that loads only each agent's published version number. The JSON API and MCP list are unchanged.
- Sub-agent validation looks every selected slug up in one query, and the cycle check walks the sub-agent graph one pivot query per level. The virtual agent form loads only the id, name and slug of the agents it offers.
- New migration `2026_10_12_000001_add_cortex_performance_indexes` indexes the sub-agent pivot's `sub_agent_id`. Run `php artisan migrate` after updating (publish it first with `cortex-migrations` if you published the migrations).
- The global search checks concrete agent results against their overrides in one query, and the settings panel counts MCP servers without resolving them.
- On Redis, a publish can take up to `cortex.cache.fresh` seconds to reach every worker; the config file and README now say so.
