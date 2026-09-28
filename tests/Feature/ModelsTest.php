<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use JayI\Cortex\Models\ConcreteAgentOverride;
use JayI\Cortex\Models\ConcreteAgentOverrideVersion;
use JayI\Cortex\Models\McpInstruction;
use JayI\Cortex\Models\McpInstructionVersion;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Models\VirtualAgentVersion;

it('relates virtual agents to versions and a published version', function () {
    $agent = VirtualAgent::factory()->create();
    $version = VirtualAgentVersion::factory()->for($agent, 'virtualAgent')->create(['version' => 1]);

    $agent->published_version_id = $version->getKey();
    $agent->save();

    expect($agent->versions)->toHaveCount(1)
        ->and($agent->refresh()->publishedVersion?->getKey())->toBe($version->getKey())
        ->and($version->virtualAgent?->getKey())->toBe($agent->getKey());
});

it('enforces virtual agent version immutability', function () {
    $version = VirtualAgentVersion::factory()->create();

    $version->update(['content' => 'changed']);
})->throws(LogicException::class, 'Virtual agent prompt versions are immutable.');

it('enforces one version number per virtual agent', function () {
    $agent = VirtualAgent::factory()->create();

    VirtualAgentVersion::factory()->for($agent, 'virtualAgent')->create(['version' => 1]);
    VirtualAgentVersion::factory()->for($agent, 'virtualAgent')->create(['version' => 1]);
})->throws(QueryException::class);

it('deletes versions when the virtual agent is deleted', function () {
    $agent = VirtualAgent::factory()->published()->create();

    $agent->delete();

    expect(VirtualAgentVersion::query()->count())->toBe(0);
});

it('casts agent settings and tools to arrays', function () {
    $agent = VirtualAgent::factory()->create([
        'settings' => ['temperature' => 0.5],
        'tools' => ['echo'],
    ]);

    expect($agent->refresh())
        ->settings->toBe(['temperature' => 0.5])
        ->tools->toBe(['echo']);
});

it('defaults agent tools and concrete sub-agents to empty arrays', function () {
    $agent = VirtualAgent::query()->create(['name' => 'Helper', 'slug' => 'helper']);

    expect($agent->refresh()->tools)->toBe([])
        ->and($agent->concrete_sub_agents)->toBe([]);
});

it('relates agents to sub-agents in both directions', function () {
    $parent = VirtualAgent::factory()->create();
    $child = VirtualAgent::factory()->create();

    $parent->subAgents()->attach($child);

    expect($parent->subAgents->pluck('id')->all())->toBe([$child->getKey()])
        ->and($child->parentAgents->pluck('id')->all())->toBe([$parent->getKey()]);
});

it('detaches sub-agent links when an agent is deleted', function () {
    $parent = VirtualAgent::factory()->create();
    $child = VirtualAgent::factory()->create();
    $parent->subAgents()->attach($child);

    $child->delete();

    expect($parent->subAgents()->count())->toBe(0);
});

it('relates mcp instructions to versions and a published version', function () {
    $instruction = McpInstruction::factory()->create();
    $version = McpInstructionVersion::factory()->for($instruction, 'mcpInstruction')->create(['version' => 1]);

    $instruction->published_version_id = $version->getKey();
    $instruction->save();

    expect($instruction->versions)->toHaveCount(1)
        ->and($instruction->refresh()->publishedVersion?->getKey())->toBe($version->getKey())
        ->and($version->mcpInstruction?->getKey())->toBe($instruction->getKey());
});

it('enforces mcp instruction version immutability', function () {
    $version = McpInstructionVersion::factory()->create();

    $version->update(['content' => 'changed']);
})->throws(LogicException::class, 'MCP server instruction versions are immutable.');

it('enforces one version number per mcp instruction', function () {
    $instruction = McpInstruction::factory()->create();

    McpInstructionVersion::factory()->for($instruction, 'mcpInstruction')->create(['version' => 1]);
    McpInstructionVersion::factory()->for($instruction, 'mcpInstruction')->create(['version' => 1]);
})->throws(QueryException::class);

it('deletes versions when the mcp instruction is deleted', function () {
    $instruction = McpInstruction::factory()->create();
    McpInstructionVersion::factory()->for($instruction, 'mcpInstruction')->create(['version' => 1]);

    $instruction->delete();

    expect(McpInstructionVersion::query()->count())->toBe(0);
});

it('relates concrete agent overrides to versions and a published version', function () {
    $override = ConcreteAgentOverride::factory()->create(['tools' => ['echo']]);
    $version = ConcreteAgentOverrideVersion::factory()->for($override, 'concreteAgentOverride')->create(['version' => 1]);

    $override->published_version_id = $version->getKey();
    $override->save();

    expect($override->refresh()->tools)->toBe(['echo'])
        ->and($override->publishedVersion?->getKey())->toBe($version->getKey())
        ->and($version->concreteAgentOverride?->getKey())->toBe($override->getKey());
});

it('enforces concrete agent override version immutability', function () {
    $version = ConcreteAgentOverrideVersion::factory()->create();

    $version->update(['content' => 'changed']);
})->throws(LogicException::class, 'Concrete agent prompt versions are immutable.');

it('deletes versions when the concrete agent override is deleted', function () {
    $override = ConcreteAgentOverride::factory()->create();
    ConcreteAgentOverrideVersion::factory()->for($override, 'concreteAgentOverride')->create(['version' => 1]);

    $override->delete();

    expect(ConcreteAgentOverrideVersion::query()->count())->toBe(0);
});
