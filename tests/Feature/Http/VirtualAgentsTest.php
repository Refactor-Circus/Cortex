<?php

declare(strict_types=1);

use JayI\Cortex\Agents\AgentRegistry;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Tests\Fixtures\EchoAgent;
use JayI\Cortex\Tests\Fixtures\EchoTool;
use JayI\Cortex\Tools\ToolRegistry;

it('lists virtual agents', function () {
    VirtualAgent::factory()->published()->create(['slug' => 'helper']);

    $this->getJson(route('cortex.virtual-agents.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'helper')
        ->assertJsonPath('data.0.published_version', 1);
});

it('creates a virtual agent with its prompt, tools, and sub-agents', function () {
    app(ToolRegistry::class)->register('echo', EchoTool::class);
    app(AgentRegistry::class)->register('echo-agent', EchoAgent::class);
    VirtualAgent::factory()->create(['slug' => 'researcher']);

    $this->postJson(route('cortex.virtual-agents.store'), [
        'name' => 'Coordinator',
        'slug' => 'coordinator',
        'instructions' => 'Coordinate.',
        'provider' => 'anthropic',
        'model' => 'claude-sonnet-5',
        'settings' => ['temperature' => 0.3],
        'tools' => ['echo'],
        'sub_agents' => ['researcher'],
        'concrete_sub_agents' => ['echo-agent'],
    ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'coordinator')
        ->assertJsonPath('data.instructions', 'Coordinate.')
        ->assertJsonPath('data.published_version', 1)
        ->assertJsonPath('data.tools', ['echo'])
        ->assertJsonPath('data.sub_agents', ['researcher'])
        ->assertJsonPath('data.concrete_sub_agents', ['echo-agent']);
});

it('requires instructions', function () {
    $this->postJson(route('cortex.virtual-agents.store'), [
        'name' => 'Broken',
        'slug' => 'broken',
    ])->assertJsonValidationErrors(['instructions']);
});

it('rejects unregistered tools and concrete sub-agents', function () {
    $this->postJson(route('cortex.virtual-agents.store'), [
        'name' => 'Broken',
        'slug' => 'broken',
        'instructions' => 'Nope.',
        'tools' => ['missing'],
        'concrete_sub_agents' => ['missing'],
    ])->assertJsonValidationErrors(['tools.0', 'concrete_sub_agents.0']);
});

it('shows a virtual agent', function () {
    VirtualAgent::factory()->published('Help.')->create(['slug' => 'helper']);

    $this->getJson(route('cortex.virtual-agents.show', 'helper'))
        ->assertOk()
        ->assertJsonPath('data.slug', 'helper')
        ->assertJsonPath('data.instructions', 'Help.')
        ->assertJsonPath('data.sub_agents', []);
});

it('returns 404 for unknown virtual agents', function () {
    $this->getJson(route('cortex.virtual-agents.show', 'missing'))->assertNotFound();
});

it('updates a virtual agent with sync semantics and versions changed instructions', function () {
    app(ToolRegistry::class)->register('echo', EchoTool::class);
    $agent = VirtualAgent::factory()->published('v1')->create(['slug' => 'helper', 'tools' => ['old']]);
    $agent->subAgents()->attach(VirtualAgent::factory()->create());

    $this->patchJson(route('cortex.virtual-agents.update', 'helper'), [
        'instructions' => 'v2',
        'tools' => ['echo'],
        'sub_agents' => [],
    ])
        ->assertOk()
        ->assertJsonPath('data.instructions', 'v2')
        ->assertJsonPath('data.published_version', 2)
        ->assertJsonPath('data.tools', ['echo'])
        ->assertJsonPath('data.sub_agents', []);
});

it('rejects circular sub-agent updates', function () {
    $a = VirtualAgent::factory()->create(['slug' => 'agent-a']);
    VirtualAgent::factory()->create(['slug' => 'agent-b']);
    $a->subAgents()->attach(VirtualAgent::query()->where('slug', 'agent-b')->firstOrFail());

    $this->patchJson(route('cortex.virtual-agents.update', 'agent-b'), [
        'sub_agents' => ['agent-a'],
    ])->assertJsonValidationErrors(['sub_agents']);
});

it('deletes a virtual agent', function () {
    VirtualAgent::factory()->create(['slug' => 'helper']);

    $this->deleteJson(route('cortex.virtual-agents.destroy', 'helper'))->assertNoContent();

    expect(VirtualAgent::query()->count())->toBe(0);
});
