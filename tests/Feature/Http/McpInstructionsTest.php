<?php

declare(strict_types=1);

use JayI\Cortex\Domains\McpServer\Models\McpInstructionModel;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionVersionModel;
use JayI\Cortex\Domains\McpServer\Services\McpServerRegistry;
use JayI\Cortex\Tests\Fixtures\EchoServer;
use Laravel\Mcp\Server\Transport\FakeTransporter;

beforeEach(function () {
    app(McpServerRegistry::class)->register('echo', EchoServer::class);
});

it('creates an instruction version for a registered server', function () {
    $this->postJson(route('cortex.servers.instructions.versions.store', ['server' => 'echo']), [
        'content' => 'Echo, but instructed better.',
    ])
        ->assertCreated()
        ->assertJsonPath('data.version', 1)
        ->assertJsonPath('data.content', 'Echo, but instructed better.');

    expect(McpInstructionModel::query()->where('server', 'echo')->exists())->toBeTrue();
});

it('rejects instruction versions for unregistered servers', function () {
    $this->postJson(route('cortex.servers.instructions.versions.store', ['server' => 'missing']), [
        'content' => 'Nope.',
    ])->assertNotFound();
});

it('increments versions and publishes on demand', function () {
    $store = route('cortex.servers.instructions.versions.store', ['server' => 'echo']);

    $this->postJson($store, ['content' => 'v1'])->assertCreated();
    $this->postJson($store, ['content' => 'v2', 'publish' => true])
        ->assertCreated()
        ->assertJsonPath('data.version', 2);

    $this->getJson(route('cortex.servers.instructions.show', ['server' => 'echo']))
        ->assertOk()
        ->assertJsonPath('data.published_version', 2)
        ->assertJsonPath('data.published_content', 'v2');
});

it('publishes an existing version explicitly', function () {
    $store = route('cortex.servers.instructions.versions.store', ['server' => 'echo']);
    $this->postJson($store, ['content' => 'v1'])->assertCreated();
    $this->postJson($store, ['content' => 'v2'])->assertCreated();

    $this->postJson(route('cortex.servers.instructions.versions.publish', ['server' => 'echo', 'version' => 1]))
        ->assertOk()
        ->assertJsonPath('data.published_version', 1)
        ->assertJsonPath('data.published_content', 'v1');
});

it('lists versions newest first', function () {
    $store = route('cortex.servers.instructions.versions.store', ['server' => 'echo']);
    $this->postJson($store, ['content' => 'v1']);
    $this->postJson($store, ['content' => 'v2']);

    $this->getJson(route('cortex.servers.instructions.versions.index', ['server' => 'echo']))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.version', 2)
        ->assertJsonPath('data.1.version', 1);
});

it('deletes an override and its versions', function () {
    $store = route('cortex.servers.instructions.versions.store', ['server' => 'echo']);
    $this->postJson($store, ['content' => 'v1', 'publish' => true]);

    $this->deleteJson(route('cortex.servers.instructions.destroy', ['server' => 'echo']))
        ->assertNoContent();

    expect(McpInstructionModel::query()->count())->toBe(0)
        ->and(McpInstructionVersionModel::query()->count())->toBe(0);
});

it('keeps versions immutable', function () {
    $version = McpInstructionVersionModel::factory()->create();

    $version->update(['content' => 'rewritten']);
})->throws(LogicException::class, 'immutable');

it('serves the published override through the servers endpoint and context', function () {
    $this->postJson(route('cortex.servers.instructions.versions.store', ['server' => 'echo']), [
        'content' => 'Published override.',
        'publish' => true,
    ])->assertCreated();

    $this->getJson(route('cortex.servers.index'))
        ->assertOk()
        ->assertJsonPath('data.1.name', 'echo')
        ->assertJsonPath('data.1.instructions', 'Published override.');

    expect((new EchoServer(new FakeTransporter))->createContext()->instructions)
        ->toBe('Published override.');
});

it('falls back to the code-declared instructions without a published version', function () {
    $this->postJson(route('cortex.servers.instructions.versions.store', ['server' => 'echo']), [
        'content' => 'Draft only.',
    ])->assertCreated();

    expect((new EchoServer(new FakeTransporter))->createContext()->instructions)
        ->toBe('Code-declared echo server instructions.');
});
