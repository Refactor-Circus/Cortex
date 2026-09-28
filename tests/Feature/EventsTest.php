<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use JayI\Cortex\Actions\CreateConcreteAgentVersionAction;
use JayI\Cortex\Actions\CreateMcpInstructionVersionAction;
use JayI\Cortex\Actions\CreateToolDescriptionVersionAction;
use JayI\Cortex\Actions\CreateVirtualAgentAction;
use JayI\Cortex\Actions\CreateVirtualAgentVersionAction;
use JayI\Cortex\Actions\DeleteConcreteAgentOverrideAction;
use JayI\Cortex\Actions\DeleteMcpInstructionAction;
use JayI\Cortex\Actions\DeleteToolDescriptionAction;
use JayI\Cortex\Actions\DeleteVirtualAgentAction;
use JayI\Cortex\Actions\ListConcreteAgentsAction;
use JayI\Cortex\Actions\ListConcreteAgentVersionsAction;
use JayI\Cortex\Actions\ListMcpInstructionVersionsAction;
use JayI\Cortex\Actions\ListMcpServersAction;
use JayI\Cortex\Actions\ListProvidersAction;
use JayI\Cortex\Actions\ListToolDescriptionVersionsAction;
use JayI\Cortex\Actions\ListToolsAction;
use JayI\Cortex\Actions\ListVirtualAgentsAction;
use JayI\Cortex\Actions\ListVirtualAgentVersionsAction;
use JayI\Cortex\Actions\PublishConcreteAgentVersionAction;
use JayI\Cortex\Actions\PublishMcpInstructionVersionAction;
use JayI\Cortex\Actions\PublishToolDescriptionVersionAction;
use JayI\Cortex\Actions\PublishVirtualAgentVersionAction;
use JayI\Cortex\Actions\RunConcreteAgentAction;
use JayI\Cortex\Actions\RunVirtualAgentAction;
use JayI\Cortex\Actions\ShowConcreteAgentAction;
use JayI\Cortex\Actions\ShowMcpInstructionAction;
use JayI\Cortex\Actions\ShowToolDescriptionAction;
use JayI\Cortex\Actions\ShowVirtualAgentAction;
use JayI\Cortex\Actions\ShowVirtualAgentVersionAction;
use JayI\Cortex\Actions\UpdateConcreteAgentToolsAction;
use JayI\Cortex\Actions\UpdateVirtualAgentAction;
use JayI\Cortex\Agents\AgentRegistry;
use JayI\Cortex\Contracts\ActionFinishedEvent;
use JayI\Cortex\Contracts\ActionStartingEvent;
use JayI\Cortex\Contracts\ModelLifecycleEvent;
use JayI\Cortex\Events\Action\VirtualAgentCreatedActionEvent;
use JayI\Cortex\Events\Action\VirtualAgentCreatingActionEvent;
use JayI\Cortex\Events\Action\VirtualAgentRanActionEvent;
use JayI\Cortex\Events\Action\VirtualAgentVersionPublishedActionEvent;
use JayI\Cortex\Events\Action\VirtualAgentVersionPublishingActionEvent;
use JayI\Cortex\Events\Model\VirtualAgentCreatingEvent;
use JayI\Cortex\Events\Model\VirtualAgentVersionCreatedEvent;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Runtime\DbAgent;
use JayI\Cortex\Tests\Fixtures\EchoAgent;

/**
 * Record every event of a kind, in order.
 *
 * @param  class-string  $kind
 * @return ArrayObject<int, object>
 */
function recordEvents(string $kind): ArrayObject
{
    /** @var ArrayObject<int, object> $seen */
    $seen = new ArrayObject;

    Event::listen($kind, function (object $event) use ($seen): void {
        $seen->append($event);
    });

    return $seen;
}

it('fires every lifecycle event of an agent', function (): void {
    $seen = recordEvents(ModelLifecycleEvent::class);

    $agent = VirtualAgent::factory()->create();
    $agent->name = 'Renamed';
    $agent->save();
    VirtualAgent::query()->find($agent->id);
    $agent->replicate();
    $agent->delete();

    $hooks = collect($seen)
        ->filter(fn (ModelLifecycleEvent $event): bool => $event->model() instanceof VirtualAgent)
        ->map(fn (ModelLifecycleEvent $event): string => $event->hook())
        ->unique()
        ->values()
        ->all();

    expect($hooks)->toEqualCanonicalizing([
        'retrieved', 'creating', 'created', 'updating', 'updated', 'saving', 'saved',
        'deleting', 'deleted', 'replicating',
    ]);
});

