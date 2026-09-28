<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use JayI\Cortex\CortexServiceProvider;
use JayI\Cortex\Mcp\Tools\CreateServerInstructionVersionTool;
use JayI\Cortex\Mcp\Tools\CreateVirtualAgentVersionTool;
use JayI\Cortex\Mcp\Tools\PublishVirtualAgentVersionTool;
use JayI\Cortex\Mcp\Tools\RunVirtualAgentTool;
use JayI\Cortex\Mcp\Tools\ShowVirtualAgentVersionTool;
use JayI\Cortex\Mcp\Tools\UpdateVirtualAgentTool;
use JayI\Cortex\Models\ConcreteAgentOverride;
use JayI\Cortex\Models\ConcreteAgentOverrideVersion;
use JayI\Cortex\Models\McpInstruction;
use JayI\Cortex\Models\McpInstructionVersion;
use JayI\Cortex\Models\ToolDescription;
use JayI\Cortex\Models\ToolDescriptionVersion;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Models\VirtualAgentVersion;
use JayI\Cortex\Policies\ConcreteAgentOverridePolicy;
use JayI\Cortex\Policies\ConcreteAgentOverrideVersionPolicy;
use JayI\Cortex\Policies\McpInstructionPolicy;
use JayI\Cortex\Policies\McpInstructionVersionPolicy;
use JayI\Cortex\Policies\ToolDescriptionPolicy;
use JayI\Cortex\Policies\ToolDescriptionVersionPolicy;
use JayI\Cortex\Policies\VirtualAgentPolicy;
use JayI\Cortex\Policies\VirtualAgentVersionPolicy;
use JayI\Cortex\Runtime\DbAgent;
use JayI\Cortex\Tests\Fixtures\EchoTool;
use JayI\Cortex\Tests\Fixtures\Policies\FrozenToolDescriptionPolicy;
use JayI\Cortex\Tests\Fixtures\Policies\NoRunAgentPolicy;
use JayI\Cortex\Tests\Fixtures\Policies\ReadOnlyVirtualAgentPolicy;
use JayI\Cortex\Tests\Fixtures\Policies\SignedInMcpInstructionPolicy;
use JayI\Cortex\Tools\ToolRegistry;

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
    expect(Gate::getPolicyFor(VirtualAgent::class))->toBeInstanceOf(VirtualAgentPolicy::class)
        ->and(Gate::getPolicyFor(VirtualAgentVersion::class))->toBeInstanceOf(VirtualAgentVersionPolicy::class)
        ->and(Gate::getPolicyFor(ConcreteAgentOverride::class))->toBeInstanceOf(ConcreteAgentOverridePolicy::class)
        ->and(Gate::getPolicyFor(ConcreteAgentOverrideVersion::class))->toBeInstanceOf(ConcreteAgentOverrideVersionPolicy::class)
        ->and(Gate::getPolicyFor(ToolDescription::class))->toBeInstanceOf(ToolDescriptionPolicy::class)
        ->and(Gate::getPolicyFor(ToolDescriptionVersion::class))->toBeInstanceOf(ToolDescriptionVersionPolicy::class)
        ->and(Gate::getPolicyFor(McpInstruction::class))->toBeInstanceOf(McpInstructionPolicy::class)
        ->and(Gate::getPolicyFor(McpInstructionVersion::class))->toBeInstanceOf(McpInstructionVersionPolicy::class);
});

it('allows guests and signed-in users everything by default, since nothing has an owner', function (): void {
    $agent = VirtualAgent::factory()->published()->create();
    $version = $agent->versions()->firstOrFail();
    $override = ConcreteAgentOverride::factory()->create();

    foreach ([null, new GenericUser(['id' => 1])] as $user) {
        $gate = Gate::forUser($user);

        expect($gate->allows('viewAny', VirtualAgent::class))->toBeTrue()
            ->and($gate->allows('create', VirtualAgent::class))->toBeTrue()
            ->and($gate->allows('view', $agent))->toBeTrue()
            ->and($gate->allows('update', $agent))->toBeTrue()
            ->and($gate->allows('delete', $agent))->toBeTrue()
            ->and($gate->allows('run', $agent))->toBeTrue()
            ->and($gate->allows('viewAny', [VirtualAgentVersion::class, $agent]))->toBeTrue()
            ->and($gate->allows('create', [VirtualAgentVersion::class, $agent]))->toBeTrue()
            ->and($gate->allows('view', $version))->toBeTrue()
            ->and($gate->allows('publish', $version))->toBeTrue()
            ->and($gate->allows('viewAny', ConcreteAgentOverride::class))->toBeTrue()
            ->and($gate->allows('update', $override))->toBeTrue()
            ->and($gate->allows('run', $override))->toBeTrue()
            ->and($gate->allows('create', [ConcreteAgentOverrideVersion::class, $override]))->toBeTrue();
    }
});

