<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use JayI\Cortex\Actions\CreateVirtualAgentVersionAction;
use JayI\Cortex\Actions\RunVirtualAgentAction;
use JayI\Cortex\Exceptions\CircularAgentReferenceException;
use JayI\Cortex\Exceptions\ToolNotFoundException;
use JayI\Cortex\Exceptions\VirtualAgentNotPublishedException;
use JayI\Cortex\Facades\Cortex;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Runtime\AgentFactory;
use JayI\Cortex\Runtime\DbAgent;
use JayI\Cortex\Tests\Fixtures\EchoTool;
use JayI\Cortex\Tools\ToolRegistry;

it('builds an agent from its published prompt version', function () {
    $agent = VirtualAgent::factory()->published('Published.')->create();
    app(CreateVirtualAgentVersionAction::class)->execute($agent, ['content' => 'Draft.']);

    expect((string) app(AgentFactory::class)->make($agent->fresh())->instructions())->toBe('Published.');
});

it('throws when the agent has no published version', function () {
    $agent = VirtualAgent::factory()->create();

    app(AgentFactory::class)->make($agent);
})->throws(VirtualAgentNotPublishedException::class);

it('resolves registered tools and sub-agents onto the runtime agent', function () {
    app(ToolRegistry::class)->register('echo', EchoTool::class);
    $agent = VirtualAgent::factory()->published()->create(['tools' => ['echo']]);
    $agent->subAgents()->attach(VirtualAgent::factory()->published()->create(['slug' => 'researcher']));

    $tools = iterator_to_array(collect(app(AgentFactory::class)->make($agent)->tools())->getIterator());

    expect($tools)->toHaveCount(2)
        ->and($tools[0])->toBeInstanceOf(EchoTool::class)
        ->and($tools[1])->toBeInstanceOf(DbAgent::class)
        ->and($tools[1]->name())->toBe('researcher');
});

it('throws for unregistered tool names', function () {
    $agent = VirtualAgent::factory()->published()->create(['tools' => ['missing']]);

    app(AgentFactory::class)->make($agent);
})->throws(ToolNotFoundException::class);

it('guards against circular sub-agent graphs at build time', function () {
    $a = VirtualAgent::factory()->published()->create();
    $b = VirtualAgent::factory()->published()->create();
    $a->subAgents()->attach($b);
    DB::table('cortex_virtual_agent_sub_agents')->insert([
        'virtual_agent_id' => $b->getKey(),
        'sub_agent_id' => $a->getKey(),
    ]);

    app(AgentFactory::class)->make($a);
})->throws(CircularAgentReferenceException::class);

it('exposes provider, model, and settings to the ai sdk', function () {
    $agent = VirtualAgent::factory()->published()->create([
        'provider' => 'anthropic',
        'model' => 'claude-sonnet-5',
        'settings' => ['temperature' => 0.3, 'max_steps' => 5, 'max_tokens' => 1000, 'top_p' => 0.9],
    ]);

    $runtime = app(AgentFactory::class)->make($agent);

    expect($runtime->provider())->toBe('anthropic')
        ->and($runtime->model())->toBe('claude-sonnet-5')
        ->and($runtime->temperature())->toBe(0.3)
        ->and($runtime->maxSteps())->toBe(5)
        ->and($runtime->maxTokens())->toBe(1000)
        ->and($runtime->topP())->toBe(0.9);
});

it('offers itself to a parent agent under its slug and description', function () {
    $agent = VirtualAgent::factory()->published()->create(['slug' => 'researcher', 'description' => 'Finds things.']);

    $runtime = app(AgentFactory::class)->make($agent);

    expect($runtime->name())->toBe('researcher')
        ->and($runtime->description())->toBe('Finds things.');
});

it('runs an agent through the run action', function () {
    DbAgent::fake(['Hello from the agent.']);
    $agent = VirtualAgent::factory()->published()->create();

    $response = app(RunVirtualAgentAction::class)->execute($agent, 'Hi');

    expect($response->text)->toBe('Hello from the agent.');
    DbAgent::assertPrompted(fn ($prompt): bool => $prompt->prompt === 'Hi');
});

it('runs an agent through the manager by slug', function () {
    DbAgent::fake(['Managed.']);
    VirtualAgent::factory()->published()->create(['slug' => 'helper']);

    expect(Cortex::runVirtualAgent('helper', 'Hi')->text)->toBe('Managed.');
});