it('fires the lifecycle events of versions and overrides', function (): void {
    $seen = recordEvents(ModelLifecycleEvent::class);

    app(CreateVirtualAgentAction::class)->execute(['name' => 'Helper', 'slug' => 'helper', 'instructions' => 'Be kind.']);
    app(CreateToolDescriptionVersionAction::class)->execute('echo', ['content' => 'Echo.', 'publish' => true]);
    app(CreateMcpInstructionVersionAction::class)->execute('cortex', ['content' => 'Use tools.', 'publish' => true]);
    app(CreateConcreteAgentVersionAction::class)->execute('echo-agent', ['content' => 'Echo.', 'publish' => true]);

    $models = collect($seen)
        ->map(fn (ModelLifecycleEvent $event): string => class_basename($event->model()).'.'.$event->hook())
        ->unique();

    expect($models)->toContain(
        'VirtualAgent.creating', 'VirtualAgent.created', 'VirtualAgent.updated', 'VirtualAgentVersion.created',
        'ToolDescription.created', 'ToolDescription.updated', 'ToolDescriptionVersion.created',
        'McpInstruction.created', 'McpInstruction.updated', 'McpInstructionVersion.created',
        'ConcreteAgentOverride.created', 'ConcreteAgentOverride.updated', 'ConcreteAgentOverrideVersion.created',
    );
});

it('carries the model on the event', function (): void {
    Event::fake([VirtualAgentVersionCreatedEvent::class]);

    $agent = app(CreateVirtualAgentAction::class)->execute(['name' => 'Helper', 'slug' => 'helper', 'instructions' => 'Be kind.']);

    Event::assertDispatched(
        VirtualAgentVersionCreatedEvent::class,
        fn (VirtualAgentVersionCreatedEvent $event): bool => $event->version->virtual_agent_id === $agent->id && $event->model() === $event->version,
    );
});

it('lets a creating listener stop an agent being created', function (): void {
    Event::listen(VirtualAgentCreatingEvent::class, fn (): bool => false);

    $agent = new VirtualAgent(['name' => 'Helper', 'slug' => 'helper']);

    expect($agent->save())->toBeFalse()
        ->and(VirtualAgent::query()->count())->toBe(0);
});

it('starts and finishes every action once, in order', function (): void {
    app(AgentRegistry::class)->register('echo-agent', EchoAgent::class);

    $starts = recordEvents(ActionStartingEvent::class);
    $stops = recordEvents(ActionFinishedEvent::class);

    DbAgent::fake(['Hello.']);
    EchoAgent::fake(['Echoed.']);

    $agent = app(CreateVirtualAgentAction::class)->execute(['name' => 'Helper', 'slug' => 'helper', 'instructions' => 'Be kind.']);
    app(CreateVirtualAgentVersionAction::class)->execute($agent, ['content' => 'Be kinder.']);
    app(PublishVirtualAgentVersionAction::class)->execute($agent, 2);
    app(ShowVirtualAgentVersionAction::class)->execute($agent, 1);
    app(ListVirtualAgentVersionsAction::class)->execute($agent);
    app(UpdateVirtualAgentAction::class)->execute($agent, ['description' => 'Helps.']);
    app(ShowVirtualAgentAction::class)->execute($agent);
    app(ListVirtualAgentsAction::class)->execute();
    app(RunVirtualAgentAction::class)->execute($agent, 'Hi');
    app(DeleteVirtualAgentAction::class)->execute($agent);

    app(CreateConcreteAgentVersionAction::class)->execute('echo-agent', ['content' => 'Echo louder.']);
    $override = app(ShowConcreteAgentAction::class)->execute('echo-agent')['override'];
    app(PublishConcreteAgentVersionAction::class)->execute($override, 1);
    app(ListConcreteAgentVersionsAction::class)->execute($override);
    app(UpdateConcreteAgentToolsAction::class)->execute('echo-agent', ['EchoTool']);
    app(ListConcreteAgentsAction::class)->execute();
    app(RunConcreteAgentAction::class)->execute('echo-agent', 'Hi');
    app(DeleteConcreteAgentOverrideAction::class)->execute($override);

    app(CreateToolDescriptionVersionAction::class)->execute('echo', ['content' => 'Echo.']);
    $description = app(ShowToolDescriptionAction::class)->execute('echo');
    app(PublishToolDescriptionVersionAction::class)->execute($description, 1);
    app(ListToolDescriptionVersionsAction::class)->execute($description);
    app(DeleteToolDescriptionAction::class)->execute($description);

    app(CreateMcpInstructionVersionAction::class)->execute('cortex', ['content' => 'Use tools.']);
    $instruction = app(ShowMcpInstructionAction::class)->execute('cortex');
    app(PublishMcpInstructionVersionAction::class)->execute($instruction, 1);
    app(ListMcpInstructionVersionsAction::class)->execute($instruction);
    app(DeleteMcpInstructionAction::class)->execute($instruction);

    app(ListToolsAction::class)->execute();
    app(ListMcpServersAction::class)->execute();
    app(ListProvidersAction::class)->execute();

    $started = collect($starts)->map(fn (object $event): string => class_basename($event))->unique();
    $finished = collect($stops)->map(fn (object $event): string => class_basename($event))->unique();

    expect($started)->toHaveCount(31)
        ->and($finished)->toHaveCount(31)
        ->and(count($starts))->toBe(count($stops))
        ->and($started->all())->toContain(
            'VirtualAgentCreatingActionEvent', 'VirtualAgentVersionCreatingActionEvent', 'VirtualAgentVersionPublishingActionEvent',
            'VirtualAgentRunningActionEvent', 'ConcreteAgentRunningActionEvent', 'ConcreteAgentToolsUpdatingActionEvent',
            'ToolDescriptionVersionCreatingActionEvent', 'McpInstructionVersionPublishingActionEvent',
        )
        ->and($finished->all())->toContain('VirtualAgentRanActionEvent', 'ConcreteAgentOverrideDeletedActionEvent', 'McpServersListedActionEvent');
});

