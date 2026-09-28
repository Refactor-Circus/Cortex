<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use JayI\Cortex\Models\ToolDescription;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Runtime\DbAgent;
use JayI\Cortex\Tests\Fixtures\EchoTool;

beforeEach(function (): void {
    ValidateCsrfToken::except(['*']);

    app()->detectEnvironment(fn (): string => 'local');

    config()->set('cortex.tools', ['echo' => EchoTool::class]);
});

it('lists virtual agents', function (): void {
    VirtualAgent::factory()->published()->create(['name' => 'Helper', 'slug' => 'helper', 'provider' => 'openai', 'model' => 'gpt-4']);

    $this->get(route('atrium.cortex.virtual-agents.index'))
        ->assertOk()
        ->assertSee('Helper')
        ->assertSee('openai')
        ->assertSee('v1');
});

it('creates a virtual agent with its prompt and drops blank settings', function (): void {
    $this->post(route('atrium.cortex.virtual-agents.store'), [
        'name' => 'Helper',
        'slug' => 'helper',
        'instructions' => 'Help out.',
        'settings' => ['temperature' => '0.7', 'max_steps' => '', 'top_p' => ''],
    ])->assertRedirect(route('atrium.cortex.virtual-agents.index'));

    $agent = VirtualAgent::query()->where('slug', 'helper')->firstOrFail();

    // Blank inputs are omitted rather than stored as empty strings.
    expect($agent->settings)->toBe(['temperature' => 0.7])
        ->and($agent->publishedVersion?->content)->toBe('Help out.');
});

it('versions the prompt in place when the form saves changed instructions', function (): void {
    $agent = VirtualAgent::factory()->published('First.')->create(['slug' => 'helper', 'tools' => ['echo']]);

    $this->put(route('atrium.cortex.virtual-agents.update', 'helper'), [
        'name' => $agent->name,
        'instructions' => 'Second.',
    ])->assertRedirect(route('atrium.cortex.virtual-agents.edit', 'helper'));

    $agent->refresh();

    // Unchecked tool boxes submit nothing, which means no tools.
    expect($agent->publishedVersion?->content)->toBe('Second.')
        ->and($agent->versions()->count())->toBe(2)
        ->and($agent->tools)->toBe([]);

    $this->get(route('atrium.cortex.virtual-agents.edit', 'helper'))
        ->assertOk()
        ->assertSee('Second.')
        ->assertSee('v1')
        ->assertSee('v2');
});

it('republishes an earlier prompt version', function (): void {
    $agent = VirtualAgent::factory()->published('First.')->create(['slug' => 'helper']);
    $this->put(route('atrium.cortex.virtual-agents.update', 'helper'), ['name' => $agent->name, 'instructions' => 'Second.']);

    $this->post(route('atrium.cortex.virtual-agents.versions.publish', ['helper', 1]))
        ->assertRedirect(route('atrium.cortex.virtual-agents.edit', 'helper'));

    expect($agent->refresh()->publishedVersion?->content)->toBe('First.');
});

it('renders the agent form with a saved provider that is no longer offered', function (): void {
    VirtualAgent::factory()->published()->create(['slug' => 'helper', 'provider' => 'retired-provider']);

    $this->get(route('atrium.cortex.virtual-agents.edit', 'helper'))
        ->assertOk()
        ->assertSee('retired-provider');
});

it('deletes a virtual agent', function (): void {
    VirtualAgent::factory()->create(['slug' => 'helper']);

    $this->delete(route('atrium.cortex.virtual-agents.destroy', 'helper'))->assertRedirect();

    expect(VirtualAgent::query()->count())->toBe(0);
});

it('renders the run page and preselects an agent', function (): void {
    VirtualAgent::factory()->create(['name' => 'Helper', 'slug' => 'helper']);

    $this->get(route('atrium.cortex.run', ['agent' => 'virtual:helper']))
        ->assertOk()
        ->assertSee('Helper')
        ->assertSee('value="virtual:helper" selected', false);
});

it('runs a virtual agent from the run page', function (): void {
    DbAgent::fake(['Hi from the agent.']);
    VirtualAgent::factory()->published()->create(['slug' => 'helper']);

    $this->post(route('atrium.cortex.run.store'), ['agent' => 'virtual:helper', 'input' => 'Hi'])
        ->assertOk()
        ->assertSee('Hi from the agent.');
});

it('lists tools', function (): void {
    $this->get(route('atrium.cortex.tools.index'))->assertOk()->assertSee('echo');
});

it('shows a tool description falling back to the code declaration', function (): void {
    // No override row exists, so the page shows what the class declares
    // rather than the 404 the JSON API would answer with.
    $this->get(route('atrium.cortex.tools.description', 'echo'))
        ->assertOk()
        ->assertSee(__('cortex::cortex.from_code'));
});

it('404s for a tool that is not registered', function (): void {
    $this->get(route('atrium.cortex.tools.description', 'nope'))->assertNotFound();
});

it('creates and publishes a description override', function (): void {
    $this->post(route('atrium.cortex.tools.description.store', 'echo'), [
        'content' => 'A better description.',
        'publish' => true,
    ])->assertRedirect();

    $description = ToolDescription::query()->where('tool', 'echo')->firstOrFail();

    expect($description->publishedVersion?->content)->toBe('A better description.');

    $this->get(route('atrium.cortex.tools.description', 'echo'))
        ->assertOk()
        ->assertSee('A better description.');
});

it('removes a description override', function (): void {
    $this->post(route('atrium.cortex.tools.description.store', 'echo'), ['content' => 'Override', 'publish' => true]);

    $this->delete(route('atrium.cortex.tools.description.destroy', 'echo'))->assertRedirect();

    expect(ToolDescription::query()->count())->toBe(0);
});

it('lists mcp servers', function (): void {
    $this->get(route('atrium.cortex.servers.index'))->assertOk()->assertSee('cortex');
});

it('shows server instructions', function (): void {
    $this->get(route('atrium.cortex.servers.instructions', 'cortex'))
        ->assertOk()
        ->assertSee(__('cortex::cortex.from_code'));
});
