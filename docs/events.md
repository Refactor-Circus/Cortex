# Events

Cortex fires two families of events:

- **Model events:** every Eloquent lifecycle hook of every Cortex model, one class per hook.
- **Action events:** a start event and a finish event for every action. The JSON API and the MCP tools both run through the actions, and so does your code when it resolves one from the container.

Every event carries the models involved, not just their ids. Every event also uses `Dispatchable` and `SerializesModels`, so it can be dispatched with `::dispatch()` and handled by queued listeners.

## Model events

Each model fires a class-based event for the 10 hooks that apply to models without soft deletes: `retrieved`, `creating`, `created`, `updating`, `updated`, `saving`, `saved`, `deleting`, `deleted` and `replicating`. No Cortex model uses soft deletes, so there are no `restoring`, `restored`, `trashed`, `forceDeleting` or `forceDeleted` events.

They live in each domain's `Events` namespace (`RefactorCircus\Cortex\Domains\{Domain}\Events`) and are named `{Entity}{Hook}Event`, the entity being the model's name less its `Model` suffix, for example `VirtualAgentCreatingEvent` or `ConcreteAgentOverrideDeletedEvent`. The model is a typed property:

| Model | Property |
| --- | --- |
| `VirtualAgent` | `$event->agent` |
| `VirtualAgentVersion` | `$event->version` |
| `ConcreteAgentOverride` | `$event->override` |
| `ConcreteAgentOverrideVersion` | `$event->version` |
| `ToolDescription` | `$event->description` |
| `ToolDescriptionVersion` | `$event->version` |
| `McpInstruction` | `$event->instruction` |
| `McpInstructionVersion` | `$event->version` |

The model is also available as `$event->model()`, alongside `$event->hook()`.

```php
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionCreatedEvent;

Event::listen(VirtualAgentVersionCreatedEvent::class, function (VirtualAgentVersionCreatedEvent $event) {
    Log::info('New prompt version', ['agent' => $event->version->virtual_agent_id, 'version' => $event->version->version]);
});
```

- **Synchronous:** they fire when Eloquent fires the hook, as Eloquent's own events do.
- **Cancelling:** a `creating`, `updating`, `saving` or `deleting` listener that returns `false` stops the operation.
- **Your own mapping:** entries a model declares on `$dispatchesEvents` win over the derived ones.

The mapping is done by the `DispatchesModelEvents` trait (`RefactorCircus\Foundation\Models\Concerns`).

## Action events

Every action dispatches two events:

1. **A start event** (`…ingActionEvent`, e.g. `VirtualAgentVersionPublishingActionEvent`), before the action does any work. It carries the action's input.
2. **A finish event** (`…edActionEvent`, e.g. `VirtualAgentVersionPublishedActionEvent`), once the action has succeeded. It carries the result.

```php
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentRanActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionPublishedActionEvent;

Event::listen(VirtualAgentVersionPublishedActionEvent::class, function (VirtualAgentVersionPublishedActionEvent $event) {
    Notification::route('slack', config('services.slack.prompts'))
        ->notify(new PromptPublished($event->agent));
});

Event::listen(VirtualAgentRanActionEvent::class, function (VirtualAgentRanActionEvent $event) {
    Metrics::record($event->agent->slug, $event->response->usage);
});
```

- **Failure:** an action that throws fires its start event and no finish event. Publishing a version number that does not exist fires `VirtualAgentVersionPublishingActionEvent` only.
- **Timing:** finish events implement `ShouldDispatchAfterCommit`, so inside a transaction they fire once it commits and never for work that was rolled back. Start events fire immediately.

Action events live beside the model events, in `RefactorCircus\Cortex\Domains\{Domain}\Events`.

## Listening to a whole family

Each family implements an interface in `RefactorCircus\Foundation\Contracts`, and Laravel delivers an event to listeners of the interfaces it implements:

| Interface | Receives |
| --- | --- |
| `ModelLifecycleEvent` | every model event |
| `ActionStartingEvent` | every action start event |
| `ActionFinishedEvent` | every action finish event |

```php
use RefactorCircus\Foundation\Contracts\ActionFinishedEvent;

Event::listen(ActionFinishedEvent::class, fn (ActionFinishedEvent $event) => AuditLog::record($event));
```

## Every action and its events

