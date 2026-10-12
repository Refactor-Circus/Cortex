<?php

declare(strict_types=1);

use RefactorCircus\Cortex\Mcp\CortexServer;
use RefactorCircus\Cortex\Mcp\Tools\ListCortexHistoryTool;

it('serves the history route inside the json api group', function (): void {
    expect(route('cortex.history.index', absolute: false))->toBe('/cortex/history');
});

it('answers 404 from the history route while no audit log is installed', function (): void {
    $this->getJson(route('cortex.history.index'))
        ->assertNotFound()
        ->assertJsonPath('message', 'No audit log is installed. Install refactor-circus/keen to record history.');
});

it('declares the history tool on the server', function (): void {
    expect(CortexServer::TOOLS)->toContain(ListCortexHistoryTool::class)
        ->and(app(ListCortexHistoryTool::class)->name())->toBe('list-cortex-history-tool');
});

it('does not offer the history tool while Cortex does not audit', function (): void {
    // No audit log is installed, so tool search leaves the history tool out.
    mcpTool(ListCortexHistoryTool::class)
        ->assertHasErrors(['Tool [list-cortex-history-tool] was not found in the catalog.']);
});
