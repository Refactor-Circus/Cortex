<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\QueryException;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;

it('relates virtual agents to versions and a published version', function () {
    $agent = VirtualAgentModel::factory()->create();
    $version = VirtualAgentVersionModel::factory()->for($agent, 'virtualAgent')->create(['version' => 1]);

    $agent->published_version_id = $version->getKey();
    $agent->save();

    expect($agent->versions)->toHaveCount(1)
        ->and($agent->refresh()->publishedVersion?->getKey())->toBe($version->getKey())
        ->and($version->virtualAgent?->getKey())->toBe($agent->getKey());
});

it('enforces virtual agent version immutability', function () {
    $version = VirtualAgentVersionModel::factory()->create();

    $version->update(['content' => 'changed']);
})->throws(LogicException::class, 'Virtual agent prompt versions are immutable.');

it('enforces one version number per virtual agent', function () {
    $agent = VirtualAgentModel::factory()->create();

    VirtualAgentVersionModel::factory()->for($agent, 'virtualAgent')->create(['version' => 1]);
    VirtualAgentVersionModel::factory()->for($agent, 'virtualAgent')->create(['version' => 1]);
})->throws(QueryException::class);

it('deletes versions when the virtual agent is deleted', function () {
    $agent = VirtualAgentModel::factory()->published()->create();

    $agent->delete();

    expect(VirtualAgentVersionModel::query()->count())->toBe(0);
});

it('casts agent settings and tools to arrays', function () {
    $agent = VirtualAgentModel::factory()->create([
        'settings' => ['temperature' => 0.5],
        'tools' => ['echo'],
    ]);

    expect($agent->refresh())
        ->settings->toBe(['temperature' => 0.5])
        ->tools->toBe(['echo']);
});

it('defaults agent tools and concrete sub-agents to empty arrays', function () {
    $agent = VirtualAgentModel::query()->create(['name' => 'Helper', 'slug' => 'helper']);

    expect($agent->refresh()->tools)->toBe([])
        ->and($agent->concrete_sub_agents)->toBe([]);
});

it('relates agents to sub-agents in both directions', function () {
    $parent = VirtualAgentModel::factory()->create();
    $child = VirtualAgentModel::factory()->create();

    $parent->subAgents()->attach($child);

    expect($parent->subAgents->pluck('id')->all())->toBe([$child->getKey()])
        ->and($child->parentAgents->pluck('id')->all())->toBe([$parent->getKey()]);
});

it('detaches sub-agent links when an agent is deleted', function () {
    $parent = VirtualAgentModel::factory()->create();
    $child = VirtualAgentModel::factory()->create();
    $parent->subAgents()->attach($child);

    $child->delete();

    expect($parent->subAgents()->count())->toBe(0);
});

it('relates mcp instructions to versions and a published version', function () {
    $instruction = McpInstructionModel::factory()->create();
    $version = McpInstructionVersionModel::factory()->for($instruction, 'mcpInstruction')->create(['version' => 1]);

    $instruction->published_version_id = $version->getKey();
    $instruction->save();

    expect($instruction->versions)->toHaveCount(1)
        ->and($instruction->refresh()->publishedVersion?->getKey())->toBe($version->getKey())
        ->and($version->mcpInstruction?->getKey())->toBe($instruction->getKey());
});

it('enforces mcp instruction version immutability', function () {
    $version = McpInstructionVersionModel::factory()->create();

    $version->update(['content' => 'changed']);
})->throws(LogicException::class, 'MCP server instruction versions are immutable.');

it('enforces one version number per mcp instruction', function () {
    $instruction = McpInstructionModel::factory()->create();

    McpInstructionVersionModel::factory()->for($instruction, 'mcpInstruction')->create(['version' => 1]);
    McpInstructionVersionModel::factory()->for($instruction, 'mcpInstruction')->create(['version' => 1]);
})->throws(QueryException::class);

it('deletes versions when the mcp instruction is deleted', function () {
    $instruction = McpInstructionModel::factory()->create();
    McpInstructionVersionModel::factory()->for($instruction, 'mcpInstruction')->create(['version' => 1]);

    $instruction->delete();

    expect(McpInstructionVersionModel::query()->count())->toBe(0);
});

it('relates concrete agent overrides to versions and a published version', function () {
    $override = ConcreteAgentOverrideModel::factory()->create(['tools' => ['echo']]);
    $version = ConcreteAgentOverrideVersionModel::factory()->for($override, 'concreteAgentOverride')->create(['version' => 1]);

    $override->published_version_id = $version->getKey();
    $override->save();

    expect($override->refresh()->tools)->toBe(['echo'])
        ->and($override->publishedVersion?->getKey())->toBe($version->getKey())
        ->and($version->concreteAgentOverride?->getKey())->toBe($override->getKey());
});

it('enforces concrete agent override version immutability', function () {
    $version = ConcreteAgentOverrideVersionModel::factory()->create();

    $version->update(['content' => 'changed']);
})->throws(LogicException::class, 'Concrete agent prompt versions are immutable.');

it('deletes versions when the concrete agent override is deleted', function () {
    $override = ConcreteAgentOverrideModel::factory()->create();
    ConcreteAgentOverrideVersionModel::factory()->for($override, 'concreteAgentOverride')->create(['version' => 1]);

    $override->delete();

    expect(ConcreteAgentOverrideVersionModel::query()->count())->toBe(0);
});

it('keeps the class names the models were stored under before they moved', function (string $old, string $model): void {
    expect(Relation::getMorphedModel($old))->toBe($model)
        ->and((new $model)->getMorphClass())->toBe($old);
})->with([
    ['RefactorCircus\\Cortex\\Models\\VirtualAgent', VirtualAgentModel::class],
    ['RefactorCircus\\Cortex\\Models\\VirtualAgentVersion', VirtualAgentVersionModel::class],
    ['RefactorCircus\\Cortex\\Models\\ConcreteAgentOverride', ConcreteAgentOverrideModel::class],
    ['RefactorCircus\\Cortex\\Models\\ConcreteAgentOverrideVersion', ConcreteAgentOverrideVersionModel::class],
    ['RefactorCircus\\Cortex\\Models\\ToolDescription', ToolDescriptionModel::class],
    ['RefactorCircus\\Cortex\\Models\\ToolDescriptionVersion', ToolDescriptionVersionModel::class],
    ['RefactorCircus\\Cortex\\Models\\McpInstruction', McpInstructionModel::class],
    ['RefactorCircus\\Cortex\\Models\\McpInstructionVersion', McpInstructionVersionModel::class],
]);

it('resolves a polymorphic value stored under an old class name', function (): void {
    $agent = VirtualAgentModel::factory()->create();

    $class = Relation::getMorphedModel('RefactorCircus\\Cortex\\Models\\VirtualAgent');

    expect($class::query()->find($agent->getKey())?->is($agent))->toBeTrue();
});