| Action | Start event | Carries | Finish event | Carries |
| --- | --- | --- | --- | --- |
| `CreateVirtualAgentAction` | `VirtualAgentCreatingActionEvent` | `$data` | `VirtualAgentCreatedActionEvent` | `$agent` |
| `CreateVirtualAgentVersionAction` | `VirtualAgentVersionCreatingActionEvent` | `$agent`, `$data` | `VirtualAgentVersionCreatedActionEvent` | `$agent`, `$version` |
| `DeleteVirtualAgentAction` | `VirtualAgentDeletingActionEvent` | `$agent` | `VirtualAgentDeletedActionEvent` | `$agent` |
| `ListVirtualAgentsAction` | `VirtualAgentsListingActionEvent` | `$page` | `VirtualAgentsListedActionEvent` | `$agents` |
| `ListVirtualAgentVersionsAction` | `VirtualAgentVersionsListingActionEvent` | `$agent`, `$page` | `VirtualAgentVersionsListedActionEvent` | `$agent`, `$versions` |
| `PublishVirtualAgentVersionAction` | `VirtualAgentVersionPublishingActionEvent` | `$agent`, `$version` | `VirtualAgentVersionPublishedActionEvent` | `$agent` |
| `RunVirtualAgentAction` | `VirtualAgentRunningActionEvent` | `$agent`, `$input` | `VirtualAgentRanActionEvent` | `$agent`, `$input`, `$response` |
| `ShowVirtualAgentAction` | `VirtualAgentShowingActionEvent` | `$agent` | `VirtualAgentShownActionEvent` | `$agent` |
| `ShowVirtualAgentVersionAction` | `VirtualAgentVersionShowingActionEvent` | `$agent`, `$version` | `VirtualAgentVersionShownActionEvent` | `$agent`, `$version` |
| `UpdateVirtualAgentAction` | `VirtualAgentUpdatingActionEvent` | `$agent`, `$data` | `VirtualAgentUpdatedActionEvent` | `$agent` |
| `CreateConcreteAgentVersionAction` | `ConcreteAgentVersionCreatingActionEvent` | `$agent`, `$data` | `ConcreteAgentVersionCreatedActionEvent` | `$agent`, `$version` |
| `DeleteConcreteAgentOverrideAction` | `ConcreteAgentOverrideDeletingActionEvent` | `$override` | `ConcreteAgentOverrideDeletedActionEvent` | `$override` |
| `ListConcreteAgentsAction` | `ConcreteAgentsListingActionEvent` | — | `ConcreteAgentsListedActionEvent` | `$agents` |
| `ListConcreteAgentVersionsAction` | `ConcreteAgentVersionsListingActionEvent` | `$override` | `ConcreteAgentVersionsListedActionEvent` | `$override`, `$versions` |
| `PublishConcreteAgentVersionAction` | `ConcreteAgentVersionPublishingActionEvent` | `$override`, `$version` | `ConcreteAgentVersionPublishedActionEvent` | `$override` |
| `RunConcreteAgentAction` | `ConcreteAgentRunningActionEvent` | `$agent`, `$input` | `ConcreteAgentRanActionEvent` | `$agent`, `$input`, `$response` |
| `ShowConcreteAgentAction` | `ConcreteAgentShowingActionEvent` | `$agent` | `ConcreteAgentShownActionEvent` | `$agent` |
| `UpdateConcreteAgentToolsAction` | `ConcreteAgentToolsUpdatingActionEvent` | `$agent`, `$tools` | `ConcreteAgentToolsUpdatedActionEvent` | `$override` |
| `CreateMcpInstructionVersionAction` | `McpInstructionVersionCreatingActionEvent` | `$server`, `$data` | `McpInstructionVersionCreatedActionEvent` | `$server`, `$version` |
| `DeleteMcpInstructionAction` | `McpInstructionDeletingActionEvent` | `$instruction` | `McpInstructionDeletedActionEvent` | `$instruction` |
| `ListMcpInstructionVersionsAction` | `McpInstructionVersionsListingActionEvent` | `$instruction` | `McpInstructionVersionsListedActionEvent` | `$instruction`, `$versions` |
| `ListMcpServersAction` | `McpServersListingActionEvent` | — | `McpServersListedActionEvent` | `$servers` |
| `PublishMcpInstructionVersionAction` | `McpInstructionVersionPublishingActionEvent` | `$instruction`, `$version` | `McpInstructionVersionPublishedActionEvent` | `$instruction` |
| `ShowMcpInstructionAction` | `McpInstructionShowingActionEvent` | `$server` | `McpInstructionShownActionEvent` | `$instruction` |
| `CreateToolDescriptionVersionAction` | `ToolDescriptionVersionCreatingActionEvent` | `$tool`, `$data` | `ToolDescriptionVersionCreatedActionEvent` | `$tool`, `$version` |
| `DeleteToolDescriptionAction` | `ToolDescriptionDeletingActionEvent` | `$description` | `ToolDescriptionDeletedActionEvent` | `$description` |
| `ListToolDescriptionVersionsAction` | `ToolDescriptionVersionsListingActionEvent` | `$description` | `ToolDescriptionVersionsListedActionEvent` | `$description`, `$versions` |
| `PublishToolDescriptionVersionAction` | `ToolDescriptionVersionPublishingActionEvent` | `$description`, `$version` | `ToolDescriptionVersionPublishedActionEvent` | `$description` |
| `ShowToolDescriptionAction` | `ToolDescriptionShowingActionEvent` | `$tool` | `ToolDescriptionShownActionEvent` | `$description` |
| `ListToolsAction` | `ToolsListingActionEvent` | — | `ToolsListedActionEvent` | `$tools` |
| `ListProvidersAction` | `ProvidersListingActionEvent` | — | `ProvidersListedActionEvent` | `$providers` |

`$data` is the validated input array. `$page` is the requested page number, or `null`. On the concrete agent actions `$agent` is the registered agent name, except on `ConcreteAgentShownActionEvent`, where it is the shown row. `$tools` is the new toolset, or `null` to clear the override. On the list actions for concrete agents, tools, servers and providers, the finish event carries the listed rows as arrays. `UpdateVirtualAgentAction` with changed instructions also runs `CreateVirtualAgentVersionAction`, so its events fire inside the update's.

## Testing

Fake only the events you assert on, so the rest of Cortex keeps working:

```php
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionPublishedActionEvent;

Event::fake([VirtualAgentVersionPublishedActionEvent::class]);

$this->postJson('/cortex/virtual-agents/support/versions/2/publish')->assertOk();

Event::assertDispatched(VirtualAgentVersionPublishedActionEvent::class, fn ($event) => $event->agent->slug === 'support');
```
