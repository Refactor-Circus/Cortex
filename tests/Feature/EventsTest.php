<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\CreateConcreteAgentVersionAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\DeleteConcreteAgentOverrideAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\ListConcreteAgentsAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\ListConcreteAgentVersionsAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\PublishConcreteAgentVersionAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\RunConcreteAgentAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\ShowConcreteAgentAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\UpdateConcreteAgentToolsAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Domains\McpServer\Actions\CreateMcpInstructionVersionAction;
use RefactorCircus\Cortex\Domains\McpServer\Actions\DeleteMcpInstructionAction;
use RefactorCircus\Cortex\Domains\McpServer\Actions\ListMcpInstructionVersionsAction;
use RefactorCircus\Cortex\Domains\McpServer\Actions\ListMcpServersAction;
use RefactorCircus\Cortex\Domains\McpServer\Actions\PublishMcpInstructionVersionAction;
use RefactorCircus\Cortex\Domains\McpServer\Actions\ShowMcpInstructionAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\CreateRedirectDomainAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\DeleteRedirectDomainAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\ListRedirectDomainsAction;
use RefactorCircus\Cortex\Domains\Tool\Actions\CreateToolDescriptionVersionAction;
use RefactorCircus\Cortex\Domains\Tool\Actions\DeleteToolDescriptionAction;
use RefactorCircus\Cortex\Domains\Tool\Actions\ListToolDescriptionVersionsAction;
use RefactorCircus\Cortex\Domains\Tool\Actions\ListToolsAction;
use RefactorCircus\Cortex\Domains\Tool\Actions\PublishToolDescriptionVersionAction;
use RefactorCircus\Cortex\Domains\Tool\Actions\ShowToolDescriptionAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\CreateVirtualAgentAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\CreateVirtualAgentVersionAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\DeleteVirtualAgentAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ListProvidersAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ListVirtualAgentsAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ListVirtualAgentVersionsAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\PublishVirtualAgentVersionAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\RunVirtualAgentAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ShowVirtualAgentAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ShowVirtualAgentVersionAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\UpdateVirtualAgentAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentCreatedActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentCreatingActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentCreatingEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentRanActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionCreatedEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionPublishedActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\VirtualAgentVersionPublishingActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Support\DbAgent;
use RefactorCircus\Cortex\Tests\Fixtures\EchoAgent;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;

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

    $domain = app(CreateRedirectDomainAction::class)->execute(['domain' => 'claude.ai']);
    app(ListRedirectDomainsAction::class)->execute();
    app(DeleteRedirectDomainAction::class)->execute($domain);

    app(ListToolsAction::class)->execute();
    app(ListMcpServersAction::class)->execute();
    app(ListProvidersAction::class)->execute();

    $started = collect($starts)->map(fn (object $event): string => class_basename($event))->unique();
    $finished = collect($stops)->map(fn (object $event): string => class_basename($event))->unique();

    expect($started)->toHaveCount(34)
        ->and($finished)->toHaveCount(34)
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
            fn (string $event): string => is_subclass_of('RefactorCircus\\Cortex\\Domains\\'.basename(dirname($path, 2)).'\\Events\\'.$event, ActionStartingEvent::class) ? 'start' : 'finish',
            $matches[1],
        );

        sort($kinds);

        if ($kinds !== ['finish', 'start']) {
            $unpaired[] = basename($path, '.php');
        }
    }

    expect($actions)->toHaveCount(34)
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
