# Release Notes

## [Unreleased](https://github.com/Refactor-Circus/Cortex/commits/main)

### Breaking

- Moved to the Refactor Circus organisation: the package is now `refactor-circus/cortex` with the PHP namespace `RefactorCircus\Cortex` (it was `jayi/cortex` and `JayI\Cortex`). Update `composer.json` requirements and `use` statements. Old class names are not kept as aliases, so stored values written under them - polymorphic `*_type` columns, audit subjects, Pennant feature names - need updating to the new names.

### Added

- **OAuth redirect domains** managed at runtime instead of only through `mcp.redirect_domains` (`MCP_REDIRECT_DOMAINS`). Cortex keeps a `cortex_redirect_domains` table (new migration) and replaces laravel/mcp's dynamic client registration controller with one that also accepts every stored origin. Domains may belong to any model (an organization, a user) or to no one, and are managed from the new **Redirect domains** dashboard screen (the compiled list of config, global, organization and user domains, filterable by source, where global domains are added), the `/cortex/redirect-domains` API and the `list-redirect-domains-tool`, `create-redirect-domain-tool` and `delete-redirect-domain-tool` MCP tools. Hosts and redirect URLs are stored as their origin; only config can allow `*`. Switch it off with `cortex.redirect_domains.enabled`. `RedirectDomainModel` gets a policy in `cortex.policies` that allows everything, like the other bundled policies.
- The package's section in Atrium's sidebar rail has its own icon (`cpu-chip`) and a fixed place in the rail.
- An **Audit log** link in the package's sidebar group, opening its own audit log in Atrium (`/atrium/history/cortex`), shown while an audit log (refactor-circus/keen) is installed and to those who may read the package's history.

### Breaking

- Cortex stands on [refactor-circus/keystone](https://github.com/jayjfletcher/Foundation), the shared runtime of the Refactor Circus suite, which it now requires. The package-local copies are gone; update imports:
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

- Agents calling an MCP tool whose `handle()` type-hints its own `Laravel\Mcp\Request` subclass now pass their arguments to it. Previously laravel/ai's `McpServerTool` bound the arguments only as the base request, so such tools, including Cortex's own MCP tools, received an empty request and failed validation.


## [v0.1.0](https://github.com/Refactor-Circus/Cortex/compare/...v0.1.0) - 202x-xx-xx

Initial pre-release.
