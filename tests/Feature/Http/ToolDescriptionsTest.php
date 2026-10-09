<?php

declare(strict_types=1);

use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Tests\Fixtures\EchoTool;

beforeEach(function () {
    app(ToolRegistry::class)->register('echo', EchoTool::class);
});

it('creates a description version for a registered tool', function () {
    $this->postJson(route('cortex.tools.description.versions.store', ['tool' => 'echo']), [
        'content' => 'Echo, but described better.',
    ])
        ->assertCreated()
        ->assertJsonPath('data.version', 1)
        ->assertJsonPath('data.content', 'Echo, but described better.');

    expect(ToolDescriptionModel::query()->where('tool', 'echo')->exists())->toBeTrue();
});

it('rejects description versions for unregistered tools', function () {
    $this->postJson(route('cortex.tools.description.versions.store', ['tool' => 'missing']), [
        'content' => 'Nope.',
    ])->assertNotFound();
});

it('increments versions and publishes on demand', function () {
    $store = route('cortex.tools.description.versions.store', ['tool' => 'echo']);

    $this->postJson($store, ['content' => 'v1'])->assertCreated();
    $this->postJson($store, ['content' => 'v2', 'publish' => true])
        ->assertCreated()
        ->assertJsonPath('data.version', 2);

    $this->getJson(route('cortex.tools.description.show', ['tool' => 'echo']))
        ->assertOk()
        ->assertJsonPath('data.published_version', 2)
        ->assertJsonPath('data.published_content', 'v2');
});

it('publishes an existing version explicitly', function () {
    $store = route('cortex.tools.description.versions.store', ['tool' => 'echo']);
    $this->postJson($store, ['content' => 'v1'])->assertCreated();
    $this->postJson($store, ['content' => 'v2'])->assertCreated();

    $this->postJson(route('cortex.tools.description.versions.publish', ['tool' => 'echo', 'version' => 1]))
        ->assertOk()
        ->assertJsonPath('data.published_version', 1)
        ->assertJsonPath('data.published_content', 'v1');
});

it('lists versions newest first', function () {
    $store = route('cortex.tools.description.versions.store', ['tool' => 'echo']);
    $this->postJson($store, ['content' => 'v1']);
    $this->postJson($store, ['content' => 'v2']);

    $this->getJson(route('cortex.tools.description.versions.index', ['tool' => 'echo']))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.version', 2)
        ->assertJsonPath('data.1.version', 1);
});

it('deletes an override and its versions', function () {
    $store = route('cortex.tools.description.versions.store', ['tool' => 'echo']);
    $this->postJson($store, ['content' => 'v1', 'publish' => true]);

    $this->deleteJson(route('cortex.tools.description.destroy', ['tool' => 'echo']))
        ->assertNoContent();

    expect(ToolDescriptionModel::query()->count())->toBe(0)
        ->and(ToolDescriptionVersionModel::query()->count())->toBe(0);
});

it('keeps versions immutable', function () {
    $version = ToolDescriptionVersionModel::factory()->create();

    $version->update(['content' => 'rewritten']);
})->throws(LogicException::class, 'immutable');

it('serves the published override through the tools endpoint and registry', function () {
    $this->postJson(route('cortex.tools.description.versions.store', ['tool' => 'echo']), [
        'content' => 'Published override.',
        'publish' => true,
    ])->assertCreated();

    $this->getJson(route('cortex.tools.index'))
        ->assertOk()
        ->assertJsonPath('data.0.description', 'Published override.');

    expect((string) app(ToolRegistry::class)->get('echo')->description())
        ->toBe('Published override.');
});

it('falls back to the code-declared description without a published version', function () {
    $this->postJson(route('cortex.tools.description.versions.store', ['tool' => 'echo']), [
        'content' => 'Draft only.',
    ])->assertCreated();

    expect((string) app(ToolRegistry::class)->get('echo')->description())
        ->toBe('Echoes back the given message.');
});
