<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use JayI\Cortex\Contracts\ActionFinishedEvent;
use JayI\Cortex\Contracts\ActionStartingEvent;
use JayI\Cortex\Contracts\ModelLifecycleEvent;
use JayI\Cortex\Domains\ConcreteAgent\Actions\CreateConcreteAgentVersionAction;
use JayI\Cortex\Domains\ConcreteAgent\Actions\DeleteConcreteAgentOverrideAction;
use JayI\Cortex\Domains\ConcreteAgent\Actions\ListConcreteAgentsAction;
use JayI\Cortex\Domains\ConcreteAgent\Actions\ListConcreteAgentVersionsAction;
use JayI\Cortex\Domains\ConcreteAgent\Actions\PublishConcreteAgentVersionAction;
use JayI\Cortex\Domains\ConcreteAgent\Actions\RunConcreteAgentAction;
use JayI\Cortex\Domains\ConcreteAgent\Actions\ShowConcreteAgentAction;
use JayI\Cortex\Domains\ConcreteAgent\Actions\UpdateConcreteAgentToolsAction;
use JayI\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use JayI\Cortex\Domains\McpServer\Actions\CreateMcpInstructionVersionAction;
use JayI\Cortex\Domains\McpServer\Actions\DeleteMcpInstructionAction;
use JayI\Cortex\Domains\McpServer\Actions\ListMcpInstructionVersionsAction;
use JayI\Cortex\Domains\McpServer\Actions\ListMcpServersAction;
use JayI\Cortex\Domains\McpServer\Actions\PublishMcpInstructionVersionAction;
use JayI\Cortex\Domains\McpServer\Actions\ShowMcpInstructionAction;
use JayI\Cortex\Domains\Tool\Actions\CreateToolDescriptionVersionAction;
use JayI\Cortex\Domains\Tool\Actions\DeleteToolDescriptionAction;
use JayI\Cortex\Domains\Tool\Actions\ListToolDescriptionVersionsAction;
use JayI\Cortex\Domains\Tool\Actions\ListToolsAction;
use JayI\Cortex\Domains\Tool\Actions\PublishToolDescriptionVersionAction;
use JayI\Cortex\Domains\Tool\Actions\ShowToolDescriptionAction;
use JayI\Cortex\Domains\VirtualAgent\Actions\CreateVirtualAgentAction;
use JayI\Cortex\Domains\VirtualAgent\Actions\CreateVirtualAgentVersionAction;
use JayI\Cortex\Domains\VirtualAgent\Actions\DeleteVirtualAgentAction;
use JayI\Cortex\Domains\VirtualAgent\Actions\ListProvidersAction;
use JayI\Cortex\Domains\VirtualAgent\Actions\ListVirtualAgentsAction;
use JayI\Cortex\Domains\VirtualAgent\Actions\ListVirtualAgentVersionsAction;
use JayI\Cortex\Domains\VirtualAgent\Actions\PublishVirtualAgentVersionAction;
use JayI\Cortex\Domains\VirtualAgent\Actions\RunVirtualAgentAction;
use JayI\Cortex\Domains\VirtualAgent\Actions\ShowVirtualAgentAction;
use JayI\Cortex\Domains\VirtualAgent\Actions\ShowVirtualAgentVersionAction;
use JayI\Cortex\Domains\VirtualAgent\Actions\UpdateVirtualAgentAction;
use JayI\Cortex\Domains\VirtualAgent\Events\VirtualAgentCreatedActionEvent;
use JayI\Cortex\Domains\VirtualAgent\Events\VirtualAgentCreatingActionEvent;
use JayI\Cortex\Domains\VirtualAgent\Events\VirtualAgentCreatingEvent;
use JayI\Cortex\Domains\VirtualAgent\Events\VirtualAgentRanActionEvent;
use JayI\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionCreatedEvent;
use JayI\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionPublishedActionEvent;
use JayI\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionPublishingActionEvent;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Cortex\Domains\VirtualAgent\Support\DbAgent;
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

    $agent = VirtualAgentModel::factory()->create();
    $agent->name = 'Renamed';
    $agent->save();
    VirtualAgentModel::query()->find($agent->id);
    $agent->replicate();
    $agent->delete();

    $hooks = collect($seen)
        ->filter(fn (ModelLifecycleEvent $event): bool => $event->model() instanceof VirtualAgentModel)
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
        'VirtualAgentModel.creating', 'VirtualAgentModel.created', 'VirtualAgentModel.updated', 'VirtualAgentVersionModel.created',
        'ToolDescriptionModel.created', 'ToolDescriptionModel.updated', 'ToolDescriptionVersionModel.created',
        'McpInstructionModel.created', 'McpInstructionModel.updated', 'McpInstructionVersionModel.created',
        'ConcreteAgentOverrideModel.created', 'ConcreteAgentOverrideModel.updated', 'ConcreteAgentOverrideVersionModel.created',
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

    $agent = new VirtualAgentModel(['name' => 'Helper', 'slug' => 'helper']);

    expect($agent->save())->toBeFalse()
        ->and(VirtualAgentModel::query()->count())->toBe(0);
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
    $actions = glob(dirname(__DIR__, 2).'/src/Domains/*/Actions/*Action.php') ?: [];

    $unpaired = [];

    foreach ($actions as $path) {
        $source = (string) file_get_contents($path);
        preg_match_all('/([A-Za-z]+ActionEvent)::dispatch/', $source, $matches);

        $kinds = array_map(
            fn (string $event): string => is_subclass_of('JayI\\Cortex\\Domains\\'.basename(dirname($path, 2)).'\\Events\\'.$event, ActionStartingEvent::class) ? 'start' : 'finish',
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
    $agent = VirtualAgentModel::factory()->published()->create();

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
        $agentsAtStart = VirtualAgentModel::query()->count();
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
    $agent = VirtualAgentModel::factory()->published()->create();

    $starts = recordEvents(VirtualAgentVersionPublishingActionEvent::class);
    $stops = recordEvents(VirtualAgentVersionPublishedActionEvent::class);

    expect(fn (): mixed => app(PublishVirtualAgentVersionAction::class)->execute($agent, 9))
        ->toThrow(ModelNotFoundException::class);

    expect($starts)->toHaveCount(1)
        ->and($stops)->toHaveCount(0);
});
