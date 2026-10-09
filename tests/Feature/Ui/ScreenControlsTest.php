<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use JayI\Atrium\Domains\Navigation\Data\NavItem;
use JayI\Atrium\Domains\Navigation\Services\NavigationRegistry;
use JayI\Atrium\Domains\Search\Data\SearchSource;
use JayI\Cortex\Atrium\CortexPlugin;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use JayI\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use JayI\Cortex\Domains\McpServer\Models\McpInstructionModel;
use JayI\Cortex\Domains\Tool\Models\ToolDescriptionModel;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Cortex\Domains\VirtualAgent\Support\DbAgent;
use JayI\Cortex\Tests\Fixtures\EchoAgent;
use JayI\Cortex\Tests\Fixtures\EchoServer;
use JayI\Cortex\Tests\Fixtures\EchoTool;
use JayI\Cortex\Tests\Fixtures\Policies\GrantedConcreteAgentOverridePolicy;
use JayI\Cortex\Tests\Fixtures\Policies\GrantedMcpInstructionPolicy;
use JayI\Cortex\Tests\Fixtures\Policies\GrantedToolDescriptionPolicy;
use JayI\Cortex\Tests\Fixtures\Policies\GrantedVirtualAgentPolicy;

/**
 * Every control on the Cortex screens is shown only when its action would
 * be allowed, asked through the same policy as the JSON API, and the action
 * itself is refused otherwise.
 */
beforeEach(function (): void {
    ValidateCsrfToken::except(['*']);

    Gate::define('viewAtrium', fn (mixed $user = null): bool => true);

    Gate::policy(VirtualAgentModel::class, GrantedVirtualAgentPolicy::class);
    Gate::policy(ConcreteAgentOverrideModel::class, GrantedConcreteAgentOverridePolicy::class);
    Gate::policy(ToolDescriptionModel::class, GrantedToolDescriptionPolicy::class);
    Gate::policy(McpInstructionModel::class, GrantedMcpInstructionPolicy::class);

    config()->set('cortex.tools', ['echo' => EchoTool::class]);
    config()->set('cortex.mcp.servers', ['echo-server' => EchoServer::class]);

    app(AgentRegistry::class)->register('echo-agent', EchoAgent::class);
});

/**
 * A signed-in user granted exactly these abilities.
 *
 * @param  array<int, string>  $abilities
 */
function screenUser(array $abilities = []): GenericUser
{
    return new GenericUser(['id' => 1, 'name' => 'Viewer', 'email' => 'viewer@example.com', 'abilities' => $abilities]);
}

function screenTestId(string $id): string
{
    return 'data-testid="'.$id.'"';
}

/**
 * @return array<int, string>
 */
function screenNavigation(?GenericUser $user): array
{
    $request = Request::create('/atrium');
    $request->setUserResolver(fn (): ?GenericUser => $user);

    // Only Cortex's own items: other installed plugins add theirs.
    $items = array_filter(app(NavigationRegistry::class)->items($request), fn (NavItem $item): bool => $item->group === 'Cortex');

    return array_values(array_map(fn (NavItem $item): string => $item->label, $items));
}

function screenAgent(): VirtualAgentModel
{
    $agent = VirtualAgentModel::factory()->published('First.')->create(['name' => 'Helper', 'slug' => 'helper']);
    $agent->versions()->create(['version' => 2, 'content' => 'Second.']);

    return $agent;
}

/**
 * The echo agent's override: v1 published, v2 a draft.
 */
function screenOverride(): ConcreteAgentOverrideModel
{
    $override = ConcreteAgentOverrideModel::query()->create(['agent' => 'echo-agent']);
    $published = $override->versions()->create(['version' => 1, 'content' => 'Override prompt.']);
    $override->versions()->create(['version' => 2, 'content' => 'Draft.']);

    $override->published_version_id = (string) $published->getKey();
    $override->save();

    return $override;
}

it('shows each navigation item only with the ability its page needs', function (): void {
    expect(screenNavigation(screenUser()))->toBe(['Tools', 'Servers', 'Redirect domains'])
        ->and(screenNavigation(screenUser(['virtual-agents.viewAny'])))->toContain('Virtual agents')
        ->and(screenNavigation(screenUser(['concrete-agents.viewAny'])))->toContain('Concrete agents')
        ->and(screenNavigation(screenUser(['concrete-agents.viewAny'])))->toContain('Run agent')
        ->and(screenNavigation(screenUser(['virtual-agents.viewAny'])))->toContain('Run agent');
});

it('builds the navigation without querying agents', function (): void {
    screenAgent();
    $user = screenUser(['virtual-agents.viewAny']);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $labels = screenNavigation($user);
    $agentQueries = collect(DB::getQueryLog())->filter(fn (array $query): bool => str_contains($query['query'], 'cortex_'));

    expect($labels)->toContain('Run agent')
        ->and($agentQueries)->toBeEmpty();
});

it('shows an empty run page to someone who may see agents but run none', function (): void {
    screenAgent();

    $this->actingAs(screenUser(['virtual-agents.viewAny']))
        ->get(route('atrium.cortex.run'))
        ->assertOk()
        ->assertSee(screenTestId('no-runnable-agents'), false)
        ->assertDontSee(screenTestId('run-agent'), false);
});

