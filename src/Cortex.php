<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Responses\AgentResponse;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Domains\McpServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Services\AgentFactory;
use RefactorCircus\Cortex\Domains\VirtualAgent\Support\DbAgent;

class Cortex
{
    public function __construct(
        private readonly ToolRegistry $tools,
        private readonly McpServerRegistry $servers,
        private readonly AgentRegistry $agents,
        private readonly AgentFactory $factory,
    ) {}

    public function tools(): ToolRegistry
    {
        return $this->tools;
    }

    public function servers(): McpServerRegistry
    {
        return $this->servers;
    }

    public function agents(): AgentRegistry
    {
        return $this->agents;
    }

    public function virtualAgent(string $slug): DbAgent
    {
        return $this->factory->make(
            VirtualAgentModel::query()->where('slug', $slug)->firstOrFail(),
        );
    }

    public function concreteAgent(string $name): Agent
    {
        return $this->agents->make($name);
    }

    public function runVirtualAgent(string $slug, string $input): AgentResponse
    {
        return $this->virtualAgent($slug)->prompt($input);
    }

    public function runConcreteAgent(string $name, string $input): AgentResponse
    {
        return $this->concreteAgent($name)->prompt($input);
    }
}
