<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\UpdateConcreteServerToolsAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\UpdateConcreteServerToolsData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Tools\ListServerInstructionVersionsTool;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Tests\Fixtures\GroupedToolsServer;

/*
 * Tool-only rows (AC 15): `tools` set, `published_version_id` null and no
 * versions. The update action creates them for a server without instructions,
 * and deleting the instructions of a row with tools leaves one behind. These
 * are the consumers AC 15 names (the Atrium screen is covered with the
 * editor): the server list, the instructions show, the version list and
 * publishing.
 */
beforeEach(function (): void {
    Cache::store('array')->flush();

    app(McpServerRegistry::class)->register('grouped', GroupedToolsServer::class);
});

// The tests take a Closure, so Pest hands each one over without calling it.
dataset('tool-only rows', [
    'saved through the update action' => [function (): void {
        app(UpdateConcreteServerToolsAction::class)->execute('grouped', new UpdateConcreteServerToolsData(['echo-mcp-tool']));
    }],
    'left by deleting the instructions' => [function (): void {
        test()->postJson(route('cortex.servers.instructions.versions.store', 'grouped'), ['content' => 'Gone soon.', 'publish' => true])->assertCreated();
        app(UpdateConcreteServerToolsAction::class)->execute('grouped', new UpdateConcreteServerToolsData(['echo-mcp-tool']));
        test()->deleteJson(route('cortex.servers.instructions.destroy', 'grouped'))->assertSuccessful();
    }],
]);

/**
 * Create the tool-only row, check it is one, then start a new request.
 */
function makeToolOnlyRow(Closure $create): void
{
    $create();

    $instruction = McpInstructionModel::query()->where('server', 'grouped')->sole();

    expect($instruction->tools)->toBe(['echo-mcp-tool'])
        ->and($instruction->published_version_id)->toBeNull()
        ->and($instruction->versions()->count())->toBe(0);

    app()->forgetScopedInstances();
}

it('lists the server with its code instructions and the tool override', function (Closure $create): void {
    makeToolOnlyRow($create);

    $listed = collect($this->getJson(route('cortex.servers.index'))->assertOk()->json('data'))->firstWhere('name', 'grouped');

    expect($listed)->toMatchArray([
        'instructions' => 'Grouped tools server instructions.',
        'tools' => ['echo-mcp-tool'],
        'tools_overridden' => true,
    ]);
})->with('tool-only rows');

it('shows no published instructions and the tool override', function (Closure $create): void {
    makeToolOnlyRow($create);

    $this->getJson(route('cortex.servers.instructions.show', 'grouped'))
        ->assertOk()
        ->assertJsonPath('data.published_version', null)
        ->assertJsonPath('data.published_content', null)
        ->assertJsonPath('data.tools', ['echo-mcp-tool'])
        ->assertJsonPath('data.tools_overridden', true);
})->with('tool-only rows');

it('lists no versions', function (Closure $create): void {
    makeToolOnlyRow($create);

    $this->getJson(route('cortex.servers.instructions.versions.index', 'grouped'))
        ->assertOk()
        ->assertJsonPath('data', []);

    mcpTool(ListServerInstructionVersionsTool::class, ['server' => 'grouped'])
        ->assertOk()
        ->assertStructuredContent(['data' => []]);
})->with('tool-only rows');

it('publishes a version on the row and keeps its tools', function (Closure $create): void {
    makeToolOnlyRow($create);

    $this->postJson(route('cortex.servers.instructions.versions.store', 'grouped'), ['content' => 'Draft.'])->assertCreated();

    $this->postJson(route('cortex.servers.instructions.versions.publish', ['server' => 'grouped', 'version' => 1]))
        ->assertOk()
        ->assertJsonPath('data.published_version', 1)
        ->assertJsonPath('data.published_content', 'Draft.')
        ->assertJsonPath('data.tools', ['echo-mcp-tool'])
        ->assertJsonPath('data.tools_overridden', true);

    expect(McpInstructionModel::query()->count())->toBe(1)
        ->and(McpInstructionModel::query()->sole()->tools)->toBe(['echo-mcp-tool']);
})->with('tool-only rows');
