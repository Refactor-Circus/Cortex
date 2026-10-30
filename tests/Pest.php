<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Testing\TestResponse;
use Laravel\Mcp\Server\Tool;
use RefactorCircus\Atrium\Testing\ResetsScreenExtensions;
use RefactorCircus\Cortex\Mcp\CortexServer;
use RefactorCircus\Cortex\Tests\PennantPlusTestCase;
use RefactorCircus\Cortex\Tests\RenamedTablesTestCase;
use RefactorCircus\Cortex\Tests\TestCase;
use RefactorCircus\Cortex\Tests\WithoutAtrium\WithoutAtriumTestCase;
use RefactorCircus\Keystone\Testing\McpTools;

// Every suite but WithoutAtrium also resets Atrium's screen extensions after
// each test. The trait is named only when it exists: Pest loads this file for
// the without-Atrium suite too, and the `without-atrium` CI job uninstalls it.
$resets = trait_exists(ResetsScreenExtensions::class) ? [ResetsScreenExtensions::class] : [];

// TestCase serves the workbench test host and registers the workbench owner policies.
uses(TestCase::class, ...$resets)->in('ArchTest.php', 'Feature', 'Unit');
uses(PennantPlusTestCase::class, ...$resets)->in('Modes/PennantPlus');
uses(RenamedTablesTestCase::class, ...$resets)->in('Modes/RenamedTables');

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
