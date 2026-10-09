<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Cortex\CortexServiceProvider;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Policies\ConcreteAgentOverridePolicy;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Policies\ConcreteAgentOverrideVersionPolicy;
use RefactorCircus\Cortex\Domains\McpServer\Mcp\Tools\CreateServerInstructionVersionTool;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Domains\McpServer\Policies\McpInstructionPolicy;
use RefactorCircus\Cortex\Domains\McpServer\Policies\McpInstructionVersionPolicy;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use RefactorCircus\Cortex\Domains\Tool\Models\ToolDescriptionVersionModel;
use RefactorCircus\Cortex\Domains\Tool\Policies\ToolDescriptionPolicy;
use RefactorCircus\Cortex\Domains\Tool\Policies\ToolDescriptionVersionPolicy;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\CreateVirtualAgentVersionTool;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\PublishVirtualAgentVersionTool;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\RunVirtualAgentTool;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\ShowVirtualAgentVersionTool;
use RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Tools\UpdateVirtualAgentTool;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Policies\VirtualAgentPolicy;
use RefactorCircus\Cortex\Domains\VirtualAgent\Policies\VirtualAgentVersionPolicy;
use RefactorCircus\Cortex\Domains\VirtualAgent\Support\DbAgent;
use RefactorCircus\Cortex\Tests\Fixtures\EchoTool;
use RefactorCircus\Cortex\Tests\Fixtures\Policies\FrozenToolDescriptionPolicy;
use RefactorCircus\Cortex\Tests\Fixtures\Policies\NoRunAgentPolicy;
use RefactorCircus\Cortex\Tests\Fixtures\Policies\ReadOnlyVirtualAgentPolicy;
use RefactorCircus\Cortex\Tests\Fixtures\Policies\SignedInMcpInstructionPolicy;

/**
 * Register the policies again after a test changes `cortex.policies`, as the
 * provider does on boot.
 *
 * @param  array<class-string, class-string>  $policies
 */
function usePolicies(array $policies): void
{
    foreach ($policies as $model => $policy) {
        config()->set('cortex.policies.'.$model, $policy);
    }

    $provider = app()->getProvider(CortexServiceProvider::class);

    (fn () => $this->registerPolicies())->call($provider);
}

it('registers the policies from the config', function (): void {
    expect(Gate::getPolicyFor(VirtualAgentModel::class))->toBeInstanceOf(VirtualAgentPolicy::class)
        ->and(Gate::getPolicyFor(VirtualAgentVersionModel::class))->toBeInstanceOf(VirtualAgentVersionPolicy::class)
        ->and(Gate::getPolicyFor(ConcreteAgentOverrideModel::class))->toBeInstanceOf(ConcreteAgentOverridePolicy::class)
        ->and(Gate::getPolicyFor(ConcreteAgentOverrideVersionModel::class))->toBeInstanceOf(ConcreteAgentOverrideVersionPolicy::class)
        ->and(Gate::getPolicyFor(ToolDescriptionModel::class))->toBeInstanceOf(ToolDescriptionPolicy::class)
        ->and(Gate::getPolicyFor(ToolDescriptionVersionModel::class))->toBeInstanceOf(ToolDescriptionVersionPolicy::class)
        ->and(Gate::getPolicyFor(McpInstructionModel::class))->toBeInstanceOf(McpInstructionPolicy::class)
        ->and(Gate::getPolicyFor(McpInstructionVersionModel::class))->toBeInstanceOf(McpInstructionVersionPolicy::class);
});

it('allows guests and signed-in users everything by default, since nothing has an owner', function (): void {
    $agent = VirtualAgentModel::factory()->published()->create();
    $version = $agent->versions()->firstOrFail();
    $override = ConcreteAgentOverrideModel::factory()->create();

    foreach ([null, new GenericUser(['id' => 1])] as $user) {
        $gate = Gate::forUser($user);

        expect($gate->allows('viewAny', VirtualAgentModel::class))->toBeTrue()
            ->and($gate->allows('create', VirtualAgentModel::class))->toBeTrue()
            ->and($gate->allows('view', $agent))->toBeTrue()
            ->and($gate->allows('update', $agent))->toBeTrue()
            ->and($gate->allows('delete', $agent))->toBeTrue()
            ->and($gate->allows('run', $agent))->toBeTrue()
            ->and($gate->allows('viewAny', [VirtualAgentVersionModel::class, $agent]))->toBeTrue()
            ->and($gate->allows('create', [VirtualAgentVersionModel::class, $agent]))->toBeTrue()
            ->and($gate->allows('view', $version))->toBeTrue()
            ->and($gate->allows('publish', $version))->toBeTrue()
            ->and($gate->allows('viewAny', ConcreteAgentOverrideModel::class))->toBeTrue()
            ->and($gate->allows('update', $override))->toBeTrue()
            ->and($gate->allows('run', $override))->toBeTrue()
            ->and($gate->allows('create', [ConcreteAgentOverrideVersionModel::class, $override]))->toBeTrue();
    }
});

