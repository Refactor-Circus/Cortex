<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Testing\TestResponse;
use Laravel\Mcp\Server\Tool;
use RefactorCircus\Cortex\Mcp\CortexServer;
use RefactorCircus\Cortex\Tests\PennantPlusTestCase;
use RefactorCircus\Cortex\Tests\RenamedTablesTestCase;
use RefactorCircus\Cortex\Tests\TestCase;
use RefactorCircus\Cortex\Tests\WithoutAtrium\WithoutAtriumTestCase;
use RefactorCircus\Keystone\Testing\McpTools;

// TestCase also resets Atrium's screen extensions after every test
// (ResetsScreenExtensions), serves the workbench test host and registers the
// workbench owner policies.
uses(TestCase::class)->in('ArchTest.php', 'Feature', 'Unit');
uses(PennantPlusTestCase::class)->in('Modes/PennantPlus');
uses(RenamedTablesTestCase::class)->in('Modes/RenamedTables');

// Run only by phpunit.without-atrium.xml (`composer test:without-atrium`),
// with Atrium's autoload prefix removed.
uses(WithoutAtriumTestCase::class)->in('WithoutAtrium');

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
