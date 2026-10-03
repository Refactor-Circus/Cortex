<?php

declare(strict_types=1);

use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;

it('creates a version without publishing it by default', function () {
    VirtualAgentModel::factory()->published('v1')->create(['slug' => 'helper']);

    $this->postJson(route('cortex.virtual-agents.versions.store', 'helper'), ['content' => 'v2'])
        ->assertCreated()
        ->assertJsonPath('data.version', 2)
        ->assertJsonPath('data.content', 'v2');

    $this->getJson(route('cortex.virtual-agents.show', 'helper'))
        ->assertJsonPath('data.published_version', 1);
});

it('lists versions newest first', function () {
    VirtualAgentModel::factory()->published('v1')->create(['slug' => 'helper']);
    $this->postJson(route('cortex.virtual-agents.versions.store', 'helper'), ['content' => 'v2']);

    $this->getJson(route('cortex.virtual-agents.versions.index', 'helper'))
        ->assertOk()
        ->assertJsonPath('data.0.version', 2)
        ->assertJsonPath('data.1.version', 1);
});

it('shows a version', function () {
    VirtualAgentModel::factory()->published('v1')->create(['slug' => 'helper']);

    $this->getJson(route('cortex.virtual-agents.versions.show', ['helper', 1]))
        ->assertOk()
        ->assertJsonPath('data.content', 'v1');

    $this->getJson(route('cortex.virtual-agents.versions.show', ['helper', 9]))->assertNotFound();
});

it('publishes a version', function () {
    VirtualAgentModel::factory()->published('v1')->create(['slug' => 'helper']);
    $this->postJson(route('cortex.virtual-agents.versions.store', 'helper'), ['content' => 'v2']);

    $this->postJson(route('cortex.virtual-agents.versions.publish', ['helper', 2]))
        ->assertOk()
        ->assertJsonPath('data.published_version', 2)
        ->assertJsonPath('data.instructions', 'v2');
});

it('validates version content', function () {
    VirtualAgentModel::factory()->create(['slug' => 'helper']);

    $this->postJson(route('cortex.virtual-agents.versions.store', 'helper'), [])
        ->assertJsonValidationErrors(['content']);
});
