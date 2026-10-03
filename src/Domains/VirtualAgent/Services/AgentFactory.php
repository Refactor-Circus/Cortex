<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Services;

use JayI\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use JayI\Cortex\Domains\Tool\Services\ToolRegistry;
use JayI\Cortex\Domains\VirtualAgent\Exceptions\CircularAgentReferenceException;
use JayI\Cortex\Domains\VirtualAgent\Exceptions\VirtualAgentNotPublishedException;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Cortex\Domains\VirtualAgent\Support\DbAgent;
use JayI\Cortex\Support\PublicationCache;

final class AgentFactory
{
    public function __construct(
        private readonly ToolRegistry $registry,
        private readonly AgentRegistry $agents,
        private readonly PublicationCache $cache,
    ) {}

    /**
     * @param  list<string>  $visited
     */
    public function make(VirtualAgentModel $agent, array $visited = []): DbAgent
    {
        if (in_array((string) $agent->getKey(), $visited, true)) {
            throw CircularAgentReferenceException::forAgent($agent);
        }

        $visited[] = (string) $agent->getKey();

        $tools = array_map(
            fn (string $name) => $this->registry->get($name),
            $agent->tools,
        );

        foreach ($agent->subAgents as $subAgent) {
            $tools[] = $this->make($subAgent, $visited);
        }

        // Concrete agents cannot reference virtual ones, so they never close
        // a cycle and need no visited tracking.
        foreach ($agent->concrete_sub_agents as $name) {
            $tools[] = $this->agents->make($name);
        }

        return new DbAgent(
            agentInstructions: $this->instructions($agent),
            agentTools: $tools,
            agentProvider: $agent->provider,
            agentModel: $agent->model,
            settings: $agent->settings ?? [],
            agentName: $agent->slug,
            agentDescription: $agent->description,
        );
    }

    private function instructions(VirtualAgentModel $agent): string
    {
        // Cached until a new version is published; the publishing actions
        // invalidate the key. Unpublished agents resolve to null, which is
        // never cached, so publishing takes effect immediately.
        $published = $this->cache->remember(
            $this->cache->virtualAgentKey((string) $agent->getKey()),
            fn (): ?string => $agent->publishedVersion?->content,
        );

        if (! is_string($published)) {
            throw VirtualAgentNotPublishedException::forAgent($agent);
        }

        return $published;
    }
}