it('checks versions against their parent through the Gate', function (): void {
    usePolicies([VirtualAgentModel::class => ReadOnlyVirtualAgentPolicy::class]);

    $agent = VirtualAgentModel::factory()->published()->create();
    $version = $agent->versions()->firstOrFail();

    expect(Gate::allows('viewAny', [VirtualAgentVersionModel::class, $agent]))->toBeTrue()
        ->and(Gate::allows('view', $version))->toBeTrue()
        ->and(Gate::allows('create', [VirtualAgentVersionModel::class, $agent]))->toBeFalse()
        ->and(Gate::allows('publish', $version))->toBeFalse();
});

it('uses a virtual agent policy swapped in the config, for agents and their versions', function (): void {
    usePolicies([VirtualAgentModel::class => ReadOnlyVirtualAgentPolicy::class]);

    VirtualAgentModel::factory()->published()->create(['slug' => 'support']);

    $this->getJson(route('cortex.virtual-agents.show', 'support'))->assertOk();
    $this->getJson(route('cortex.virtual-agents.versions.show', ['support', 1]))->assertOk();
    $this->patchJson(route('cortex.virtual-agents.update', 'support'), ['name' => 'Renamed'])->assertForbidden();
    $this->postJson(route('cortex.virtual-agents.versions.store', 'support'), ['content' => 'New.'])->assertForbidden();
    $this->postJson(route('cortex.virtual-agents.versions.publish', ['support', 1]))->assertForbidden();

    mcpTool(ShowVirtualAgentVersionTool::class, ['slug' => 'support', 'version' => 1])->assertOk();
    mcpTool(UpdateVirtualAgentTool::class, ['slug' => 'support', 'name' => 'Renamed'])->assertHasErrors(['Unauthorized.']);
    mcpTool(CreateVirtualAgentVersionTool::class, ['slug' => 'support', 'content' => 'New.'])->assertHasErrors(['Unauthorized.']);
    mcpTool(PublishVirtualAgentVersionTool::class, ['slug' => 'support', 'version' => 1])->assertHasErrors(['Unauthorized.']);

    expect(VirtualAgentVersionModel::query()->count())->toBe(1)
        ->and(VirtualAgentModel::query()->value('name'))->not->toBe('Renamed');
});

it('checks the custom run ability on virtual agents over HTTP and MCP', function (): void {
    usePolicies([VirtualAgentModel::class => NoRunAgentPolicy::class]);

    DbAgent::fake(['Hello.']);
    VirtualAgentModel::factory()->published()->create(['slug' => 'helper']);

    $this->getJson(route('cortex.virtual-agents.show', 'helper'))->assertOk();
    $this->postJson(route('cortex.virtual-agents.run', 'helper'), ['input' => 'Hi'])->assertForbidden();

    mcpTool(RunVirtualAgentTool::class, ['slug' => 'helper', 'input' => 'Hi'])->assertHasErrors(['Unauthorized.']);

    DbAgent::assertNeverPrompted();
});

it('checks the first version of an override against the override policy', function (): void {
    usePolicies([ToolDescriptionModel::class => FrozenToolDescriptionPolicy::class]);

    app(ToolRegistry::class)->register('echo', EchoTool::class);

    $this->postJson(route('cortex.tools.description.versions.store', 'echo'), ['content' => 'Echo.'])
        ->assertForbidden();

    expect(ToolDescriptionModel::query()->count())->toBe(0);
});

it('passes the signed-in user to the policy over HTTP and MCP', function (): void {
    usePolicies([McpInstructionModel::class => SignedInMcpInstructionPolicy::class]);

    $this->postJson(route('cortex.servers.instructions.versions.store', 'cortex'), ['content' => 'Guest.'])
        ->assertForbidden();
    mcpTool(CreateServerInstructionVersionTool::class, ['server' => 'cortex', 'content' => 'Guest.'])
        ->assertHasErrors(['Unauthorized.']);

    $this->actingAs(new GenericUser(['id' => 1]));

    $this->postJson(route('cortex.servers.instructions.versions.store', 'cortex'), ['content' => 'Member.'])
        ->assertCreated();
    mcpTool(CreateServerInstructionVersionTool::class, ['server' => 'cortex', 'content' => 'Member again.'])
        ->assertOk();

    expect(McpInstructionVersionModel::query()->count())->toBe(2);
});
