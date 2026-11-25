<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Testing\TestResponse;
use Laravel\Mcp\Server\Tool;
use RefactorCircus\Cortex\Mcp\CortexServer;
use RefactorCircus\Cortex\Tests\RenamedTablesTestCase;
use RefactorCircus\Cortex\Tests\TestCase;
use RefactorCircus\Keystone\Testing\McpTools;

// TestCase registers the workbench owner policies.
uses(TestCase::class)->in('ArchTest.php', 'Feature', 'Unit');
uses(RenamedTablesTestCase::class)->in('Modes/RenamedTables');

/**
 * Call a catalog tool the way a client reaches it: every Cortex tool sits
 * behind ToolSearch, so the call goes through the execute_tools entry point,
 * with its structured answer checked against the tool's output schema.
 *
 * @param  class-string<Tool>  $tool
 * @param  array<string, mixed>  $arguments
 */
function mcpTool(string $tool, array $arguments = []): TestResponse
{
    return McpTools::call(CortexServer::class, $tool, $arguments);
}

/**
 * composer.json as text without its `extra.atrium` block: the only place
 * the manifest may name Atrium is the declaration of Cortex's front-end
 * module (atrium/, built to dist/atrium), which Atrium's page loads.
 */
function manifestWithoutAtriumModule(): string
{
    /** @var array<string, mixed> $manifest */
    $manifest = json_decode((string) file_get_contents(dirname(__DIR__).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    if (is_array($manifest['extra'] ?? null)) {
        unset($manifest['extra']['atrium']);
    }

    return (string) json_encode($manifest, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
