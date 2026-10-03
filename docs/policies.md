# Policies

Cortex registers a policy for each of its models from `cortex.policies`, and the JSON API and MCP tools check every call that touches a model through the Gate:

```php
'policies' => [
    VirtualAgent::class => \JayI\Cortex\Policies\VirtualAgentPolicy::class,
    VirtualAgentVersion::class => \JayI\Cortex\Policies\VirtualAgentVersionPolicy::class,
    ConcreteAgentOverride::class => \JayI\Cortex\Policies\ConcreteAgentOverridePolicy::class,
    ConcreteAgentOverrideVersion::class => \JayI\Cortex\Policies\ConcreteAgentOverrideVersionPolicy::class,
    ToolDescription::class => \JayI\Cortex\Policies\ToolDescriptionPolicy::class,
    ToolDescriptionVersion::class => \JayI\Cortex\Policies\ToolDescriptionVersionPolicy::class,
    McpInstruction::class => \JayI\Cortex\Policies\McpInstructionPolicy::class,
    McpInstructionVersion::class => \JayI\Cortex\Policies\McpInstructionVersionPolicy::class,
],
```

## What the bundled policies allow

Cortex records have no owner. Agents and overrides are shared configuration, so there is no "owner may do anything" rule to apply. Instead:

- **`VirtualAgentPolicy`, `ConcreteAgentOverridePolicy`, `ToolDescriptionPolicy`, `McpInstructionPolicy`** allow every ability, for signed-in users and guests alike. Your route and MCP middleware stay the only gate, exactly as before policies existed. Nothing that worked without them is refused.
- **`VirtualAgentVersionPolicy`, `ConcreteAgentOverrideVersionPolicy`, `ToolDescriptionVersionPolicy`, `McpInstructionVersionPolicy`** ask the Gate about the parent virtual agent or override. Listing or reading versions needs `view` on the parent. Adding or publishing a version needs `update` on the parent. Versions are immutable, so no ability changes or deletes one.

Because version policies go through the Gate, replacing the virtual agent policy also governs its prompt versions.

## What each endpoint checks

Calls are checked as the authenticated user, or as a guest when nobody is signed in.

| Endpoint | MCP tool | Ability | Subject |
| --- | --- | --- | --- |
| `GET /virtual-agents` | `list-virtual-agents-tool` | `viewAny` | `VirtualAgent::class` |
| `POST /virtual-agents` | `create-virtual-agent-tool` | `create` | `VirtualAgent::class` |
| `GET /virtual-agents/{slug}` | `show-virtual-agent-tool` | `view` | the agent |
| `PATCH /virtual-agents/{slug}` | `update-virtual-agent-tool` | `update` | the agent |
| `DELETE /virtual-agents/{slug}` | `delete-virtual-agent-tool` | `delete` | the agent |
| `POST /virtual-agents/{slug}/run` | `run-virtual-agent-tool` | `run` | the agent |
| `GET /virtual-agents/{slug}/versions` | `list-virtual-agent-versions-tool` | `viewAny` | `[VirtualAgentVersion::class, $agent]` |
| `POST /virtual-agents/{slug}/versions` | `create-virtual-agent-version-tool` | `create` | `[VirtualAgentVersion::class, $agent]` |
| `GET /virtual-agents/{slug}/versions/{version}` | `show-virtual-agent-version-tool` | `view` | the version |
| `POST /virtual-agents/{slug}/versions/{version}/publish` | `publish-virtual-agent-version-tool` | `publish` | the version |
| `GET /concrete-agents` | `list-concrete-agents-tool` | `viewAny` | `ConcreteAgentOverride::class` |
| `GET /concrete-agents/{agent}` | `show-concrete-agent-tool` | `view` | the override |
| `POST /concrete-agents/{agent}/run` | `run-concrete-agent-tool` | `run` | the override |
| `PUT /concrete-agents/{agent}/tools` | `update-concrete-agent-tools-tool` | `update` | the override |
| `DELETE /concrete-agents/{agent}/override` | `delete-concrete-agent-override-tool` | `delete` | the override |
| `GET /concrete-agents/{agent}/versions` | `list-concrete-agent-versions-tool` | `viewAny` | `[ConcreteAgentOverrideVersion::class, $override]` |
| `POST /concrete-agents/{agent}/versions` | `create-concrete-agent-version-tool` | `create` | `[ConcreteAgentOverrideVersion::class, $override]` |
| `POST /concrete-agents/{agent}/versions/{version}/publish` | `publish-concrete-agent-version-tool` | `publish` | the version |
| `GET /tools/{tool}/description` | | `view` | the override |
| `DELETE /tools/{tool}/description` | | `delete` | the override |
| `GET /tools/{tool}/description/versions` | | `viewAny` | `[ToolDescriptionVersion::class, $description]` |
| `POST /tools/{tool}/description/versions` | | `create` | `[ToolDescriptionVersion::class, $description]` |
| `POST /tools/{tool}/description/versions/{version}/publish` | | `publish` | the version |
| `GET /servers/{server}/instructions` | `show-server-instructions-tool` | `view` | the override |
| `DELETE /servers/{server}/instructions` | `delete-server-instructions-tool` | `delete` | the override |
| `GET /servers/{server}/instructions/versions` | `list-server-instruction-versions-tool` | `viewAny` | `[McpInstructionVersion::class, $instruction]` |
| `POST /servers/{server}/instructions/versions` | `create-server-instruction-version-tool` | `create` | `[McpInstructionVersion::class, $instruction]` |
| `POST /servers/{server}/instructions/versions/{version}/publish` | `publish-server-instruction-version-tool` | `publish` | the version |