it('checks versions against their parent through the Gate', function (): void {
    usePolicies([VirtualAgent::class => ReadOnlyVirtualAgentPolicy::class]);

    $agent = VirtualAgent::factory()->published()->create();
    $version = $agent->versions()->firstOrFail();

    expect(Gate::allows('viewAny', [VirtualAgentVersion::class, $agent]))->toBeTrue()
        ->and(Gate::allows('view', $version))->toBeTrue()
        ->and(Gate::allows('create', [VirtualAgentVersion::class, $agent]))->toBeFalse()
        ->and(Gate::allows('publish', $version))->toBeFalse();
});

it('uses a virtual agent policy swapped in the config, for agents and their versions', function (): void {
    usePolicies([VirtualAgent::class => ReadOnlyVirtualAgentPolicy::class]);

    VirtualAgent::factory()->published()->create(['slug' => 'support']);

    $this->getJson(route('cortex.virtual-agents.show', 'support'))->assertOk();
    $this->getJson(route('cortex.virtual-agents.versions.show', ['support', 1]))->assertOk();
    $this->patchJson(route('cortex.virtual-agents.update', 'support'), ['name' => 'Renamed'])->assertForbidden();
    $this->postJson(route('cortex.virtual-agents.versions.store', 'support'), ['content' => 'New.'])->assertForbidden();
    $this->postJson(route('cortex.virtual-agents.versions.publish', ['support', 1]))->assertForbidden();

    mcpTool(ShowVirtualAgentVersionTool::class, ['slug' => 'support', 'version' => 1])->assertOk();
    mcpTool(UpdateVirtualAgentTool::class, ['slug' => 'support', 'name' => 'Renamed'])->assertHasErrors(['Unauthorized.']);
    mcpTool(CreateVirtualAgentVersionTool::class, ['slug' => 'support', 'content' => 'New.'])->assertHasErrors(['Unauthorized.']);
    mcpTool(PublishVirtualAgentVersionTool::class, ['slug' => 'support', 'version' => 1])->assertHasErrors(['Unauthorized.']);

    expect(VirtualAgentVersion::query()->count())->toBe(1)
        ->and(VirtualAgent::query()->value('name'))->not->toBe('Renamed');
});

it('checks the custom run ability on virtual agents over HTTP and MCP', function (): void {
    usePolicies([VirtualAgent::class => NoRunAgentPolicy::class]);

    DbAgent::fake(['Hello.']);
    VirtualAgent::factory()->published()->create(['slug' => 'helper']);

    $this->getJson(route('cortex.virtual-agents.show', 'helper'))->assertOk();
    $this->postJson(route('cortex.virtual-agents.run', 'helper'), ['input' => 'Hi'])->assertForbidden();

    mcpTool(RunVirtualAgentTool::class, ['slug' => 'helper', 'input' => 'Hi'])->assertHasErrors(['Unauthorized.']);

    DbAgent::assertNeverPrompted();
});

it('checks the first version of an override against the override policy', function (): void {
    usePolicies([ToolDescription::class => FrozenToolDescriptionPolicy::class]);

    app(ToolRegistry::class)->register('echo', EchoTool::class);

    $this->postJson(route('cortex.tools.description.versions.store', 'echo'), ['content' => 'Echo.'])
        ->assertForbidden();

    expect(ToolDescription::query()->count())->toBe(0);
});

it('passes the signed-in user to the policy over HTTP and MCP', function (): void {
    usePolicies([McpInstruction::class => SignedInMcpInstructionPolicy::class]);

    $this->postJson(route('cortex.servers.instructions.versions.store', 'cortex'), ['content' => 'Guest.'])
        ->assertForbidden();
    mcpTool(CreateServerInstructionVersionTool::class, ['server' => 'cortex', 'content' => 'Guest.'])
        ->assertHasErrors(['Unauthorized.']);

    $this->actingAs(new GenericUser(['id' => 1]));

    $this->postJson(route('cortex.servers.instructions.versions.store', 'cortex'), ['content' => 'Member.'])
        ->assertCreated();
    mcpTool(CreateServerInstructionVersionTool::class, ['server' => 'cortex', 'content' => 'Member again.'])
        ->assertOk();

    expect(McpInstructionVersion::query()->count())->toBe(2);
});
