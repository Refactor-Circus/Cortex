<?php

declare(strict_types=1);

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Cortex\Atrium\CortexPlugin;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\CreateConcreteAgentVersionAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Data\CreateConcreteAgentVersionData;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Domains\McpServer\Actions\CreateMcpInstructionVersionAction;
use RefactorCircus\Cortex\Domains\McpServer\Data\CreateMcpInstructionVersionData;
use RefactorCircus\Cortex\Domains\RedirectDomain\Events\RedirectDomainsListingActionEvent;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\Tool\Actions\CreateToolDescriptionVersionAction;
use RefactorCircus\Cortex\Domains\Tool\Data\CreateToolDescriptionVersionData;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\CreateVirtualAgentVersionAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\UpdateVirtualAgentAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\CreateVirtualAgentVersionData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\UpdateVirtualAgentData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Services\AgentFactory;
use RefactorCircus\Cortex\Domains\VirtualAgent\Support\DbAgent;
use RefactorCircus\Cortex\Support\Tables;
use RefactorCircus\Cortex\Tests\Fixtures\EchoAgent;
use RefactorCircus\Cortex\Tests\Fixtures\EchoTool;
use RefactorCircus\Cortex\Tests\Fixtures\PlainAgent;

/**
 * The SQL statements the callback runs, lower-cased.
 *
 * @return list<string>
 */
function statements(Closure $callback): array
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    try {
        $callback();
    } finally {
        DB::disableQueryLog();
    }

    return array_map(fn (array $query): string => strtolower((string) $query['query']), DB::getQueryLog());
}

/**
 * A layered graph of published virtual agents: `$width` agents per level,
 * each linked to every agent on the next level, under one root. The paths
 * through it grow as width^depth.
 */
function layeredAgents(int $depth, int $width): VirtualAgentModel
{
    $root = VirtualAgentModel::factory()->published()->create(['slug' => 'root']);
    $above = [$root];

    for ($level = 1; $level <= $depth; $level++) {
        $current = [];

        for ($i = 0; $i < $width; $i++) {
            $current[] = VirtualAgentModel::factory()->published()->create(['slug' => "level-{$level}-{$i}"]);
        }

        foreach ($above as $parent) {
            $parent->subAgents()->attach(array_map(fn (VirtualAgentModel $agent): string => (string) $agent->getKey(), $current));
        }

        $above = $current;
    }

    return $root;
}

/**
 * Run the callback with SQLite rendering row locks as a marker comment, so
 * the statements a lock would reach on PostgreSQL or MySQL can be inspected.
 *
 * @return list<string>
 */
function lockedStatements(Closure $callback): array
{
    $connection = DB::connection();
    $original = $connection->getQueryGrammar();

    $connection->setQueryGrammar(new class($connection) extends SQLiteGrammar
    {
        protected function compileLock(Builder $query, $value): string
        {
            return $value === false ? '' : '/* for update */';
        }
    });

    $statements = [];
    $connection->listen(function ($query) use (&$statements): void {
        $statements[] = strtolower($query->sql);
    });

    try {
        $callback();
    } finally {
        $connection->setQueryGrammar($original);
    }

    return array_values(array_filter($statements, fn (string $sql): bool => str_contains($sql, 'for update')));
}

it('locks the parent row, never an aggregate, when numbering versions', function (Closure $create) {
    $create();
    $locked = lockedStatements($create);

    expect($locked)->not->toBeEmpty();

    foreach ($locked as $sql) {
        expect($sql)->not->toMatch('/\b(max|min|count|sum|avg)\s*\(/');
    }
})->with([
    'virtual agent' => [function (): void {
        $agent = VirtualAgentModel::query()->firstWhere('slug', 'locked') ?? VirtualAgentModel::factory()->create(['slug' => 'locked']);
        app(CreateVirtualAgentVersionAction::class)->execute($agent, new CreateVirtualAgentVersionData(content: 'Next.'));
    }],
    'concrete agent' => [fn () => app(CreateConcreteAgentVersionAction::class)->execute('echo-agent', new CreateConcreteAgentVersionData(content: 'Next.'))],
    'tool description' => [fn () => app(CreateToolDescriptionVersionAction::class)->execute('echo', new CreateToolDescriptionVersionData(content: 'Next.'))],
    'mcp instruction' => [fn () => app(CreateMcpInstructionVersionAction::class)->execute('echo-server', new CreateMcpInstructionVersionData(content: 'Next.'))],
]);

it('builds a shared sub-agent once, loading the graph a level at a time', function () {
    config(['cortex.cache.enabled' => false]);
    $root = layeredAgents(depth: 5, width: 2);
    $root = VirtualAgentModel::query()->findOrFail($root->getKey());

    $agent = null;
    $queries = statements(function () use ($root, &$agent): void {
        $agent = app(AgentFactory::class)->make($root);
    });

    $pivot = Tables::name('virtual_agent_sub_agents');

    // One pivot query per level (the last finds no children) and one for
    // every published version, however many paths reach each agent.
    expect(count(array_filter($queries, fn (string $sql): bool => str_contains($sql, $pivot))))->toBe(6)
        ->and(count($queries))->toBe(7);

    /** @var DbAgent $agent */
    $tools = [...$agent->tools()];
    expect($tools)->toHaveCount(2);

    $left = [...$tools[0]->tools()];
    $right = [...$tools[1]->tools()];

    expect($left[0])->toBe($right[0])
        ->and($agent->instructions())->toBe('You are a helpful assistant.');
});

