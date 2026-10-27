<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Services;

use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Support\PublicationCache;
use RefactorCircus\Cortex\Support\WarningLimiter;
use Throwable;

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
 * A stored value that is not a list, a table without the `tools` column
 * (the migration was not run) and a column check that throws (for example
 * with the connection down) read as no override, with a rate-limited
 * warning. The instructions override is read on its own and still applies.
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
        if (! $this->hasToolsColumn($server)) {
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
     * Whether tool overrides can be read for this call. A missing column and
     * a check that throws both read as no override, each with a rate-limited
     * warning; a throwing check is not remembered, so the next call checks
     * again.
     */
    private function hasToolsColumn(string $server): bool
    {
        try {
            $exists = $this->column->exists();
        } catch (Throwable $exception) {
            $this->warnings->warn(
                $server,
                'column-check:'.$exception::class,
                sprintf(
                    'Cortex serves the code tools of MCP server [%s]: checking the [%s] table for its tools column failed with %s. The check runs again on the next request.',
                    $server,
                    (new McpInstructionModel)->getTable(),
                    $exception::class,
                ),
                ['server' => $server, 'message' => $exception->getMessage()],
            );

            return false;
        }

        if (! $exists) {
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

            return false;
        }

        return true;
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
