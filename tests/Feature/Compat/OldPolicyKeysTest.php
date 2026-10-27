<?php

declare(strict_types=1);

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use RefactorCircus\Cortex\CortexServiceProvider;
use RefactorCircus\Cortex\Domains\ConcreteServer\ConcreteServerServiceProvider;
use RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Tools\CreateServerInstructionVersionTool;
use RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Tools\DeleteServerInstructionsTool;
use RefactorCircus\Cortex\Domains\ConcreteServer\Mcp\Tools\ShowServerInstructionsTool;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteServer\Policies\McpInstructionPolicy;
use RefactorCircus\Cortex\Domains\ConcreteServer\Policies\McpInstructionVersionPolicy;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Tests\Fixtures\EchoServer;
use RefactorCircus\Cortex\Tests\Fixtures\Policies\DenyingOldKeyMcpInstructionPolicy;

/**
 * The names the two models and their policies had before the domain was
 * renamed from `McpServer`, as a host's published `config/cortex.php` lists
 * them.
 *
 * @return array{instruction: string, version: string, instruction_policy: string, version_policy: string}
 */
function oldPolicyKeyNames(): array
{
    return [
        'instruction' => 'RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionModel',
        'version' => 'RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionVersionModel',
        'instruction_policy' => 'RefactorCircus\Cortex\Domains\McpServer\Policies\McpInstructionPolicy',
        'version_policy' => 'RefactorCircus\Cortex\Domains\McpServer\Policies\McpInstructionVersionPolicy',
    ];
}

/**
 * The bundled `cortex.policies` map without the two moved models, as in a
 * config file a host published before the rename.
 *
 * @return array<string, string>
 */
function policiesPublishedBeforeRename(): array
{
    /** @var array<string, string> $policies */
    $policies = config('cortex.policies');

    return Arr::except($policies, [McpInstructionModel::class, McpInstructionVersionModel::class]);
}

/**
 * Replace `cortex.policies` with a host's map, then run what the providers
 * run: the remap and its warnings from the ConcreteServer provider, and
 * `registerPolicies()` from the Cortex provider.
 *
 * @param  array<string, string>  $policies
 */
function bootWithHostPolicies(array $policies): void
{
    config()->set('cortex.policies', $policies);

    $provider = app()->getProvider(ConcreteServerServiceProvider::class);

    expect($provider)->toBeInstanceOf(ConcreteServerServiceProvider::class);

    /** @var ConcreteServerServiceProvider $provider */
    $provider->remapOldPolicyKeys();
    $provider->warnAboutOldPolicyKeys();

    (fn () => $this->registerPolicies())->call(app()->getProvider(CortexServiceProvider::class));
}

/**
 * Whether a logged warning names both the old and the new policy key.
 */
function namesPolicyKeys(string $message, mixed $context, string $old, string $new): bool
{
    return str_contains($message, $old)
        && str_contains($message, $new)
        && is_array($context)
        && ($context['old'] ?? null) === $old
        && ($context['new'] ?? null) === $new;
}

beforeEach(function (): void {
    app(McpServerRegistry::class)->register('echo', EchoServer::class);
});

it('applies a denying host policy keyed by the old model name over HTTP and MCP', function (): void {
    $old = oldPolicyKeyNames();

    bootWithHostPolicies([
        ...policiesPublishedBeforeRename(),
        $old['instruction'] => DenyingOldKeyMcpInstructionPolicy::class,
        $old['version'] => $old['version_policy'],
    ]);

    $instruction = McpInstructionModel::query()->create(['server' => 'echo']);

    expect(Gate::getPolicyFor(McpInstructionModel::class))->toBeInstanceOf(DenyingOldKeyMcpInstructionPolicy::class);

    // Reading is allowed: the host policy's methods type-hint the old model name.
    $this->getJson(route('cortex.servers.instructions.show', 'echo'))->assertOk();
    mcpTool(ShowServerInstructionsTool::class, ['server' => 'echo'])->assertOk();

    // Changing is refused, not left to the bundled allow-all policy.
    $this->postJson(route('cortex.servers.instructions.versions.store', 'echo'), ['content' => 'Guest.'])
        ->assertUnauthorized();
    $this->deleteJson(route('cortex.servers.instructions.destroy', 'echo'))->assertUnauthorized();

    mcpTool(CreateServerInstructionVersionTool::class, ['server' => 'echo', 'content' => 'Guest.'])
        ->assertHasErrors(['Unauthorized.']);
    mcpTool(DeleteServerInstructionsTool::class, ['server' => 'echo'])
        ->assertHasErrors(['Unauthorized.']);

    expect(McpInstructionVersionModel::query()->count())->toBe(0)
        ->and($instruction->fresh())->not->toBeNull();
});