it('refuses each page without the ability behind it', function (string $route, array $parameters): void {
    screenAgent();

    $this->actingAs(screenUser())->get(route($route, $parameters))->assertForbidden();
})->with([
    'virtual agents' => ['atrium.cortex.virtual-agents.index', []],
    'new virtual agent' => ['atrium.cortex.virtual-agents.create', []],
    'edit virtual agent' => ['atrium.cortex.virtual-agents.edit', ['helper']],
    'concrete agents' => ['atrium.cortex.concrete-agents.index', []],
    'concrete agent' => ['atrium.cortex.concrete-agents.show', ['echo-agent']],
    'run' => ['atrium.cortex.run', []],
    'tool description' => ['atrium.cortex.tools.description', ['echo']],
    'server instructions' => ['atrium.cortex.servers.instructions', ['echo-server']],
]);

it('lists virtual agents without the controls the viewer may not use', function (): void {
    screenAgent();

    $this->actingAs(screenUser(['virtual-agents.viewAny']))
        ->get(route('atrium.cortex.virtual-agents.index'))
        ->assertOk()
        ->assertSee('Helper')
        ->assertDontSee(screenTestId('new-agent'), false)
        ->assertDontSee(screenTestId('edit-helper'), false)
        ->assertDontSee(screenTestId('run-helper'), false)
        ->assertDontSee(screenTestId('delete-helper'), false);
});

it('shows each virtual agent list control with its ability', function (string $ability, string $control): void {
    screenAgent();

    $this->actingAs(screenUser(['virtual-agents.viewAny', 'virtual-agents.'.$ability]))
        ->get(route('atrium.cortex.virtual-agents.index'))
        ->assertOk()
        ->assertSee(screenTestId($control), false);
})->with([
    'create' => ['create', 'new-agent'],
    'edit' => ['view', 'edit-helper'],
    'run' => ['run', 'run-helper'],
    'delete' => ['delete', 'delete-helper'],
]);

it('shows a virtual agent read-only to someone who may only view it', function (): void {
    screenAgent();

    $this->actingAs(screenUser(['virtual-agents.view']))
        ->get(route('atrium.cortex.virtual-agents.edit', 'helper'))
        ->assertOk()
        ->assertSee(screenTestId('versions-card'), false)
        ->assertSee('<fieldset class="flex flex-col gap-4" disabled', false)
        ->assertDontSee(screenTestId('save-agent'), false)
        ->assertDontSee(screenTestId('publish-2'), false)
        ->assertDontSee(screenTestId('run-agent-link'), false);

    $this->actingAs(screenUser(['virtual-agents.view', 'virtual-agents.update', 'virtual-agents.run']))
        ->get(route('atrium.cortex.virtual-agents.edit', 'helper'))
        ->assertSee(screenTestId('save-agent'), false)
        ->assertSee(screenTestId('publish-2'), false)
        ->assertSee(screenTestId('run-agent-link'), false);
});

it('refuses virtual agent changes without their abilities', function (): void {
    $agent = screenAgent();
    $this->actingAs(screenUser(['virtual-agents.viewAny', 'virtual-agents.view']));

    $this->post(route('atrium.cortex.virtual-agents.store'), ['name' => 'New', 'slug' => 'new', 'instructions' => 'Hi.'])->assertForbidden();
    $this->put(route('atrium.cortex.virtual-agents.update', 'helper'), ['name' => 'Renamed', 'instructions' => 'First.'])->assertForbidden();
    $this->post(route('atrium.cortex.virtual-agents.versions.publish', ['helper', 2]))->assertForbidden();
    $this->delete(route('atrium.cortex.virtual-agents.destroy', 'helper'))->assertForbidden();

    expect(VirtualAgentModel::query()->count())->toBe(1)
        ->and($agent->fresh()?->name)->toBe('Helper')
        ->and($agent->fresh()?->publishedVersion?->version)->toBe(1);
});

it('allows virtual agent changes with their abilities', function (): void {
    screenAgent();
    $this->actingAs(screenUser(['virtual-agents.update', 'virtual-agents.delete']));

    $this->post(route('atrium.cortex.virtual-agents.versions.publish', ['helper', 2]))->assertRedirect();
    $this->delete(route('atrium.cortex.virtual-agents.destroy', 'helper'))->assertRedirect();

    expect(VirtualAgentModel::query()->count())->toBe(0);
});

it('lists concrete agents without the controls the viewer may not use', function (): void {
    $this->actingAs(screenUser(['concrete-agents.viewAny']))
        ->get(route('atrium.cortex.concrete-agents.index'))
        ->assertOk()
        ->assertSee('echo-agent')
        ->assertDontSee(screenTestId('manage-echo-agent'), false)
        ->assertDontSee(screenTestId('run-echo-agent'), false);

    $this->actingAs(screenUser(['concrete-agents.viewAny', 'concrete-agents.view', 'concrete-agents.run']))
        ->get(route('atrium.cortex.concrete-agents.index'))
        ->assertSee(screenTestId('manage-echo-agent'), false)
        ->assertSee(screenTestId('run-echo-agent'), false);
});

