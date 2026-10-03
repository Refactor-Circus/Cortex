<?php

declare(strict_types=1);

use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use JayI\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use JayI\Cortex\Tests\Fixtures\EchoAgent;

beforeEach(function () {
    app(AgentRegistry::class)->register('echo-agent', EchoAgent::class);
});

it('lists concrete agents', function () {
    $this->getJson(route('cortex.concrete-agents.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'echo-agent')
        ->assertJsonPath('data.0.overridable', true)
        ->assertJsonPath('data.0.tools', ['EchoTool'])
        ->assertJsonPath('data.0.published_version', null)
        ->assertJsonPath('data.0.tools_overridden', false);
});

it('shows a concrete agent with its defaults', function () {
    $this->getJson(route('cortex.concrete-agents.show', 'echo-agent'))
        ->assertOk()
        ->assertJsonPath('data.name', 'echo-agent')
        ->assertJsonPath('data.instructions', 'Echo everything back.')
        ->assertJsonPath('data.default_instructions', 'Echo everything back.')
        ->assertJsonPath('data.default_tools', ['EchoTool']);
});

it('returns 404 for unregistered agents', function () {
    $this->getJson(route('cortex.concrete-agents.show', 'missing'))->assertNotFound();
    $this->postJson(route('cortex.concrete-agents.versions.store', 'missing'), ['content' => 'x'])->assertNotFound();
});

it('creates, lists and publishes prompt versions', function () {
    $store = route('cortex.concrete-agents.versions.store', 'echo-agent');

    $this->postJson($store, ['content' => 'v1'])
        ->assertCreated()
        ->assertJsonPath('data.version', 1);
    $this->postJson($store, ['content' => 'v2', 'publish' => true])
        ->assertCreated()
        ->assertJsonPath('data.version', 2);

    $this->getJson(route('cortex.concrete-agents.versions.index', 'echo-agent'))
        ->assertOk()
        ->assertJsonPath('data.0.version', 2)
        ->assertJsonPath('data.1.version', 1);

    $this->postJson(route('cortex.concrete-agents.versions.publish', ['echo-agent', 1]))
        ->assertOk()
        ->assertJsonPath('data.published_version', 1)
        ->assertJsonPath('data.published_content', 'v1');

    app()->forgetScopedInstances();

    $this->getJson(route('cortex.concrete-agents.show', 'echo-agent'))
        ->assertJsonPath('data.instructions', 'v1')
        ->assertJsonPath('data.published_version', 1);
});

it('returns 404 listing versions without an override', function () {
    $this->getJson(route('cortex.concrete-agents.versions.index', 'echo-agent'))->assertNotFound();
});

it('sets and clears the toolset override', function () {
    $tools = route('cortex.concrete-agents.tools.update', 'echo-agent');

    $this->putJson($tools, ['tools' => ['EchoTool']])
        ->assertOk()
        ->assertJsonPath('data.agent', 'echo-agent')
        ->assertJsonPath('data.tools', ['EchoTool']);

    $this->putJson($tools, ['tools' => null])
        ->assertOk()
        ->assertJsonPath('data.tools', null);

    expect(ConcreteAgentOverrideModel::query()->where('agent', 'echo-agent')->value('tools'))->toBeNull();
});

it('rejects unknown tools and a missing tools key', function () {
    $tools = route('cortex.concrete-agents.tools.update', 'echo-agent');

    $this->putJson($tools, ['tools' => ['missing']])->assertJsonValidationErrors(['tools.0']);
    $this->putJson($tools, [])->assertJsonValidationErrors(['tools']);
});

it('deletes the override', function () {
    $this->deleteJson(route('cortex.concrete-agents.override.destroy', 'echo-agent'))->assertNotFound();

    $this->putJson(route('cortex.concrete-agents.tools.update', 'echo-agent'), ['tools' => []])->assertOk();

    $this->deleteJson(route('cortex.concrete-agents.override.destroy', 'echo-agent'))->assertNoContent();

    expect(ConcreteAgentOverrideModel::query()->count())->toBe(0);
});

it('runs a concrete agent', function () {
    EchoAgent::fake(['Hi back.']);

    $this->postJson(route('cortex.concrete-agents.run', 'echo-agent'), ['input' => 'Hi'])
        ->assertOk()
        ->assertJsonPath('data.text', 'Hi back.')
        ->assertJsonStructure(['data' => ['text', 'usage']]);

    EchoAgent::assertPrompted(fn ($prompt): bool => $prompt->prompt === 'Hi');
});

it('validates run input', function () {
    $this->postJson(route('cortex.concrete-agents.run', 'echo-agent'), [])
        ->assertJsonValidationErrors(['input']);
});
