<?php

declare(strict_types=1);

use RefactorCircus\Cortex\Atrium\Features\CortexSupportFeature;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Policies\ConcreteAgentOverridePolicy;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Policies\ConcreteAgentOverrideVersionPolicy;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Policies\McpInstructionPolicy;
use RefactorCircus\Cortex\Domains\ConcreteServer\Policies\McpInstructionVersionPolicy;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\RedirectDomain\Policies\RedirectDomainPolicy;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel;
use RefactorCircus\Cortex\Domains\Tool\Policies\ToolDescriptionPolicy;
use RefactorCircus\Cortex\Domains\Tool\Policies\ToolDescriptionVersionPolicy;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Policies\VirtualAgentPolicy;
use RefactorCircus\Cortex\Domains\VirtualAgent\Policies\VirtualAgentVersionPolicy;

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP API Routes
    |--------------------------------------------------------------------------
    |
    | Whether the Cortex management API routes load, and the prefix and
    | middleware applied to them, which the audit history route at
    | {prefix}/history shares. Add authentication middleware (e.g.
    | auth:sanctum) before exposing these routes in production - they manage
    | and execute agents.
    |
    */

    'routes' => [
        'enabled' => true,
        'prefix' => 'cortex',
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Policies
    |--------------------------------------------------------------------------
    |
    | The policy the Gate uses for each model. The JSON API and MCP tools
    | check every call against these, as the authenticated user (or as a
    | guest when nobody is signed in). Cortex records have no owner, so the
    | bundled policies allow everything and your route middleware stays the
    | gate, as before. Versions defer to their virtual agent or override:
    | reading one needs `view` on it, adding or publishing one needs `update`.
    | Point a model at your own class to replace its policy.
    |
    | updateTools: The McpInstructionModel ability that overrides or clears a
    |              concrete MCP server's tool list. The bundled default
    |              allows guests too, and whoever passes it can put any
    |              registered Cortex tool in front of every client of that
    |              server, so outside `local` Cortex logs a warning at boot
    |              while guests are allowed. A policy of your own without an
    |              updateTools method denies it. To allow trusted users only
    |              (README: "Who may change a server's tools"):
    |
    |              public function updateTools(?Authenticatable $user, McpInstructionModel $instruction): bool
    |              {
    |                  if ($user === null) {
    |                      return false;
    |                  }
    |
    |                  return $user->can('manage-mcp-tools');
    |              }
    |
    */

    'policies' => [
        VirtualAgentModel::class => VirtualAgentPolicy::class,
        VirtualAgentVersionModel::class => VirtualAgentVersionPolicy::class,
        ConcreteAgentOverrideModel::class => ConcreteAgentOverridePolicy::class,
        ConcreteAgentOverrideVersionModel::class => ConcreteAgentOverrideVersionPolicy::class,
        ToolDescriptionModel::class => ToolDescriptionPolicy::class,
        ToolDescriptionVersionModel::class => ToolDescriptionVersionPolicy::class,
        McpInstructionModel::class => McpInstructionPolicy::class,
        McpInstructionVersionModel::class => McpInstructionVersionPolicy::class,
        RedirectDomainModel::class => RedirectDomainPolicy::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    |
    | The table each of Cortex's records is stored in. Set these before
    | running the migrations: the migrations, models, validation rules and
    | queries all read them. Keys left out keep their default.
    |
    | virtual_agent_sub_agents: The pivot linking a virtual agent to the
    |                           virtual agents it may call as sub-agents.
    |
    */

    'tables' => [
        'virtual_agents' => 'cortex_virtual_agents',
        'virtual_agent_versions' => 'cortex_virtual_agent_versions',
        'virtual_agent_sub_agents' => 'cortex_virtual_agent_sub_agents',
        'tool_descriptions' => 'cortex_tool_descriptions',
        'tool_description_versions' => 'cortex_tool_description_versions',
        'mcp_instructions' => 'cortex_mcp_instructions',
        'mcp_instruction_versions' => 'cortex_mcp_instruction_versions',
        'concrete_agent_overrides' => 'cortex_concrete_agent_overrides',
        'concrete_agent_override_versions' => 'cortex_concrete_agent_override_versions',
        'redirect_domains' => 'cortex_redirect_domains',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard UI
    |--------------------------------------------------------------------------
    |
    | Cortex renders its dashboard through Atrium, which owns the path,
    | middleware and authorization gate. Set this to false to keep the JSON
    | API without adding Cortex to the dashboard.
    |
    */

    'ui' => [

        /*
        | Whether Cortex registers itself with the Atrium dashboard. The JSON
        | API is unaffected by this switch.
        */

        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Atrium
    |--------------------------------------------------------------------------
    |
    | features: Features that must all be on for Cortex to appear in Atrium
    |           at all - its navigation, search, settings and pages (which
    |           answer 404 otherwise). Atrium asks its feature resolver, so
    |           Pennant (through refactor-circus/pennantplus) or any other flag system
    |           decides.
    |
    |           CortexSupportFeature is on until its global value is set, and
    |           only its global value counts. Swap in a subclass to change
    |           that, or your own feature names. Feature classes that do not
    |           exist (without refactor-circus/pennantplus) are skipped, so nothing is
    |           checked until Pennant is installed. Empty always shows Cortex.
    |
    | Individual pages and controls are still shown per the policies above.
    |
    */

    'atrium' => [
        'features' => [
            CortexSupportFeature::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP Servers
    |--------------------------------------------------------------------------
    |
    | Cortex can register its MCP server for you. Both transports are
    | disabled by default. When enabling the web transport, add auth
    | middleware - the server manages and executes agents.
    |
    | The `servers` list holds MCP server classes whose instructions Cortex
    | manages (versioned overrides replacing the code-declared value when
    | published) and whose tool list it can override (see the updateTools
    | ability under Policies). Cortex's own server is always registered as
    | 'cortex', and its tools cannot be overridden.
    | String keys set the server's registered name; unkeyed entries
    | derive it from the class. Servers may also be registered at
    | runtime via Cortex::servers()->register($name, $class).
    |
    */

    'mcp' => [
        'web' => [
            'enabled' => false,
            'route' => 'mcp/cortex',
            'middleware' => [],
        ],
        'local' => [
            'enabled' => false,
            'handle' => 'cortex',
        ],
        'servers' => [
            // 'support' => \App\Mcp\SupportServer::class,
        ],

        /*
        |----------------------------------------------------------------------
        | MCP Warning Rate Limit
        |----------------------------------------------------------------------
        |
        | warning_ttl: How long, in seconds, Cortex stays quiet about a stored
        |              server override it cannot serve as saved once it has
        |              logged it: a stale, failing or ambiguous tool entry,
        |              a tools value that is not a list, the missing `tools`
        |              column, or a failed server context rebuild. Each logs
        |              once per server and entry per window, across requests,
        |              through the publication cache store, so use a store
        |              every node shares. Default 3600 (one hour); values
        |              below 1 count as 1. Lower it while fixing overrides,
        |              raise it to quiet a busy log.
        |
        */

        'warning_ttl' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth Redirect Domains
    |--------------------------------------------------------------------------
    |
    | MCP clients register themselves through laravel/mcp's dynamic client
    | registration (Mcp::oauthRoutes()), which only accepts redirect URIs on
    | the origins in `mcp.redirect_domains`. While this is enabled, Cortex
    | also accepts every origin stored in its redirect domains table, managed
    | from the dashboard, the JSON API ({prefix}/redirect-domains) and the MCP
    | tools, so a new client needs no deploy. A domain may belong to an owner
    | (Roster adds them per organization and per user) or to none.
    |
    | Registration is anonymous: any client may register on any allowed
    | origin, whoever added it. Users still approve each client on the OAuth
    | consent screen. The JSON API and MCP tools that manage the list follow
    | the RedirectDomainModel policy above and your route middleware, so keep
    | auth middleware in front of them. The configured domains, `*` and
    | localhost keep working as laravel/mcp documents; only config can allow
    | `*`. Set this to false to use the configured domains alone.
    |
    */

    'redirect_domains' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auditing
    |--------------------------------------------------------------------------
    |
    | Cortex keeps no audit log of its own. With refactor-circus/keen
    | installed, Keen records every change Cortex makes, and Cortex shows the
    | history on its Atrium pages, its `GET /cortex/history` route and its
    | history MCP tool. Without Keen none of that exists.
    |
    | enabled: Set to false to keep Cortex out of the audit log even while
    |          Keen is installed: Keen records nothing of Cortex's, and Cortex
    |          offers no history.
    |
    */

    'audit' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Publication Cache
    |--------------------------------------------------------------------------
    |
    | Published virtual agent prompts, tool description overrides, MCP server
    | instruction overrides and concrete agent overrides are cached so agent runs and MCP listings don't
    | hit the database on every request; the publishing actions invalidate
    | explicitly. When Redis is
    | available it is preferred and read via Cache::flexible()
    | (fresh/stale seconds below); any other store caches
    | until invalidation. Set `store` to pin a store, or
    | `enabled` to false to read straight from the
    | database on every pull.
    |
    | On Redis a publish clears the key, but a read or background refresh
    | already under way can write the previous content back, so a publish
    | may take up to `fresh` seconds to reach every worker. Lower `fresh`
    | (or pin a non-Redis `store`) where a publish must show at once.
    |
    */

    'cache' => [
        'enabled' => true,
        'store' => null,
        'fresh' => 300,
        'stale' => 86400,
    ],

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | The providers (and models) offered when configuring an agent, keyed by
    | provider name with a list of model names. Leave empty to offer every
    | text-capable provider configured for laravel/ai along with the
    | models it declares (default, smartest, cheapest).
    |
    */

    'providers' => [
        // 'anthropic' => ['claude-sonnet-5-5', 'claude-opus-5-5'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tools
    |--------------------------------------------------------------------------
    |
    | Tools available to agents. Each class must implement
    | Laravel\Ai\Contracts\Tool or extend Laravel\Mcp\Server\Tool (MCP tools
    | are wrapped for agent use automatically). String keys set the tool's
    | registered name; unkeyed entries derive it from the tool itself.
    | An array entry sets tags too. Tools may also be registered at runtime
    | via Cortex::tools()->register($name, $class, $tags).
    |
    */

    'tools' => [
        // 'search' => \App\Ai\Tools\SearchTool::class,
        // \App\Mcp\Tools\LookupTool::class,
        // 'lookup' => ['class' => \App\Mcp\Tools\LookupTool::class, 'tags' => ['catalog']],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tool Tags
    |--------------------------------------------------------------------------
    |
    | Tags group tools on the dashboard and in the agent tool pickers. A tool
    | gets the tags given when it is registered (the `tags` of an array
    | entry above, or register($name, $class, $tags)) plus one per matching
    | namespace pattern below, where `{tag}` stands for one namespace
    | segment: `App\Domains\{tag}\` tags App\Domains\Order\...\ShowOrderTool
    | as `order`. Tags are kebab-cased.
    |
    */

    'tool_tags' => [
        'namespaces' => [
            'App\\Domains\\{tag}\\',
            'App\\Modules\\{tag}\\',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Concrete Agents
    |--------------------------------------------------------------------------
    |
    | Class-based agents to manage in Cortex. Each class must implement
    | Laravel\Ai\Contracts\Agent. Registered agents can be run and attached
    | to virtual agents as sub-agents. Extend RefactorCircus\Cortex\Domains\ConcreteAgent\Support\Agent (or
    | use the HasCortexOverrides trait) so the prompt and toolset published
    | in Cortex replace the ones declared in code. String keys set the
    | agent's registered name; unkeyed entries derive it from the class
    | basename. Agents may also be registered at runtime via
    | Cortex::agents()->register($name, $class).
    |
    */

    'agents' => [
        // 'triage' => \App\Ai\Agents\TriageAgent::class,
    ],

];
