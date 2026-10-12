<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Support;

use RefactorCircus\Keystone\Database\Tables as KeystoneTables;

/**
 * The table each of Cortex's records lives in, from the `cortex.tables`
 * config key. Models, migrations, validation rules and queries all ask
 * here, so renaming a table is one config change made before the
 * migrations run.
 *
 * A published config that lists only some tables keeps the defaults for the
 * rest.
 */
final class Tables extends KeystoneTables
{
    protected const string CONFIG = 'cortex.tables';

    /**
     * @var array<string, string>
     */
    public const array DEFAULTS = [
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
    ];
}
