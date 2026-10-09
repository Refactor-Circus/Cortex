<?php

declare(strict_types=1);

use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Testing\TestResponse;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use Laravel\Mcp\Transport\JsonRpcResponse;
use RefactorCircus\Cortex\Mcp\CortexServer;
use RefactorCircus\Cortex\Tests\PennantPlusTestCase;
use RefactorCircus\Cortex\Tests\TestCase;

uses(TestCase::class)->in('ArchTest.php', 'Feature', 'Unit');
uses(PennantPlusTestCase::class)->in('Modes/PennantPlus');

/**
 * Call a catalog tool the way a client must now reach it.
 *
 * Every Cortex tool sits behind ToolSearch, so the individual tools are no
 * longer registered primitives: they are reachable only through the
 * execute_tools entry point. This resolves the tool's registered name and
 * routes the call through that entry point, returning the inner tool's own
 * response so the usual assertions still apply.
 *
 * @param  class-string<Tool>  $tool
 * @param  array<string, mixed>  $arguments
 */
function mcpTool(string $tool, array $arguments = []): TestResponse
{
    $entryPoints = (new CortexServer(new FakeTransporter))->createContext()->tools();

    $execute = $entryPoints->firstOrFail(
        fn (Tool $candidate): bool => $candidate->name() === 'execute_tools',
    );

    $primitive = app($tool);

    $envelope = $execute->handle(new Request([
        'calls' => [['name' => $primitive->name(), 'arguments' => $arguments]],
    ]));

    /** @var array<string, mixed> $decoded */
    $decoded = json_decode(
        (string) collect($envelope)->firstOrFail()->content(),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    /** @var array<string, mixed> $result */
    $result = $decoded['results'][0] ?? [];

    // execute_tools reports each call under results[], which moves the inner
    // tool's own content and structuredContent one level down. Lifting the
    // single result back to the top level keeps the response assertions
    // meaningful against the tool that actually ran.
    return new TestResponse($primitive, JsonRpcResponse::result(1, array_filter([
        'content' => $result['content'] ?? [],
        'structuredContent' => $result['structuredContent'] ?? null,
        'isError' => $result['isError'] ?? false,
    ], fn (mixed $value): bool => $value !== null)));
}
