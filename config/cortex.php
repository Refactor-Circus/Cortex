<?php

declare(strict_types=1);

use JayI\Cortex\Atrium\Features\CortexSupportFeature;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use JayI\Cortex\Domains\ConcreteAgent\Policies\ConcreteAgentOverridePolicy;
use JayI\Cortex\Domains\ConcreteAgent\Policies\ConcreteAgentOverrideVersionPolicy;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionModel;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionVersionModel;
use JayI\Cortex\Domains\McpServer\Policies\McpInstructionPolicy;
use JayI\Cortex\Domains\McpServer\Policies\McpInstructionVersionPolicy;
use JayI\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use JayI\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel;
use JayI\Cortex\Domains\Tool\Policies\ToolDescriptionPolicy;
use JayI\Cortex\Domains\Tool\Policies\ToolDescriptionVersionPolicy;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use JayI\Cortex\Domains\VirtualAgent\Policies\VirtualAgentPolicy;
use JayI\Cortex\Domains\VirtualAgent\Policies\VirtualAgentVersionPolicy;

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP API Routes
    |--------------------------------------------------------------------------
    |
    | The prefix and middleware applied to the Cortex management API routes.
    | Add authentication middleware (e.g. auth:sanctum) before exposing
    | these routes in production - they manage and execute agents.
    |
    */

    'routes' => [
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
    |           Pennant (through jayi/pennantplus) or any other flag system
    |           decides.
    |
    |           CortexSupportFeature is on until its global value is set, and
    |           only its global value counts. Swap in a subclass to change
    |           that, or your own feature names. Feature classes that do not
    |           exist (without jayi/pennantplus) are skipped, so nothing is
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
    | published). Cortex's own server is always registered as 'cortex'.
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
    | to virtual agents as sub-agents. Extend JayI\Cortex\Domains\ConcreteAgent\Support\Agent (or
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
