<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\ClearConcreteServerToolsAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\UpdateConcreteServerToolsAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\ClearConcreteServerToolsData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\UpdateConcreteServerToolsData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Tests\Fixtures\EchoCortexTool;
use RefactorCircus\Cortex\Tests\Fixtures\EchoMcpTool;
use RefactorCircus\Cortex\Tests\Fixtures\OldNamespaceServer;
use RefactorCircus\Cortex\Tests\Fixtures\OldTraitServer;
use RefactorCircus\Cortex\Tests\Support\ServerContextTools;

/*
 * AC 6: host servers written before the rename, on the old base class or the
 * old trait name (reached here through the allowlisted fixtures), serve a
 * saved tool override.
 */
beforeEach(function (): void {
    Cache::store('array')->flush();

    app(McpServerRegistry::class)->register('old-namespace', OldNamespaceServer::class);
    app(McpServerRegistry::class)->register('old-trait', OldTraitServer::class);

    app(ToolRegistry::class)->register('echo-mcp-tool', EchoMcpTool::class);
    app(ToolRegistry::class)->register('echo-cortex-tool', EchoCortexTool::class);
});

it('serves a saved tool override and its code tools again once it is cleared', function (string $server, string $class): void {
    // Neither fixture declares tools.
    expect(ServerContextTools::listed($class::tools()))->toBe([]);

    app(UpdateConcreteServerToolsAction::class)->execute($server, UpdateConcreteServerToolsData::from([
        'tools' => ['echo-cortex-tool', 'echo-mcp-tool'],
    ], $server));
    app()->forgetScopedInstances();

    expect(ServerContextTools::listed($class::tools()))->toBe(['echo-cortex-tool', 'echo-mcp-tool']);

    $class::tool(EchoMcpTool::class, ['message' => 'old host'])->assertOk()->assertSee('old host');

    app(ClearConcreteServerToolsAction::class)->execute($server, ClearConcreteServerToolsData::from([], $server));
    app()->forgetScopedInstances();

    expect(ServerContextTools::listed($class::tools()))->toBe([]);
})->with([
    'old base class' => ['old-namespace', OldNamespaceServer::class],
    'old trait name' => ['old-trait', OldTraitServer::class],
]);
