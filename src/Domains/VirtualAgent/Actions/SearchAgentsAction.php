<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Actions;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\SearchAgentsData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\AgentsSearchedActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\AgentsSearchingActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Keystone\Actions\Action;

final class SearchAgentsAction extends Action
{
    /**
     * How many agents of each kind a search returns.
     */
    private const int LIMIT = 5;

    /**
     * The LIKE escape character the queries name: it works the same on
     * SQLite, MySQL and PostgreSQL, unlike a backslash.
     */
    private const string ESCAPE = '!';

    public function __construct(private readonly AgentRegistry $agents) {}

    /**
     * Search virtual agents (by name or slug) and registered concrete agents
     * (by name, case-insensitively) for the caller: each kind only when they
     * may list it, each result only when they may view it.
     *
     * @return list<array{type: string, key: string, name: string}>
     */
    protected function handle(SearchAgentsData $data, ?Authenticatable $user = null): array
    {
        AgentsSearchingActionEvent::dispatch($data, $user);

        $result = $this->perform($data, Gate::forUser($user));

        AgentsSearchedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return list<array{type: string, key: string, name: string}>
     */
    private function perform(SearchAgentsData $data, GateContract $gate): array
    {
        return [...$this->virtualAgents($data->query, $gate), ...$this->concreteAgents($data->query, $gate)];
    }

    /**
     * Up to five virtual agents whose name or slug contains the query, with
     * LIKE wildcards in it matched literally, ordered by name.
     *
     * @return list<array{type: string, key: string, name: string}>
     */
    private function virtualAgents(string $query, GateContract $gate): array
    {
        if (! $gate->allows('viewAny', VirtualAgentModel::class)) {
            return [];
        }

        $pattern = '%'.str_replace([self::ESCAPE, '%', '_'], [self::ESCAPE.self::ESCAPE, self::ESCAPE.'%', self::ESCAPE.'_'], $query).'%';

        return array_values(VirtualAgentModel::query()
            ->where(fn (Builder $builder): Builder => $builder
                ->whereRaw("name like ? escape '!'", [$pattern])
                ->orWhereRaw("slug like ? escape '!'", [$pattern]))
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get()
            ->filter(fn (VirtualAgentModel $agent): bool => $gate->allows('view', $agent))
            ->map(fn (VirtualAgentModel $agent): array => [
                'type' => 'virtual_agent',
                'key' => (string) $agent->slug,
                'name' => (string) $agent->name,
            ])
            ->all());
    }

    /**
     * Up to five registered concrete agents whose name contains the query,
     * case-insensitively, ordered by name. Each is checked against its saved
     * override, all fetched in one query, or an unsaved one when it has none.
     *
     * @return list<array{type: string, key: string, name: string}>
     */
    private function concreteAgents(string $query, GateContract $gate): array
    {
        if (! $gate->allows('viewAny', ConcreteAgentOverrideModel::class)) {
            return [];
        }

        $needle = mb_strtolower($query);
        $names = array_values(array_filter(
            $this->agents->names(),
            fn (string $name): bool => str_contains(mb_strtolower($name), $needle),
        ));
        sort($names, SORT_STRING);
        $names = array_slice($names, 0, self::LIMIT);

        if ($names === []) {
            return [];
        }

        $overrides = ConcreteAgentOverrideModel::query()->whereIn('agent', $names)->get()->keyBy('agent');

        return array_values(array_map(
            fn (string $name): array => ['type' => 'concrete_agent', 'key' => $name, 'name' => $name],
            array_filter(
                $names,
                fn (string $name): bool => $gate->allows('view', $overrides->get($name) ?? new ConcreteAgentOverrideModel(['agent' => $name])),
            ),
        ));
    }
}
