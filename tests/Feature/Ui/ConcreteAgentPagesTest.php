<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use JayI\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use JayI\Cortex\Tests\Fixtures\EchoAgent;
use JayI\Cortex\Tests\Fixtures\PlainAgent;

beforeEach(function (): void {
    ValidateCsrfToken::except(['*']);

    app()->detectEnvironment(fn (): string => 'local');
});

function registerEchoAgent(): void
{
    app(AgentRegistry::class)->register('echo-agent', EchoAgent::class);
}

it('lists concrete agents', function (): void {
    registerEchoAgent();

    $this->get(route('atrium.cortex.concrete-agents.index'))
        ->assertOk()
        ->assertSee('echo-agent')
        ->assertSee(__('cortex::cortex.from_code'));
});

it('shows an empty state with no concrete agents', function (): void {
    $this->get(route('atrium.cortex.concrete-agents.index'))
        ->assertOk()
        ->assertSee(__('cortex::cortex.no_concrete_agents'));
});

it('shows an agent falling back to its code prompt and tools', function (): void {
    registerEchoAgent();

    $this->get(route('atrium.cortex.concrete-agents.show', 'echo-agent'))
        ->assertOk()
        ->assertSee(__('cortex::cortex.from_code'))
        ->assertSee('Echo everything back.')
        ->assertSee('EchoTool')
        ->assertDontSee(__('cortex::cortex.not_overridable'));
});

it('404s for an agent that is not registered', function (): void {
    $this->get(route('atrium.cortex.concrete-agents.show', 'nope'))->assertNotFound();
});

it('creates and publishes a prompt version', function (): void {
    registerEchoAgent();

    $this->post(route('atrium.cortex.concrete-agents.store', 'echo-agent'), [
        'content' => 'A better prompt.',
        'publish' => true,
    ])->assertRedirect(route('atrium.cortex.concrete-agents.show', 'echo-agent'));

    $override = ConcreteAgentOverrideModel::query()->where('agent', 'echo-agent')->firstOrFail();

    expect($override->publishedVersion?->content)->toBe('A better prompt.');

    $this->get(route('atrium.cortex.concrete-agents.show', 'echo-agent'))
        ->assertOk()
        ->assertSee('A better prompt.');
});

it('publishes an older version', function (): void {
    registerEchoAgent();

    $this->post(route('atrium.cortex.concrete-agents.store', 'echo-agent'), ['content' => 'v1', 'publish' => true]);
    $this->post(route('atrium.cortex.concrete-agents.store', 'echo-agent'), ['content' => 'v2', 'publish' => true]);

    $this->post(route('atrium.cortex.concrete-agents.publish', ['echo-agent', 1]))->assertRedirect();

    expect(ConcreteAgentOverrideModel::query()->firstOrFail()->publishedVersion?->content)->toBe('v1');
});

it('saves the checked tools as an override and resets to code', function (): void {
    registerEchoAgent();

    $this->put(route('atrium.cortex.concrete-agents.tools', 'echo-agent'), ['tools' => []])
        ->assertRedirect(route('atrium.cortex.concrete-agents.show', 'echo-agent'));

    expect(ConcreteAgentOverrideModel::query()->firstOrFail()->tools)->toBe([]);

    $this->put(route('atrium.cortex.concrete-agents.tools', 'echo-agent'), ['tools' => ['EchoTool']]);

    expect(ConcreteAgentOverrideModel::query()->firstOrFail()->tools)->toBe(['EchoTool']);

    $this->get(route('atrium.cortex.concrete-agents.show', 'echo-agent'))
        ->assertOk()
        ->assertSee(__('cortex::cortex.overridden'));

    $this->put(route('atrium.cortex.concrete-agents.tools', 'echo-agent'), ['tools' => ['EchoTool'], 'use_code_tools' => '1']);

    expect(ConcreteAgentOverrideModel::query()->firstOrFail()->tools)->toBeNull();
});

it('removes the overrides', function (): void {
    registerEchoAgent();

    $this->post(route('atrium.cortex.concrete-agents.store', 'echo-agent'), ['content' => 'Override', 'publish' => true]);

    $this->delete(route('atrium.cortex.concrete-agents.destroy', 'echo-agent'))->assertRedirect();

    expect(ConcreteAgentOverrideModel::query()->count())->toBe(0);
});

it('warns when an agent cannot be overridden', function (): void {
    app(AgentRegistry::class)->register('plain', PlainAgent::class);

    $this->get(route('atrium.cortex.concrete-agents.show', 'plain'))
        ->assertOk()
        ->assertSee(__('cortex::cortex.not_overridable'));
});

it('runs a concrete agent from the run page', function (): void {
    registerEchoAgent();
    EchoAgent::fake(['Hi back.']);

    $this->get(route('atrium.cortex.run', ['agent' => 'concrete:echo-agent']))
        ->assertOk()
        ->assertSee('concrete:echo-agent', false);

    $this->post(route('atrium.cortex.run.store'), ['agent' => 'concrete:echo-agent', 'input' => 'Hi'])
        ->assertOk()
        ->assertSee('Hi back.');

    EchoAgent::assertPrompted(fn ($prompt): bool => $prompt->prompt === 'Hi');
});