it('builds each concrete agent once when listing or showing it', function () {
    app(AgentRegistry::class)->register('echo-agent', EchoAgent::class);
    app(AgentRegistry::class)->register('plain-agent', PlainAgent::class);

    $made = 0;
    app()->resolving(EchoAgent::class, function () use (&$made): void {
        $made++;
    });
    app()->resolving(PlainAgent::class, function () use (&$made): void {
        $made++;
    });

    $all = app(AgentRegistry::class)->all();
    expect($made)->toBe(2)->and($all)->toHaveCount(2);

    $made = 0;
    $shown = app(AgentRegistry::class)->describe('echo-agent');
    expect($made)->toBe(1)
        ->and($shown['default_instructions'])->toBe('Echo everything back.')
        ->and($shown['default_tools'])->toBe(['EchoTool']);
});

it('lists tool names and tags without building any tool', function () {
    app(ToolRegistry::class)->register('echo', EchoTool::class, ['demo']);

    $made = 0;
    app()->resolving(EchoTool::class, function () use (&$made): void {
        $made++;
    });

    expect(app(ToolRegistry::class)->summaries())->toBe([['name' => 'echo', 'class' => EchoTool::class, 'tags' => ['demo']]])
        ->and(app(ToolRegistry::class)->summaries('other'))->toBe([])
        ->and($made)->toBe(0);
});

it('pages stored redirect domains with their owners in a fixed number of queries', function () {
    app()->detectEnvironment(fn (): string => 'local');

    $owned = function (int $count): void {
        foreach (VirtualAgentModel::factory()->count($count)->create() as $owner) {
            RedirectDomainModel::factory()->create(['owner_type' => $owner->getMorphClass(), 'owner_id' => $owner->getKey()]);
        }
    };

    $owned(3);
    $few = count(statements(fn () => $this->get(route('atrium.cortex.redirect-domains.index'))->assertOk()));

    $owned(40);
    $many = count(statements(fn () => $this->get(route('atrium.cortex.redirect-domains.index'))->assertOk()));

    expect($many)->toBe($few);

    $filters = null;
    Event::listen(RedirectDomainsListingActionEvent::class, function (RedirectDomainsListingActionEvent $event) use (&$filters): void {
        $filters = $event->filters;
    });

    $this->get(route('atrium.cortex.redirect-domains.index', ['source' => 'owned']))->assertOk();

    expect($filters?->paged)->toBeTrue()->and($filters?->source)->toBe('owned');

    $this->get(route('atrium.cortex.redirect-domains.index', ['source' => 'owned', 'page' => 2]))
        ->assertOk()
        ->assertSee('data-testid="redirect-domain-owned"', false);
});

it('lists virtual agents on the dashboard without their prompts or sub-agents', function () {
    app()->detectEnvironment(fn (): string => 'local');
    $agent = VirtualAgentModel::factory()->published('A long prompt.')->create();
    $agent->subAgents()->attach(VirtualAgentModel::factory()->create()->getKey());

    $queries = statements(fn () => $this->get(route('atrium.cortex.virtual-agents.index'))->assertOk()->assertSee('v1'));

    $pivot = Tables::name('virtual_agent_sub_agents');
    $versions = Tables::name('virtual_agent_versions');

    expect(array_filter($queries, fn (string $sql): bool => str_contains($sql, $pivot)))->toBe([])
        ->and(array_filter($queries, fn (string $sql): bool => str_contains($sql, $versions) && str_contains($sql, 'content')))->toBe([])
        ->and(array_filter($queries, fn (string $sql): bool => str_contains($sql, '"'.$versions.'".*') || str_contains($sql, 'select * from "'.$versions.'"')))->toBe([]);
});

it('checks sub-agent slugs and cycles a query at a time, not one per slug or agent', function () {
    $chain = layeredAgents(depth: 4, width: 1);
    $slugs = VirtualAgentModel::factory()->count(5)->create()->pluck('slug')->all();
    $target = VirtualAgentModel::factory()->create(['slug' => 'target']);

    $queries = statements(function () use ($target, $slugs): void {
        $data = UpdateVirtualAgentData::from(['sub_agents' => [...$slugs, 'root']], $target);
        app(UpdateVirtualAgentAction::class)->execute($target, $data);
    });

    $pivot = Tables::name('virtual_agent_sub_agents');
    $agents = Tables::name('virtual_agents');

    // Validation looks the slugs up together, and the cycle check walks the
    // chain under `root` one level per query.
    expect(array_filter($queries, fn (string $sql): bool => str_contains($sql, 'count(*)') && str_contains($sql, '"'.$agents.'"')))->toBe([])
        ->and(count(array_filter($queries, fn (string $sql): bool => str_contains($sql, 'from "'.$pivot.'" where "virtual_agent_id" in'))))->toBe(5)
        ->and($target->refresh()->subAgents)->toHaveCount(6)
        ->and($chain->exists)->toBeTrue();

    expect(fn () => UpdateVirtualAgentData::from(['sub_agents' => ['missing', $slugs[0]]], $target))
        ->toThrow(ValidationException::class);
});

it('checks concrete agent search results against their overrides in one query', function () {
    app(AgentRegistry::class)->register('echo-agent', EchoAgent::class);
    app(AgentRegistry::class)->register('echo-agent-two', EchoAgent::class);

    $source = app(CortexPlugin::class)->search();

    $results = [];
    $queries = statements(function () use ($source, &$results): void {
        $results = array_map(fn ($result): string => $result->title, $source->results('echo'));
    });

    expect($results)->toBe(['echo-agent', 'echo-agent-two'])
        ->and(count(array_filter($queries, fn (string $sql): bool => str_contains($sql, Tables::name('concrete_agent_overrides')))))->toBe(1);
});