Creating the first version of a tool description, server instruction or concrete agent override also creates the override. That call is checked against an unsaved override for that tool, server or agent, so your policy sees `$description->tool`, `$instruction->server` or `$override->agent` either way. Showing, running or setting the tools of a concrete agent with no override row is checked the same way.

`GET /tools`, `GET /servers` and `GET /providers` and their MCP tools read the tool and server registries and the provider config. No model is involved, so they check no policy.

An HTTP call that is refused returns `403`. An MCP call that is refused returns the error `Unauthorized.`.

## The dashboard

The Atrium pages ask the same questions, through `JayI\Cortex\Http\Ui\ScreenAccess`: each page and each action checks the ability and subject in the table above and answers `403` when refused, and each control on a page - a navigation item, button, form or card - is shown only when its action would be allowed. Views ask with the `@cortexCan` Blade conditional, which is the same check:

```blade
@cortexCan('update', $agent)
    ...
@endcortexCan

@cortexCan('create', \JayI\Cortex\Models\VirtualAgentVersion::class, [$agent])
    ...
@endcortexCan
```

| Navigation item | Shown when |
| --- | --- |
| Virtual agents | `viewAny` on `VirtualAgent::class` |
| Concrete agents | `viewAny` on `ConcreteAgentOverride::class` |
| Run agent | `viewAny` on either kind of agent; the page offers only the agents the user may `run`, and says so when there are none |
| Tools, Servers | always, as `GET /tools` and `GET /servers` check no policy |

A tool description or server instruction page with no override yet is checked against an unsaved override, as a concrete agent page is, so it opens before the first version exists. A virtual agent page shown to someone with `view` but not `update` is read-only.

## Replacing a policy

- **Per ability:** extend a bundled policy and override the method for that ability. Then point its model at your class in `cortex.policies`:

  ```php
  namespace App\Policies;

  use Illuminate\Contracts\Auth\Authenticatable;
  use JayI\Cortex\Models\VirtualAgent;
  use JayI\Cortex\Policies\VirtualAgentPolicy as CortexVirtualAgentPolicy;

  class VirtualAgentPolicy extends CortexVirtualAgentPolicy
  {
      // Only prompt editors may change agents, or add and publish prompt versions.
      public function update(?Authenticatable $user, VirtualAgent $agent): bool
      {
          return $user?->can('edit-prompts') ?? false;
      }
  }
  ```

  ```php
  'policies' => [
      VirtualAgent::class => App\Policies\VirtualAgentPolicy::class,
      // ...
  ],
  ```

- **Whole policy:** point the model at your own class. It does not have to extend the bundled one.
- **Signed-in users only:** type the user parameter as non-nullable (`Authenticatable $user`). The Gate then refuses guests without calling the method.

Your application's own `$user->can('run', $agent)` checks use the same policies.
