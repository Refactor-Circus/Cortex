<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Services;

use Illuminate\Database\Eloquent\Collection;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Domains\VirtualAgent\Exceptions\CircularAgentReferenceException;
use RefactorCircus\Cortex\Domains\VirtualAgent\Exceptions\VirtualAgentNotPublishedException;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Support\DbAgent;
use RefactorCircus\Cortex\Support\PublicationCache;

final class AgentFactory
{
    public function __construct(
        private readonly ToolRegistry $registry,
        private readonly AgentRegistry $agents,
        private readonly PublicationCache $cache,
    ) {}

    /**
     * Build the agent with its whole sub-agent graph. The graph is loaded
     * breadth-first (one pivot query per level) and every virtual agent is
     * built once, so a sub-agent shared by several parents is reused rather
     * than rebuilt for each path that reaches it.
     *
     * @param  list<string>  $visited
     */
    public function make(VirtualAgentModel $agent, array $visited = []): DbAgent
    {
        $nodes = $this->graph($agent);
        $built = [];

        return $this->build($agent, $visited, $nodes, $built);
    }

    /**
     * @param  list<string>  $visited  the agents on the path to this one
     * @param  array<string, VirtualAgentModel>  $nodes
     * @param  array<string, DbAgent>  $built
     */
    private function build(VirtualAgentModel $agent, array $visited, array $nodes, array &$built): DbAgent
    {
        $key = (string) $agent->getKey();

        if (in_array($key, $visited, true)) {
            throw CircularAgentReferenceException::forAgent($agent);
        }

        if (isset($built[$key])) {
            return $built[$key];
        }

        $visited[] = $key;
        $agent = $nodes[$key] ?? $agent;

        $tools = array_map(
            fn (string $name) => $this->registry->get($name),
            $agent->tools,
        );

        foreach ($agent->subAgents as $subAgent) {
            $tools[] = $this->build($nodes[(string) $subAgent->getKey()] ?? $subAgent, $visited, $nodes, $built);
        }

        // Concrete agents cannot reference virtual ones, so they never close
        // a cycle and need no visited tracking.
        foreach ($agent->concrete_sub_agents as $name) {
            $tools[] = $this->agents->make($name);
        }

        return $built[$key] = new DbAgent(
            agentInstructions: $this->instructions($agent, $nodes),
            agentTools: $tools,
            agentProvider: $agent->provider,
            agentModel: $agent->model,
            settings: $agent->settings ?? [],
            agentName: $agent->slug,
            agentDescription: $agent->description,
        );
    }

    /**
     * Every virtual agent reachable from the root, keyed by id, each with
     * its sub-agents loaded.
     *
     * @return array<string, VirtualAgentModel>
     */
    private function graph(VirtualAgentModel $root): array
    {
        $nodes = [(string) $root->getKey() => $root];
        $level = [$root];

        while ($level !== []) {
            $pending = array_filter($level, fn (VirtualAgentModel $model): bool => ! $model->relationLoaded('subAgents'));

            if ($pending !== []) {
                (new Collection($pending))->load('subAgents');
            }

            $next = [];

            foreach ($level as $model) {
                foreach ($model->subAgents as $subAgent) {
                    $key = (string) $subAgent->getKey();

                    if (! isset($nodes[$key])) {
                        $nodes[$key] = $subAgent;
                        $next[] = $subAgent;
                    }
                }
            }

            $level = $next;
        }

        return $nodes;
    }

    /**
     * @param  array<string, VirtualAgentModel>  $nodes
     */
    private function instructions(VirtualAgentModel $agent, array $nodes): string
    {
        // Cached until a new version is published; the publishing actions
        // invalidate the key. Unpublished agents resolve to null, which is
        // never cached, so publishing takes effect immediately. The first
        // miss loads the published versions of the whole graph in one query.
        $published = $this->cache->remember(
            $this->cache->virtualAgentKey((string) $agent->getKey()),
            function () use ($agent, $nodes): ?string {
                $pending = array_values(array_filter($nodes, fn (VirtualAgentModel $model): bool => ! $model->relationLoaded('publishedVersion')));

                if ($pending !== []) {
                    (new Collection($pending))->load('publishedVersion');
                }

                return $agent->publishedVersion?->content;
            },
        );

        if (! is_string($published)) {
            throw VirtualAgentNotPublishedException::forAgent($agent);
        }

        return $published;
    }
}
