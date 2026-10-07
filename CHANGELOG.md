# Release Notes

## [Unreleased](https://github.com/jayi/cortex/compare/v0.1.0...1.x)

### Added

- An **Audit log** link in the package's sidebar group, opening its own audit log in Atrium (`/atrium/history/cortex`), shown while an audit log (jayi/keen) is installed and to those who may read the package's history.

### Breaking

- Cortex stands on [jayi/foundation](https://github.com/jayjfletcher/Foundation), the shared runtime of the jayi suite, which it now requires. The package-local copies are gone; update imports:
  - `JayI\Cortex\Contracts\{ActionStartingEvent,ActionFinishedEvent,ModelLifecycleEvent}` → `JayI\Foundation\Contracts\...`, so one listener hears every package of the suite
  - `JayI\Cortex\Support\Models\Concerns\DispatchesModelEvents` → `JayI\Foundation\Models\Concerns\DispatchesModelEvents`
  - `JayI\Cortex\Support\ServiceProvider` → `JayI\Foundation\Support\ServiceProvider`; `CortexServiceProvider` extends `JayI\Foundation\Support\PackageServiceProvider` and registers Cortex with the `PackageRegistry` as `cortex`
  - `JayI\Cortex\Http\Request`, `JayI\Cortex\Mcp\Request` and `JayI\Cortex\Support\Policies\Policy` stay, extending Foundation's bases. They keep asking the Gate as the signed-in user or as a guest, so authorization is unchanged. MCP calls are now marked with the `mcp` surface.
  - `CortexServer` extends `JayI\Foundation\Mcp\Server`, lists its tools in a public `TOOLS` constant, and Cortex's own MCP tools extend `JayI\Foundation\Mcp\Tool`. Both still serve published instruction and description overrides. `Domains\McpServer\Support\Server`, `Domains\Tool\Support\Tool` and their `HasVersionedInstructions` / `HasVersionedDescription` traits remain the bases for an application's own servers and tools.
- The JSON API loads only while the new `cortex.routes.enabled` key is true. It defaults to true, also for a published config file that predates the key.

- The package is reorganised into domain modules (`src/Domains/VirtualAgent`, `ConcreteAgent`, `Tool`, `McpServer`), mirroring the mono application's layout. Classes move namespaces and the models gain a `Model` suffix; there are no aliases for the old class names, so update imports and `cortex.policies` keys. Config keys, route names and paths, MCP tool names, publish tags, views, translations, tables and model event class names are unchanged. Each model keeps its old class name as its morph alias, so values stored under it still resolve, and `CortexSupportFeature` keeps its Pennant stored name (`JayI\Cortex\Features\CortexSupportFeature`). The JSON API routes now load from each domain (`routes/cortex.php` is gone). Old → new:
  - `JayI\Cortex\Actions\Concerns\ResolvesVirtualAgentReferences` → `JayI\Cortex\Domains\VirtualAgent\Concerns\ResolvesVirtualAgentReferences`
  - `JayI\Cortex\Actions\CreateConcreteAgentVersionAction` → `JayI\Cortex\Domains\ConcreteAgent\Actions\CreateConcreteAgentVersionAction`
  - `JayI\Cortex\Actions\CreateMcpInstructionVersionAction` → `JayI\Cortex\Domains\McpServer\Actions\CreateMcpInstructionVersionAction`
  - `JayI\Cortex\Actions\CreateToolDescriptionVersionAction` → `JayI\Cortex\Domains\Tool\Actions\CreateToolDescriptionVersionAction`
  - `JayI\Cortex\Actions\CreateVirtualAgentAction` → `JayI\Cortex\Domains\VirtualAgent\Actions\CreateVirtualAgentAction`
  - `JayI\Cortex\Actions\CreateVirtualAgentVersionAction` → `JayI\Cortex\Domains\VirtualAgent\Actions\CreateVirtualAgentVersionAction`
  - `JayI\Cortex\Actions\DeleteConcreteAgentOverrideAction` → `JayI\Cortex\Domains\ConcreteAgent\Actions\DeleteConcreteAgentOverrideAction`
  - `JayI\Cortex\Actions\DeleteMcpInstructionAction` → `JayI\Cortex\Domains\McpServer\Actions\DeleteMcpInstructionAction`
  - `JayI\Cortex\Actions\DeleteToolDescriptionAction` → `JayI\Cortex\Domains\Tool\Actions\DeleteToolDescriptionAction`
  - `JayI\Cortex\Actions\DeleteVirtualAgentAction` → `JayI\Cortex\Domains\VirtualAgent\Actions\DeleteVirtualAgentAction`
  - `JayI\Cortex\Actions\ListConcreteAgentVersionsAction` → `JayI\Cortex\Domains\ConcreteAgent\Actions\ListConcreteAgentVersionsAction`
  - `JayI\Cortex\Actions\ListConcreteAgentsAction` → `JayI\Cortex\Domains\ConcreteAgent\Actions\ListConcreteAgentsAction`
  - `JayI\Cortex\Actions\ListMcpInstructionVersionsAction` → `JayI\Cortex\Domains\McpServer\Actions\ListMcpInstructionVersionsAction`
  - `JayI\Cortex\Actions\ListMcpServersAction` → `JayI\Cortex\Domains\McpServer\Actions\ListMcpServersAction`
  - `JayI\Cortex\Actions\ListProvidersAction` → `JayI\Cortex\Domains\VirtualAgent\Actions\ListProvidersAction`
  - `JayI\Cortex\Actions\ListToolDescriptionVersionsAction` → `JayI\Cortex\Domains\Tool\Actions\ListToolDescriptionVersionsAction`
  - `JayI\Cortex\Actions\ListToolsAction` → `JayI\Cortex\Domains\Tool\Actions\ListToolsAction`
  - `JayI\Cortex\Actions\ListVirtualAgentVersionsAction` → `JayI\Cortex\Domains\VirtualAgent\Actions\ListVirtualAgentVersionsAction`
  - `JayI\Cortex\Actions\ListVirtualAgentsAction` → `JayI\Cortex\Domains\VirtualAgent\Actions\ListVirtualAgentsAction`
  - `JayI\Cortex\Actions\PublishConcreteAgentVersionAction` → `JayI\Cortex\Domains\ConcreteAgent\Actions\PublishConcreteAgentVersionAction`
  - `JayI\Cortex\Actions\PublishMcpInstructionVersionAction` → `JayI\Cortex\Domains\McpServer\Actions\PublishMcpInstructionVersionAction`
  - `JayI\Cortex\Actions\PublishToolDescriptionVersionAction` → `JayI\Cortex\Domains\Tool\Actions\PublishToolDescriptionVersionAction`
  - `JayI\Cortex\Actions\PublishVirtualAgentVersionAction` → `JayI\Cortex\Domains\VirtualAgent\Actions\PublishVirtualAgentVersionAction`
  - `JayI\Cortex\Actions\RunConcreteAgentAction` → `JayI\Cortex\Domains\ConcreteAgent\Actions\RunConcreteAgentAction`
  - `JayI\Cortex\Actions\RunVirtualAgentAction` → `JayI\Cortex\Domains\VirtualAgent\Actions\RunVirtualAgentAction`
  - `JayI\Cortex\Actions\ShowConcreteAgentAction` → `JayI\Cortex\Domains\ConcreteAgent\Actions\ShowConcreteAgentAction`
  - `JayI\Cortex\Actions\ShowMcpInstructionAction` → `JayI\Cortex\Domains\McpServer\Actions\ShowMcpInstructionAction`
  - `JayI\Cortex\Actions\ShowToolDescriptionAction` → `JayI\Cortex\Domains\Tool\Actions\ShowToolDescriptionAction`
  - `JayI\Cortex\Actions\ShowVirtualAgentAction` → `JayI\Cortex\Domains\VirtualAgent\Actions\ShowVirtualAgentAction`
  - `JayI\Cortex\Actions\ShowVirtualAgentVersionAction` → `JayI\Cortex\Domains\VirtualAgent\Actions\ShowVirtualAgentVersionAction`
  - `JayI\Cortex\Actions\UpdateConcreteAgentToolsAction` → `JayI\Cortex\Domains\ConcreteAgent\Actions\UpdateConcreteAgentToolsAction`
  - `JayI\Cortex\Actions\UpdateVirtualAgentAction` → `JayI\Cortex\Domains\VirtualAgent\Actions\UpdateVirtualAgentAction`
  - `JayI\Cortex\Agents\Agent` → `JayI\Cortex\Domains\ConcreteAgent\Support\Agent`
  - `JayI\Cortex\Agents\AgentRegistry` → `JayI\Cortex\Domains\ConcreteAgent\Services\AgentRegistry`
  - `JayI\Cortex\Agents\Attributes\LockedTools` → `JayI\Cortex\Domains\ConcreteAgent\Support\LockedTools`
  - `JayI\Cortex\Agents\Concerns\HasCortexOverrides` → `JayI\Cortex\Domains\ConcreteAgent\Concerns\HasCortexOverrides`
  - `JayI\Cortex\Agents\ConcreteAgentOverrides` → `JayI\Cortex\Domains\ConcreteAgent\Services\ConcreteAgentOverrides`
  - `JayI\Cortex\Exceptions\AgentNotFoundException` → `JayI\Cortex\Domains\ConcreteAgent\Exceptions\AgentNotFoundException`
  - `JayI\Cortex\Exceptions\CircularAgentReferenceException` → `JayI\Cortex\Domains\VirtualAgent\Exceptions\CircularAgentReferenceException`
  - `JayI\Cortex\Exceptions\McpServerNotFoundException` → `JayI\Cortex\Domains\McpServer\Exceptions\McpServerNotFoundException`
  - `JayI\Cortex\Exceptions\ToolNotFoundException` → `JayI\Cortex\Domains\Tool\Exceptions\ToolNotFoundException`
  - `JayI\Cortex\Exceptions\VirtualAgentNotPublishedException` → `JayI\Cortex\Domains\VirtualAgent\Exceptions\VirtualAgentNotPublishedException`
  - `JayI\Cortex\Features\CortexSupportFeature` → `JayI\Cortex\Atrium\Features\CortexSupportFeature`
  - `JayI\Cortex\Http\Controllers\ConcreteAgentController` → `JayI\Cortex\Domains\ConcreteAgent\Http\Controllers\ConcreteAgentController`
  - `JayI\Cortex\Http\Controllers\ConcreteAgentVersionController` → `JayI\Cortex\Domains\ConcreteAgent\Http\Controllers\ConcreteAgentVersionController`
  - `JayI\Cortex\Http\Controllers\McpInstructionController` → `JayI\Cortex\Domains\McpServer\Http\Controllers\McpInstructionController`
  - `JayI\Cortex\Http\Controllers\McpServerController` → `JayI\Cortex\Domains\McpServer\Http\Controllers\McpServerController`
  - `JayI\Cortex\Http\Controllers\ProviderController` → `JayI\Cortex\Domains\VirtualAgent\Http\Controllers\ProviderController`
  - `JayI\Cortex\Http\Controllers\ToolController` → `JayI\Cortex\Domains\Tool\Http\Controllers\ToolController`
  - `JayI\Cortex\Http\Controllers\ToolDescriptionController` → `JayI\Cortex\Domains\Tool\Http\Controllers\ToolDescriptionController`
  - `JayI\Cortex\Http\Controllers\VirtualAgentController` → `JayI\Cortex\Domains\VirtualAgent\Http\Controllers\VirtualAgentController`
  - `JayI\Cortex\Http\Controllers\VirtualAgentRunController` → `JayI\Cortex\Domains\VirtualAgent\Http\Controllers\VirtualAgentRunController`
  - `JayI\Cortex\Http\Controllers\VirtualAgentVersionController` → `JayI\Cortex\Domains\VirtualAgent\Http\Controllers\VirtualAgentVersionController`
  - `JayI\Cortex\Http\Requests\ConcreteAgentRequest` → `JayI\Cortex\Domains\ConcreteAgent\Http\Requests\ConcreteAgentRequest`
  - `JayI\Cortex\Http\Requests\DeleteConcreteAgentOverrideRequest` → `JayI\Cortex\Domains\ConcreteAgent\Http\Requests\DeleteConcreteAgentOverrideRequest`
  - `JayI\Cortex\Http\Requests\DeleteMcpInstructionRequest` → `JayI\Cortex\Domains\McpServer\Http\Requests\DeleteMcpInstructionRequest`
  - `JayI\Cortex\Http\Requests\DeleteToolDescriptionRequest` → `JayI\Cortex\Domains\Tool\Http\Requests\DeleteToolDescriptionRequest`
  - `JayI\Cortex\Http\Requests\DeleteVirtualAgentRequest` → `JayI\Cortex\Domains\VirtualAgent\Http\Requests\DeleteVirtualAgentRequest`
  - `JayI\Cortex\Http\Requests\IndexConcreteAgentVersionsRequest` → `JayI\Cortex\Domains\ConcreteAgent\Http\Requests\IndexConcreteAgentVersionsRequest`
  - `JayI\Cortex\Http\Requests\IndexConcreteAgentsRequest` → `JayI\Cortex\Domains\ConcreteAgent\Http\Requests\IndexConcreteAgentsRequest`
  - `JayI\Cortex\Http\Requests\IndexMcpInstructionVersionsRequest` → `JayI\Cortex\Domains\McpServer\Http\Requests\IndexMcpInstructionVersionsRequest`
  - `JayI\Cortex\Http\Requests\IndexMcpServersRequest` → `JayI\Cortex\Domains\McpServer\Http\Requests\IndexMcpServersRequest`
  - `JayI\Cortex\Http\Requests\IndexProvidersRequest` → `JayI\Cortex\Domains\VirtualAgent\Http\Requests\IndexProvidersRequest`
  - `JayI\Cortex\Http\Requests\IndexToolDescriptionVersionsRequest` → `JayI\Cortex\Domains\Tool\Http\Requests\IndexToolDescriptionVersionsRequest`
  - `JayI\Cortex\Http\Requests\IndexToolsRequest` → `JayI\Cortex\Domains\Tool\Http\Requests\IndexToolsRequest`
  - `JayI\Cortex\Http\Requests\IndexVirtualAgentVersionsRequest` → `JayI\Cortex\Domains\VirtualAgent\Http\Requests\IndexVirtualAgentVersionsRequest`
  - `JayI\Cortex\Http\Requests\IndexVirtualAgentsRequest` → `JayI\Cortex\Domains\VirtualAgent\Http\Requests\IndexVirtualAgentsRequest`
  - `JayI\Cortex\Http\Requests\McpInstructionRequest` → `JayI\Cortex\Domains\McpServer\Http\Requests\McpInstructionRequest`
  - `JayI\Cortex\Http\Requests\PublishConcreteAgentVersionRequest` → `JayI\Cortex\Domains\ConcreteAgent\Http\Requests\PublishConcreteAgentVersionRequest`
  - `JayI\Cortex\Http\Requests\PublishMcpInstructionVersionRequest` → `JayI\Cortex\Domains\McpServer\Http\Requests\PublishMcpInstructionVersionRequest`
  - `JayI\Cortex\Http\Requests\PublishToolDescriptionVersionRequest` → `JayI\Cortex\Domains\Tool\Http\Requests\PublishToolDescriptionVersionRequest`
  - `JayI\Cortex\Http\Requests\PublishVirtualAgentVersionRequest` → `JayI\Cortex\Domains\VirtualAgent\Http\Requests\PublishVirtualAgentVersionRequest`
  - `JayI\Cortex\Http\Requests\RunConcreteAgentRequest` → `JayI\Cortex\Domains\ConcreteAgent\Http\Requests\RunConcreteAgentRequest`
  - `JayI\Cortex\Http\Requests\RunVirtualAgentRequest` → `JayI\Cortex\Domains\VirtualAgent\Http\Requests\RunVirtualAgentRequest`
  - `JayI\Cortex\Http\Requests\ShowConcreteAgentRequest` → `JayI\Cortex\Domains\ConcreteAgent\Http\Requests\ShowConcreteAgentRequest`
  - `JayI\Cortex\Http\Requests\ShowMcpInstructionRequest` → `JayI\Cortex\Domains\McpServer\Http\Requests\ShowMcpInstructionRequest`
  - `JayI\Cortex\Http\Requests\ShowToolDescriptionRequest` → `JayI\Cortex\Domains\Tool\Http\Requests\ShowToolDescriptionRequest`
  - `JayI\Cortex\Http\Requests\ShowVirtualAgentRequest` → `JayI\Cortex\Domains\VirtualAgent\Http\Requests\ShowVirtualAgentRequest`
  - `JayI\Cortex\Http\Requests\ShowVirtualAgentVersionRequest` → `JayI\Cortex\Domains\VirtualAgent\Http\Requests\ShowVirtualAgentVersionRequest`
  - `JayI\Cortex\Http\Requests\StoreConcreteAgentVersionRequest` → `JayI\Cortex\Domains\ConcreteAgent\Http\Requests\StoreConcreteAgentVersionRequest`
  - `JayI\Cortex\Http\Requests\StoreMcpInstructionVersionRequest` → `JayI\Cortex\Domains\McpServer\Http\Requests\StoreMcpInstructionVersionRequest`
  - `JayI\Cortex\Http\Requests\StoreToolDescriptionVersionRequest` → `JayI\Cortex\Domains\Tool\Http\Requests\StoreToolDescriptionVersionRequest`
  - `JayI\Cortex\Http\Requests\StoreVirtualAgentRequest` → `JayI\Cortex\Domains\VirtualAgent\Http\Requests\StoreVirtualAgentRequest`
  - `JayI\Cortex\Http\Requests\StoreVirtualAgentVersionRequest` → `JayI\Cortex\Domains\VirtualAgent\Http\Requests\StoreVirtualAgentVersionRequest`
  - `JayI\Cortex\Http\Requests\ToolDescriptionRequest` → `JayI\Cortex\Domains\Tool\Http\Requests\ToolDescriptionRequest`
  - `JayI\Cortex\Http\Requests\UpdateConcreteAgentToolsRequest` → `JayI\Cortex\Domains\ConcreteAgent\Http\Requests\UpdateConcreteAgentToolsRequest`
  - `JayI\Cortex\Http\Requests\UpdateVirtualAgentRequest` → `JayI\Cortex\Domains\VirtualAgent\Http\Requests\UpdateVirtualAgentRequest`
  - `JayI\Cortex\Http\Requests\VirtualAgentRequest` → `JayI\Cortex\Domains\VirtualAgent\Http\Requests\VirtualAgentRequest`
  - `JayI\Cortex\Http\Resources\ConcreteAgentOverrideResource` → `JayI\Cortex\Domains\ConcreteAgent\Resources\ConcreteAgentOverrideResource`
  - `JayI\Cortex\Http\Resources\ConcreteAgentOverrideVersionResource` → `JayI\Cortex\Domains\ConcreteAgent\Resources\ConcreteAgentOverrideVersionResource`
  - `JayI\Cortex\Http\Resources\ConcreteAgentResource` → `JayI\Cortex\Domains\ConcreteAgent\Http\Resources\ConcreteAgentResource`
  - `JayI\Cortex\Http\Resources\McpInstructionResource` → `JayI\Cortex\Domains\McpServer\Resources\McpInstructionResource`
  - `JayI\Cortex\Http\Resources\McpInstructionVersionResource` → `JayI\Cortex\Domains\McpServer\Resources\McpInstructionVersionResource`
  - `JayI\Cortex\Http\Resources\McpServerResource` → `JayI\Cortex\Domains\McpServer\Http\Resources\McpServerResource`
  - `JayI\Cortex\Http\Resources\ToolDescriptionResource` → `JayI\Cortex\Domains\Tool\Resources\ToolDescriptionResource`
  - `JayI\Cortex\Http\Resources\ToolDescriptionVersionResource` → `JayI\Cortex\Domains\Tool\Resources\ToolDescriptionVersionResource`
  - `JayI\Cortex\Http\Resources\ToolResource` → `JayI\Cortex\Domains\Tool\Http\Resources\ToolResource`
  - `JayI\Cortex\Http\Resources\VirtualAgentResource` → `JayI\Cortex\Domains\VirtualAgent\Resources\VirtualAgentResource`
  - `JayI\Cortex\Http\Resources\VirtualAgentVersionResource` → `JayI\Cortex\Domains\VirtualAgent\Resources\VirtualAgentVersionResource`
  - `JayI\Cortex\Http\Ui\Concerns\AuthorizesScreens` → `JayI\Cortex\Atrium\Http\Controllers\Concerns\AuthorizesScreens`
  - `JayI\Cortex\Http\Ui\ConcreteAgentUiController` → `JayI\Cortex\Atrium\Http\Controllers\ConcreteAgentUiController`
  - `JayI\Cortex\Http\Ui\McpInstructionUiController` → `JayI\Cortex\Atrium\Http\Controllers\McpInstructionUiController`
  - `JayI\Cortex\Http\Ui\RunAgentUiController` → `JayI\Cortex\Atrium\Http\Controllers\RunAgentUiController`
  - `JayI\Cortex\Http\Ui\RunnableAgents` → `JayI\Cortex\Atrium\RunnableAgents`
  - `JayI\Cortex\Http\Ui\ScreenAccess` → `JayI\Cortex\Atrium\ScreenAccess`
  - `JayI\Cortex\Http\Ui\ServerUiController` → `JayI\Cortex\Atrium\Http\Controllers\ServerUiController`
  - `JayI\Cortex\Http\Ui\ToolDescriptionUiController` → `JayI\Cortex\Atrium\Http\Controllers\ToolDescriptionUiController`
  - `JayI\Cortex\Http\Ui\ToolUiController` → `JayI\Cortex\Atrium\Http\Controllers\ToolUiController`
  - `JayI\Cortex\Http\Ui\VirtualAgentUiController` → `JayI\Cortex\Atrium\Http\Controllers\VirtualAgentUiController`
  - `JayI\Cortex\Http\Ui\VirtualAgentVersionUiController` → `JayI\Cortex\Atrium\Http\Controllers\VirtualAgentVersionUiController`
  - `JayI\Cortex\Mcp\Concerns\HasVersionedInstructions` → `JayI\Cortex\Domains\McpServer\Concerns\HasVersionedInstructions`
  - `JayI\Cortex\Mcp\McpInstructionOverrides` → `JayI\Cortex\Domains\McpServer\Services\McpInstructionOverrides`
  - `JayI\Cortex\Mcp\McpServerRegistry` → `JayI\Cortex\Domains\McpServer\Services\McpServerRegistry`
  - `JayI\Cortex\Mcp\Requests\ConcreteAgentMcpRequest` → `JayI\Cortex\Domains\ConcreteAgent\Mcp\Requests\ConcreteAgentMcpRequest`
  - `JayI\Cortex\Mcp\Requests\CreateConcreteAgentVersionMcpRequest` → `JayI\Cortex\Domains\ConcreteAgent\Mcp\Requests\CreateConcreteAgentVersionMcpRequest`
  - `JayI\Cortex\Mcp\Requests\CreateServerInstructionVersionMcpRequest` → `JayI\Cortex\Domains\McpServer\Mcp\Requests\CreateServerInstructionVersionMcpRequest`
  - `JayI\Cortex\Mcp\Requests\CreateVirtualAgentMcpRequest` → `JayI\Cortex\Domains\VirtualAgent\Mcp\Requests\CreateVirtualAgentMcpRequest`
  - `JayI\Cortex\Mcp\Requests\CreateVirtualAgentVersionMcpRequest` → `JayI\Cortex\Domains\VirtualAgent\Mcp\Requests\CreateVirtualAgentVersionMcpRequest`
  - `JayI\Cortex\Mcp\Requests\DeleteConcreteAgentOverrideMcpRequest` → `JayI\Cortex\Domains\ConcreteAgent\Mcp\Requests\DeleteConcreteAgentOverrideMcpRequest`
  - `JayI\Cortex\Mcp\Requests\DeleteServerInstructionsMcpRequest` → `JayI\Cortex\Domains\McpServer\Mcp\Requests\DeleteServerInstructionsMcpRequest`
  - `JayI\Cortex\Mcp\Requests\DeleteVirtualAgentMcpRequest` → `JayI\Cortex\Domains\VirtualAgent\Mcp\Requests\DeleteVirtualAgentMcpRequest`
  - `JayI\Cortex\Mcp\Requests\ListConcreteAgentVersionsMcpRequest` → `JayI\Cortex\Domains\ConcreteAgent\Mcp\Requests\ListConcreteAgentVersionsMcpRequest`
  - `JayI\Cortex\Mcp\Requests\ListConcreteAgentsMcpRequest` → `JayI\Cortex\Domains\ConcreteAgent\Mcp\Requests\ListConcreteAgentsMcpRequest`
  - `JayI\Cortex\Mcp\Requests\ListServerInstructionVersionsMcpRequest` → `JayI\Cortex\Domains\McpServer\Mcp\Requests\ListServerInstructionVersionsMcpRequest`
  - `JayI\Cortex\Mcp\Requests\ListServersMcpRequest` → `JayI\Cortex\Domains\McpServer\Mcp\Requests\ListServersMcpRequest`
  - `JayI\Cortex\Mcp\Requests\ListToolsMcpRequest` → `JayI\Cortex\Domains\Tool\Mcp\Requests\ListToolsMcpRequest`
  - `JayI\Cortex\Mcp\Requests\ListVirtualAgentVersionsMcpRequest` → `JayI\Cortex\Domains\VirtualAgent\Mcp\Requests\ListVirtualAgentVersionsMcpRequest`
  - `JayI\Cortex\Mcp\Requests\ListVirtualAgentsMcpRequest` → `JayI\Cortex\Domains\VirtualAgent\Mcp\Requests\ListVirtualAgentsMcpRequest`
  - `JayI\Cortex\Mcp\Requests\PublishConcreteAgentVersionMcpRequest` → `JayI\Cortex\Domains\ConcreteAgent\Mcp\Requests\PublishConcreteAgentVersionMcpRequest`
  - `JayI\Cortex\Mcp\Requests\PublishServerInstructionVersionMcpRequest` → `JayI\Cortex\Domains\McpServer\Mcp\Requests\PublishServerInstructionVersionMcpRequest`
  - `JayI\Cortex\Mcp\Requests\PublishVirtualAgentVersionMcpRequest` → `JayI\Cortex\Domains\VirtualAgent\Mcp\Requests\PublishVirtualAgentVersionMcpRequest`
  - `JayI\Cortex\Mcp\Requests\RunConcreteAgentMcpRequest` → `JayI\Cortex\Domains\ConcreteAgent\Mcp\Requests\RunConcreteAgentMcpRequest`
  - `JayI\Cortex\Mcp\Requests\RunVirtualAgentMcpRequest` → `JayI\Cortex\Domains\VirtualAgent\Mcp\Requests\RunVirtualAgentMcpRequest`
  - `JayI\Cortex\Mcp\Requests\ServerMcpRequest` → `JayI\Cortex\Domains\McpServer\Mcp\Requests\ServerMcpRequest`
  - `JayI\Cortex\Mcp\Requests\ShowConcreteAgentMcpRequest` → `JayI\Cortex\Domains\ConcreteAgent\Mcp\Requests\ShowConcreteAgentMcpRequest`
  - `JayI\Cortex\Mcp\Requests\ShowServerInstructionsMcpRequest` → `JayI\Cortex\Domains\McpServer\Mcp\Requests\ShowServerInstructionsMcpRequest`
  - `JayI\Cortex\Mcp\Requests\ShowVirtualAgentMcpRequest` → `JayI\Cortex\Domains\VirtualAgent\Mcp\Requests\ShowVirtualAgentMcpRequest`
  - `JayI\Cortex\Mcp\Requests\ShowVirtualAgentVersionMcpRequest` → `JayI\Cortex\Domains\VirtualAgent\Mcp\Requests\ShowVirtualAgentVersionMcpRequest`
  - `JayI\Cortex\Mcp\Requests\UpdateConcreteAgentToolsMcpRequest` → `JayI\Cortex\Domains\ConcreteAgent\Mcp\Requests\UpdateConcreteAgentToolsMcpRequest`
  - `JayI\Cortex\Mcp\Requests\UpdateVirtualAgentMcpRequest` → `JayI\Cortex\Domains\VirtualAgent\Mcp\Requests\UpdateVirtualAgentMcpRequest`
  - `JayI\Cortex\Mcp\Requests\VirtualAgentMcpRequest` → `JayI\Cortex\Domains\VirtualAgent\Mcp\Requests\VirtualAgentMcpRequest`
  - `JayI\Cortex\Mcp\Server` → `JayI\Cortex\Domains\McpServer\Support\Server`
  - `JayI\Cortex\Mcp\Tools\Concerns\DescribesVirtualAgentPayload` → `JayI\Cortex\Domains\VirtualAgent\Concerns\DescribesVirtualAgentPayload`
  - `JayI\Cortex\Mcp\Tools\CreateConcreteAgentVersionTool` → `JayI\Cortex\Domains\ConcreteAgent\Mcp\Tools\CreateConcreteAgentVersionTool`
  - `JayI\Cortex\Mcp\Tools\CreateServerInstructionVersionTool` → `JayI\Cortex\Domains\McpServer\Mcp\Tools\CreateServerInstructionVersionTool`
  - `JayI\Cortex\Mcp\Tools\CreateVirtualAgentTool` → `JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\CreateVirtualAgentTool`
  - `JayI\Cortex\Mcp\Tools\CreateVirtualAgentVersionTool` → `JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\CreateVirtualAgentVersionTool`
  - `JayI\Cortex\Mcp\Tools\DeleteConcreteAgentOverrideTool` → `JayI\Cortex\Domains\ConcreteAgent\Mcp\Tools\DeleteConcreteAgentOverrideTool`
  - `JayI\Cortex\Mcp\Tools\DeleteServerInstructionsTool` → `JayI\Cortex\Domains\McpServer\Mcp\Tools\DeleteServerInstructionsTool`
  - `JayI\Cortex\Mcp\Tools\DeleteVirtualAgentTool` → `JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\DeleteVirtualAgentTool`
  - `JayI\Cortex\Mcp\Tools\ListConcreteAgentVersionsTool` → `JayI\Cortex\Domains\ConcreteAgent\Mcp\Tools\ListConcreteAgentVersionsTool`
  - `JayI\Cortex\Mcp\Tools\ListConcreteAgentsTool` → `JayI\Cortex\Domains\ConcreteAgent\Mcp\Tools\ListConcreteAgentsTool`
  - `JayI\Cortex\Mcp\Tools\ListServerInstructionVersionsTool` → `JayI\Cortex\Domains\McpServer\Mcp\Tools\ListServerInstructionVersionsTool`
  - `JayI\Cortex\Mcp\Tools\ListServersTool` → `JayI\Cortex\Domains\McpServer\Mcp\Tools\ListServersTool`
  - `JayI\Cortex\Mcp\Tools\ListToolsTool` → `JayI\Cortex\Domains\Tool\Mcp\Tools\ListToolsTool`
  - `JayI\Cortex\Mcp\Tools\ListVirtualAgentVersionsTool` → `JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\ListVirtualAgentVersionsTool`
  - `JayI\Cortex\Mcp\Tools\ListVirtualAgentsTool` → `JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\ListVirtualAgentsTool`
  - `JayI\Cortex\Mcp\Tools\PublishConcreteAgentVersionTool` → `JayI\Cortex\Domains\ConcreteAgent\Mcp\Tools\PublishConcreteAgentVersionTool`
  - `JayI\Cortex\Mcp\Tools\PublishServerInstructionVersionTool` → `JayI\Cortex\Domains\McpServer\Mcp\Tools\PublishServerInstructionVersionTool`
  - `JayI\Cortex\Mcp\Tools\PublishVirtualAgentVersionTool` → `JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\PublishVirtualAgentVersionTool`
  - `JayI\Cortex\Mcp\Tools\RunConcreteAgentTool` → `JayI\Cortex\Domains\ConcreteAgent\Mcp\Tools\RunConcreteAgentTool`
  - `JayI\Cortex\Mcp\Tools\RunVirtualAgentTool` → `JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\RunVirtualAgentTool`
  - `JayI\Cortex\Mcp\Tools\ShowConcreteAgentTool` → `JayI\Cortex\Domains\ConcreteAgent\Mcp\Tools\ShowConcreteAgentTool`
  - `JayI\Cortex\Mcp\Tools\ShowServerInstructionsTool` → `JayI\Cortex\Domains\McpServer\Mcp\Tools\ShowServerInstructionsTool`
  - `JayI\Cortex\Mcp\Tools\ShowVirtualAgentTool` → `JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\ShowVirtualAgentTool`
  - `JayI\Cortex\Mcp\Tools\ShowVirtualAgentVersionTool` → `JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\ShowVirtualAgentVersionTool`
  - `JayI\Cortex\Mcp\Tools\UpdateConcreteAgentToolsTool` → `JayI\Cortex\Domains\ConcreteAgent\Mcp\Tools\UpdateConcreteAgentToolsTool`
  - `JayI\Cortex\Mcp\Tools\UpdateVirtualAgentTool` → `JayI\Cortex\Domains\VirtualAgent\Mcp\Tools\UpdateVirtualAgentTool`
  - `JayI\Cortex\Models\Concerns\DispatchesModelEvents` → `JayI\Cortex\Support\Models\Concerns\DispatchesModelEvents`
  - `JayI\Cortex\Models\ConcreteAgentOverride` → `JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel`
  - `JayI\Cortex\Models\ConcreteAgentOverrideVersion` → `JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel`
  - `JayI\Cortex\Models\McpInstruction` → `JayI\Cortex\Domains\McpServer\Models\McpInstructionModel`
  - `JayI\Cortex\Models\McpInstructionVersion` → `JayI\Cortex\Domains\McpServer\Models\McpInstructionVersionModel`
  - `JayI\Cortex\Models\ToolDescription` → `JayI\Cortex\Domains\Tool\Models\ToolDescriptionModel`
  - `JayI\Cortex\Models\ToolDescriptionVersion` → `JayI\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel`
  - `JayI\Cortex\Models\VirtualAgent` → `JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel`
  - `JayI\Cortex\Models\VirtualAgentVersion` → `JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel`
  - `JayI\Cortex\Policies\ConcreteAgentOverridePolicy` → `JayI\Cortex\Domains\ConcreteAgent\Policies\ConcreteAgentOverridePolicy`
  - `JayI\Cortex\Policies\ConcreteAgentOverrideVersionPolicy` → `JayI\Cortex\Domains\ConcreteAgent\Policies\ConcreteAgentOverrideVersionPolicy`
  - `JayI\Cortex\Policies\McpInstructionPolicy` → `JayI\Cortex\Domains\McpServer\Policies\McpInstructionPolicy`
  - `JayI\Cortex\Policies\McpInstructionVersionPolicy` → `JayI\Cortex\Domains\McpServer\Policies\McpInstructionVersionPolicy`
  - `JayI\Cortex\Policies\Policy` → `JayI\Cortex\Support\Policies\Policy`
  - `JayI\Cortex\Policies\ToolDescriptionPolicy` → `JayI\Cortex\Domains\Tool\Policies\ToolDescriptionPolicy`
  - `JayI\Cortex\Policies\ToolDescriptionVersionPolicy` → `JayI\Cortex\Domains\Tool\Policies\ToolDescriptionVersionPolicy`
  - `JayI\Cortex\Policies\VirtualAgentPolicy` → `JayI\Cortex\Domains\VirtualAgent\Policies\VirtualAgentPolicy`
  - `JayI\Cortex\Policies\VirtualAgentVersionPolicy` → `JayI\Cortex\Domains\VirtualAgent\Policies\VirtualAgentVersionPolicy`
  - `JayI\Cortex\Runtime\AgentFactory` → `JayI\Cortex\Domains\VirtualAgent\Services\AgentFactory`
  - `JayI\Cortex\Runtime\DbAgent` → `JayI\Cortex\Domains\VirtualAgent\Support\DbAgent`
  - `JayI\Cortex\Tools\Concerns\HasVersionedDescription` → `JayI\Cortex\Domains\Tool\Concerns\HasVersionedDescription`
  - `JayI\Cortex\Tools\DescribedTool` → `JayI\Cortex\Domains\Tool\Support\DescribedTool`
  - `JayI\Cortex\Tools\Tool` → `JayI\Cortex\Domains\Tool\Support\Tool`
  - `JayI\Cortex\Tools\ToolDescriptionOverrides` → `JayI\Cortex\Domains\Tool\Services\ToolDescriptionOverrides`
  - `JayI\Cortex\Tools\ToolName` → `JayI\Cortex\Domains\Tool\Support\ToolName`
  - `JayI\Cortex\Tools\ToolRegistry` → `JayI\Cortex\Domains\Tool\Services\ToolRegistry`
  - Events: `JayI\Cortex\Events\Action\{Name}` and `JayI\Cortex\Events\Model\{Name}` → `JayI\Cortex\Domains\{Domain}\Events\{Name}`, names unchanged (the domain is the entity's: `VirtualAgent*` and `Providers*` → `VirtualAgent`, `ConcreteAgent*` → `ConcreteAgent`, `ToolDescription*` and `Tools*` → `Tool`, `McpInstruction*` and `McpServers*` → `McpServer`).
- Requires the domain-module releases of `jayi/atrium` and `jayi/pennantplus` (`JayI\Atrium\Domains\...`, `JayI\PennantPlus\Domains\...`).

### Added

- Audit history: `GET /cortex/history` (route `cortex.history.index`, inside the JSON API group) and the `list-cortex-history-tool` MCP tool list Cortex's audit entries, newest first, through jayi/foundation. Both answer "not installed" (404 over HTTP) until an audit log such as jayi/keen is installed.
- Virtual agents: DB-backed agents (`cortex_virtual_agents`) that own their prompt, versioned in place with immutable versions and a published-version pointer (`cortex_virtual_agent_versions`), plus registered tools, provider/model settings, virtual sub-agents (`cortex_virtual_agent_sub_agents`, with cycle protection) and concrete sub-agents. Updating the instructions saves a new published version. All Cortex tables use ULID primary keys.
- Concrete agents: `AgentRegistry` registers class-based `Laravel\Ai\Contracts\Agent` implementations by name via config (`cortex.agents`) or `Cortex::agents()->register()`. Registered agents are listed, runnable and attachable as sub-agents of virtual agents.
- `JayI\Cortex\Agents\Agent` base class and `HasCortexOverrides` trait: agents declare `defaultInstructions()` and `defaultTools()`, and run with the published Cortex overrides (`cortex_concrete_agent_overrides`, `cortex_concrete_agent_override_versions`) when they exist — a versioned prompt override and a toolset override picked from the class's own tools and registered Cortex tools. `#[LockedTools]` opts an agent out of toolset overrides.
- `DbAgent` implements `CanActAsTool`, so a virtual sub-agent is offered to its parent under its slug and description instead of `DbAgent`.
- `ToolRegistry` for registering `Laravel\Ai\Contracts\Tool` classes by name via config (`cortex.tools`) or `Cortex::tools()->register()`.
- Tool tags: tools are tagged at registration (`Cortex::tools()->register($name, $class, $tags)` or a `['class' => ..., 'tags' => [...]]` config entry) and from namespace patterns (`cortex.tool_tags.namespaces`). The dashboard tool list filters by tag, the virtual and concrete agent tool pickers filter by tag, name and selection, and `GET /cortex/tools` and `list-tools-tool` take a `tag` filter and return each tool's `tags`.
- Agent execution on the Laravel AI SDK: `Cortex::runVirtualAgent()`, `Cortex::runConcreteAgent()`, `Cortex::virtualAgent()`, `Cortex::concreteAgent()`, `POST /cortex/virtual-agents/{slug}/run`, `POST /cortex/concrete-agents/{agent}/run`, and the matching MCP tools.
- REST API under the configurable `cortex` prefix covering virtual agents and their prompt versions, concrete agents and their overrides, tools, and run.
- `CortexServer` MCP server with tools at parity with the API, config-gated web (`Mcp::web`) and local (`Mcp::local`) transports, disabled by default.
- `laravel/ai` (^0.11) and `laravel/mcp` (^1.0) dependencies.
- MCP server instruction management with immutable versioning and a published-version pointer (`cortex_mcp_instructions`, `cortex_mcp_instruction_versions`): published overrides replace a server's code-declared `#[Instructions]` at runtime.
- `McpServerRegistry` for registering MCP server classes by name via config (`cortex.mcp.servers`) or `Cortex::servers()->register()`; Cortex's own server is always registered as `cortex`.
- `JayI\Cortex\Mcp\Server` base class and `HasVersionedInstructions` trait so any Laravel MCP server can serve its published instruction override.
- REST endpoints under `/cortex/servers` for listing servers and managing instruction overrides, six matching MCP tools on `CortexServer` (25 tools in all), and a Servers section in the dashboard with a versioned instructions editor.

- Model events: every Eloquent hook of every Cortex model dispatches its own class in `JayI\Cortex\Events\Model` (`{Model}{Hook}Event`, e.g. `VirtualAgentVersionCreatedEvent`) through the `DispatchesModelEvents` trait. All implement `JayI\Cortex\Contracts\ModelLifecycleEvent`.
- Action events: every action dispatches a start event before its work and a finish event with its result (`JayI\Cortex\Events\Action`, e.g. `VirtualAgentRunningActionEvent` / `VirtualAgentRanActionEvent`). Start events implement `ActionStartingEvent`. Finish events implement `ActionFinishedEvent`, dispatch after commit and are skipped when the action throws. See `docs/events.md`.
- Policies for every model (`JayI\Cortex\Policies`), registered with the Gate from the new `cortex.policies` config. Every API endpoint and MCP tool that touches a model now authorizes through them, as the signed-in user or as a guest. The bundled policies allow everything, since Cortex records have no owner, so existing behaviour is unchanged. Version policies defer to their virtual agent or override through the Gate. See `docs/policies.md`.

- Dashboard permission gates: every Atrium page, action and control is checked against the `cortex.policies` policies exactly as the JSON API and MCP tools check them, through `JayI\Cortex\Http\Ui\ScreenAccess` and the new `@cortexCan` Blade conditional. Navigation items, buttons, forms and cards are shown only when their action would be allowed, the run page offers only the agents the user may `run`, and search returns only what the searcher may view.
- `JayI\Cortex\Features\CortexSupportFeature` (needs `jayi/pennantplus`, suggested) and the `cortex.atrium.features` config: switch Cortex in Atrium on and off as a whole. Feature classes that cannot be loaded are skipped.
- `JayI\Cortex\Atrium\Badges` maps each dashboard state to its Atrium colour.
- Per-record history on the dashboard: `<x-atrium::audit-trail>` shows Cortex's audit entries on the virtual agents list, and each virtual agent's, tool description's, MCP server instruction's and concrete agent override's own entries on its screen. Nothing renders until an audit log such as jayi/keen is installed.

### Changed

- The Atrium screens use Atrium's components only: searches are `x-atrium::search-input`, tag filters are `x-atrium::chip`s (linked on the tool list, Alpine-driven in the tool pickers), the picker's "selected only" box is a bare `x-atrium::form.checkbox`, and the status and first prompt or agent error come from `<x-atrium::flash :keys="['prompt', 'agent']" />`.
- `CortexPlugin::features()` uses Atrium's `featuresFromConfig()`, and the plugin's `key()` / `label()` come from Atrium's base derivation (still `cortex` / `Cortex`).
- The Run agent navigation item and page follow `viewAny` on either kind of agent, so building the dashboard navigation no longer queries every agent; the page lists the agents the user may run, or says there are none.
- The Atrium screens follow Atrium's screen conventions: actions, tabs and back links are icon buttons (the label is the tooltip and accessible name), states are status dots carrying `data-status`, and every navigation item has a Heroicons icon. Tool tag filters are linked chips. Requires `jayi/atrium` at f5eb488 or later.
- The dashboard pages now authorize: before, they ran actions without asking the policies the API asks.

- The dashboard is server-rendered Blade built on `jayi/atrium`, which Cortex now requires, replacing the Vue 3 SPA. Cortex registers an Atrium plugin with navigation, routes under `/atrium/cortex/...`, search over virtual and concrete agents, and a settings panel. Atrium owns the path, middleware and `viewAtrium` gate, so the `ui.auth` config, the `UiTokenResolver` contract and the `/cortex/ui` route are gone; `ui.enabled` remains as the switch.
- Requires `laravel/framework` instead of `illuminate/support`, since the package uses form requests, events and queues from the framework.

### Removed

- `resources/css/atrium.css` and its registration through Atrium's style hook: Cortex ships no styles, and every class its views use comes from Atrium's stylesheet.
- The `cortex::ui.partials.status` view, replaced by Atrium's `flash` component.
- The TypeScript SDK (`@jayi/cortex-sdk`, `sdk/`), its npm workspace and `sdk:generate`/`sdk:build` scripts, and the `dedoc/scramble` dev dependency that exported its OpenAPI spec.
- The `cortex-assets` publish tag and the empty `public/` directory it published. The dashboard is Blade rendered through Atrium and ships no assets of its own.
- The skeleton `cortex:placeholder` Artisan command and the `cortex::messages.placeholder` translation.

### Fixed

- Agents calling an MCP tool whose `handle()` type-hints its own `Laravel\Mcp\Request` subclass now pass their arguments to it. Previously laravel/ai's `McpServerTool` bound the arguments only as the base request, so such tools, including Cortex's own MCP tools, received an empty request and failed validation.


## [v0.1.0](https://github.com/jayi/cortex/compare/...v0.1.0) - 202x-xx-xx

Initial pre-release.
