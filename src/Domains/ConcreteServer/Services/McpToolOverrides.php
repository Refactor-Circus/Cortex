<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Services;

use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Support\PublicationCache;
use RefactorCircus\Cortex\Support\WarningLimiter;

/**
 * Lookup of concrete MCP server tool overrides: server name to the stored
 * list of tool names, in stored order. Every override row with a non-null
 * `tools` counts, whether or not an instructions version is published
 * ({@see McpInstructionOverrides} reads published rows only).
 *
 * The map is cached under `mcpToolsKey()` until an action forgets it and
 * memoized per request: bound as a scoped singleton so nothing leaks across
 * Octane requests.
 *
 * A stored value that is not a list, and a table without the `tools` column
 * (the migration was not run), read as no override, with a rate-limited
 * warning.
 */
final class McpToolOverrides
{
    private const string MIGRATION = '2026_10_23_000002_add_tools_to_cortex_mcp_instructions';

    /**
     * @var array<string, mixed>|null
     */
    private ?array $overrides = null;

    public function __construct(
        private readonly PublicationCache $cache,
        private readonly ToolsColumn $column,
        private readonly WarningLimiter $warnings,
    ) {}

    /**
     * The stored tool list for the server (an empty list serves no tools),
     * or null to serve its code tools.
     *
     * @return list<string>|null
     */
    public function for(string $server): ?array
    {
        if (! $this->column->exists()) {
            $this->warnings->warn(
                $server,
                'column:tools',
                sprintf(
                    'Cortex serves the code tools of MCP server [%s]: the [%s] table has no tools column. Publish the cortex-migrations tag and run the %s migration.',
                    $server,
                    (new McpInstructionModel)->getTable(),
                    self::MIGRATION,
                ),
                ['server' => $server],
            );

            return null;
        }

        $tools = $this->all()[$server] ?? null;

        if ($tools === null) {
            return null;
        }

        if (! is_array($tools)) {
            $type = get_debug_type($tools);

            $this->warnings->warn(
                $server,
                'value_type:'.$type,
                sprintf(
                    'Cortex ignores the stored tool override of MCP server [%s]: it holds a %s instead of a list of tool names, so the code tools are served.',
                    $server,
                    $type,
                ),
                ['server' => $server, 'value_type' => $type],
            );

            return null;
        }

        return array_values(array_filter($tools, is_string(...)));
    }

    /**
     * Every stored `tools` value by server, as decoded from the column.
     *
     * @return array<string, mixed>
     */
    private function all(): array
    {
        /** @var array<string, mixed> */
        return $this->overrides ??= (array) $this->cache->remember(
            $this->cache->mcpToolsKey(),
            fn (): array => McpInstructionModel::query()
                ->whereNotNull('tools')
                ->get(['server', 'tools'])
                ->mapWithKeys(fn (McpInstructionModel $instruction): array => [
                    $instruction->server => $instruction->getAttribute('tools'),
                ])
                ->all(),
        );
    }
}