it('shows a concrete agent without the controls the viewer may not use', function (): void {
    screenOverride();

    $this->actingAs(screenUser(['concrete-agents.view']))
        ->get(route('atrium.cortex.concrete-agents.show', 'echo-agent'))
        ->assertOk()
        ->assertSee(screenTestId('versions-card'), false)
        ->assertDontSee(screenTestId('publish-2'), false)
        ->assertDontSee(screenTestId('new-version-card'), false)
        ->assertDontSee(screenTestId('remove-override'), false)
        ->assertDontSee(screenTestId('save-tools'), false)
        ->assertDontSee(screenTestId('run-agent-link'), false);
});

it('shows each concrete agent control with its ability', function (string $ability, string $control): void {
    screenOverride();

    $this->actingAs(screenUser(['concrete-agents.view', 'concrete-agents.'.$ability]))
        ->get(route('atrium.cortex.concrete-agents.show', 'echo-agent'))
        ->assertOk()
        ->assertSee(screenTestId($control), false);
})->with([
    'publish' => ['update', 'publish-2'],
    'new version' => ['update', 'new-version-card'],
    'tools' => ['update', 'save-tools'],
    'remove override' => ['delete', 'remove-override'],
    'run' => ['run', 'run-agent-link'],
]);

it('refuses concrete agent changes without their abilities', function (): void {
    screenOverride();

    $this->actingAs(screenUser(['concrete-agents.view']));

    $this->post(route('atrium.cortex.concrete-agents.store', 'echo-agent'), ['content' => 'New.'])->assertForbidden();
    $this->post(route('atrium.cortex.concrete-agents.publish', ['echo-agent', 2]))->assertForbidden();
    $this->put(route('atrium.cortex.concrete-agents.tools', 'echo-agent'), ['tools' => []])->assertForbidden();
    $this->delete(route('atrium.cortex.concrete-agents.destroy', 'echo-agent'))->assertForbidden();

    expect(ConcreteAgentOverrideModel::query()->sole()->versions()->count())->toBe(2);
});

it('offers to run only the agents the viewer may run, and refuses the others', function (): void {
    DbAgent::fake(['Hi back.']);
    screenAgent();

    $this->actingAs(screenUser(['concrete-agents.viewAny', 'concrete-agents.run']))
        ->get(route('atrium.cortex.run'))
        ->assertOk()
        ->assertSee('concrete:echo-agent', false)
        ->assertDontSee('virtual:helper', false);

    $this->post(route('atrium.cortex.run.store'), ['agent' => 'virtual:helper', 'input' => 'Hi'])->assertForbidden();
});

it('shows tool descriptions only to those who may view them', function (): void {
    $this->actingAs(screenUser())
        ->get(route('atrium.cortex.tools.index'))
        ->assertOk()
        ->assertDontSee(screenTestId('description-echo'), false);

    $this->actingAs(screenUser(['tool-descriptions.view']))
        ->get(route('atrium.cortex.tools.index'))
        ->assertSee(screenTestId('description-echo'), false);

    $this->get(route('atrium.cortex.tools.description', 'echo'))
        ->assertOk()
        ->assertDontSee(screenTestId('new-version-card'), false);

    $this->post(route('atrium.cortex.tools.description.store', 'echo'), ['content' => 'New.'])->assertForbidden();

    expect(ToolDescriptionModel::query()->count())->toBe(0);

    $this->actingAs(screenUser(['tool-descriptions.view', 'tool-descriptions.update']))
        ->get(route('atrium.cortex.tools.description', 'echo'))
        ->assertSee(screenTestId('add-version'), false);
});

it('shows server instructions only to those who may view them', function (): void {
    $this->actingAs(screenUser())
        ->get(route('atrium.cortex.servers.index'))
        ->assertOk()
        ->assertDontSee(screenTestId('instructions-echo-server'), false);

    $this->actingAs(screenUser(['server-instructions.view']))
        ->get(route('atrium.cortex.servers.index'))
        ->assertSee(screenTestId('instructions-echo-server'), false);

    $this->get(route('atrium.cortex.servers.instructions', 'echo-server'))
        ->assertOk()
        ->assertDontSee(screenTestId('new-version-card'), false);

    $this->post(route('atrium.cortex.servers.instructions.store', 'echo-server'), ['content' => 'New.'])->assertForbidden();

    expect(McpInstructionModel::query()->count())->toBe(0);
});

it('searches only what the searcher may list', function (): void {
    screenAgent();

    $source = app(CortexPlugin::class)->search();
    expect($source)->toBeInstanceOf(SearchSource::class);

    $request = Request::create('/atrium');
    $request->setUserResolver(fn (): GenericUser => screenUser());

    expect($source->isAuthorized($request))->toBeFalse();

    $this->actingAs(screenUser(['virtual-agents.viewAny', 'virtual-agents.view']));

    $results = array_map(fn ($result): string => $result->title, $source->results('e'));

    expect($results)->toBe(['Helper']);
});