it('moves old keys to the new model classes in the config', function (): void {
    $old = oldPolicyKeyNames();

    bootWithHostPolicies([
        ...policiesPublishedBeforeRename(),
        $old['instruction'] => DenyingOldKeyMcpInstructionPolicy::class,
        $old['version'] => $old['version_policy'],
    ]);

    /** @var array<string, string> $policies */
    $policies = config('cortex.policies');

    expect($policies)->not->toHaveKey($old['instruction'])
        ->and($policies)->not->toHaveKey($old['version'])
        ->and($policies[McpInstructionModel::class])->toBe(DenyingOldKeyMcpInstructionPolicy::class)
        ->and($policies[McpInstructionVersionModel::class])->toBe($old['version_policy']);
});

it('logs one deprecation warning naming the old and new keys per remapped key', function (): void {
    $old = oldPolicyKeyNames();

    Log::spy();

    bootWithHostPolicies([
        ...policiesPublishedBeforeRename(),
        $old['instruction'] => DenyingOldKeyMcpInstructionPolicy::class,
        $old['version'] => $old['version_policy'],
    ]);

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, mixed $context = []): bool => namesPolicyKeys($message, $context, $old['instruction'], McpInstructionModel::class))
        ->once();
    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, mixed $context = []): bool => namesPolicyKeys($message, $context, $old['version'], McpInstructionVersionModel::class))
        ->once();
    Log::shouldHaveReceived('warning')->twice();

    // The warnings are logged once per boot, not on every later call.
    app()->getProvider(ConcreteServerServiceProvider::class)?->warnAboutOldPolicyKeys();

    Log::shouldHaveReceived('warning')->twice();
});

it('lets the new key win when both keys are listed, and still warns about the old one', function (): void {
    $old = oldPolicyKeyNames();

    Log::spy();

    bootWithHostPolicies([
        ...policiesPublishedBeforeRename(),
        $old['instruction'] => DenyingOldKeyMcpInstructionPolicy::class,
        McpInstructionModel::class => McpInstructionPolicy::class,
    ]);

    /** @var array<string, string> $policies */
    $policies = config('cortex.policies');

    expect(Gate::getPolicyFor(McpInstructionModel::class))->toBeInstanceOf(McpInstructionPolicy::class)
        ->and($policies)->not->toHaveKey($old['instruction'])
        ->and($policies[McpInstructionModel::class])->toBe(McpInstructionPolicy::class);

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, mixed $context = []): bool => namesPolicyKeys($message, $context, $old['instruction'], McpInstructionModel::class))
        ->once();
    Log::shouldHaveReceived('warning')->once();

    // The new key's allow-all policy applies, not the old key's denying one.
    $this->postJson(route('cortex.servers.instructions.versions.store', 'echo'), ['content' => 'Allowed.'])
        ->assertCreated();
});

it('resolves an old policy class name given as the value through the shims', function (): void {
    $old = oldPolicyKeyNames();

    bootWithHostPolicies([
        ...policiesPublishedBeforeRename(),
        $old['instruction'] => $old['instruction_policy'],
        McpInstructionVersionModel::class => $old['version_policy'],
    ]);

    $instructionPolicy = Gate::getPolicyFor(McpInstructionModel::class);
    $versionPolicy = Gate::getPolicyFor(McpInstructionVersionModel::class);

    expect($instructionPolicy)->toBeInstanceOf(McpInstructionPolicy::class)
        ->and((new ReflectionClass($instructionPolicy))->getName())->toBe(McpInstructionPolicy::class)
        ->and($versionPolicy)->toBeInstanceOf(McpInstructionVersionPolicy::class)
        ->and((new ReflectionClass($versionPolicy))->getName())->toBe(McpInstructionVersionPolicy::class);

    $this->postJson(route('cortex.servers.instructions.versions.store', 'echo'), ['content' => 'Allowed.'])
        ->assertCreated();
});

it('leaves a config without old keys alone and logs nothing', function (): void {
    /** @var array<string, string> $before */
    $before = config('cortex.policies');

    Log::spy();

    bootWithHostPolicies($before);

    expect(config('cortex.policies'))->toBe($before)
        ->and(Gate::getPolicyFor(McpInstructionModel::class))->toBeInstanceOf(McpInstructionPolicy::class);

    Log::shouldNotHaveReceived('warning');
});

it('remaps while registering, before any provider boots and registers the policies', function (): void {
    $old = oldPolicyKeyNames();

    config()->set('cortex.policies', [
        ...policiesPublishedBeforeRename(),
        $old['instruction'] => DenyingOldKeyMcpInstructionPolicy::class,
    ]);

    $provider = new ConcreteServerServiceProvider(app());
    $provider->register();

    /** @var array<string, string> $policies */
    $policies = config('cortex.policies');

    expect($policies)->not->toHaveKey($old['instruction'])
        ->and($policies[McpInstructionModel::class])->toBe(DenyingOldKeyMcpInstructionPolicy::class);

    Log::spy();

    $provider->warnAboutOldPolicyKeys();

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, mixed $context = []): bool => namesPolicyKeys($message, $context, $old['instruction'], McpInstructionModel::class))
        ->once();
});