it('gives every action exactly one start and one finish event', function (): void {
    $actions = glob(dirname(__DIR__, 2).'/src/Actions/*Action.php') ?: [];

    $unpaired = [];

    foreach ($actions as $path) {
        $source = (string) file_get_contents($path);
        preg_match_all('/([A-Za-z]+ActionEvent)::dispatch/', $source, $matches);

        $kinds = array_map(
            fn (string $event): string => is_subclass_of('JayI\\Cortex\\Events\\Action\\'.$event, ActionStartingEvent::class) ? 'start' : 'finish',
            $matches[1],
        );

        sort($kinds);

        if ($kinds !== ['finish', 'start']) {
            $unpaired[] = basename($path, '.php');
        }
    }

    expect($actions)->toHaveCount(31)
        ->and($unpaired)->toBe([]);
});

it('carries the input on the start event and the result on the finish event', function (): void {
    DbAgent::fake(['Hello.']);
    $agent = VirtualAgent::factory()->published()->create();

    Event::fake([VirtualAgentRanActionEvent::class]);

    app(RunVirtualAgentAction::class)->execute($agent, 'Hi');

    Event::assertDispatched(
        VirtualAgentRanActionEvent::class,
        fn (VirtualAgentRanActionEvent $event): bool => $event->agent->is($agent) && $event->input === 'Hi' && $event->response->text === 'Hello.',
    );
});

it('starts before the work and finishes only once it is committed', function (): void {
    $agentsAtStart = null;

    Event::listen(VirtualAgentCreatingActionEvent::class, function () use (&$agentsAtStart): void {
        $agentsAtStart = VirtualAgent::query()->count();
    });

    $finished = recordEvents(VirtualAgentCreatedActionEvent::class);

    DB::transaction(function () use ($finished): void {
        app(CreateVirtualAgentAction::class)->execute(['name' => 'Helper', 'slug' => 'helper', 'instructions' => 'Be kind.']);

        expect($finished)->toHaveCount(0);
    });

    expect($agentsAtStart)->toBe(0)
        ->and($finished)->toHaveCount(1)
        ->and($finished[0]->agent->slug)->toBe('helper');
});

it('starts a failed action but never finishes it', function (): void {
    $agent = VirtualAgent::factory()->published()->create();

    $starts = recordEvents(VirtualAgentVersionPublishingActionEvent::class);
    $stops = recordEvents(VirtualAgentVersionPublishedActionEvent::class);

    expect(fn (): mixed => app(PublishVirtualAgentVersionAction::class)->execute($agent, 9))
        ->toThrow(ModelNotFoundException::class);

    expect($starts)->toHaveCount(1)
        ->and($stops)->toHaveCount(0);
});
