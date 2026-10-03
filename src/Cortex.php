<?php

declare(strict_types=1);

namespace JayI\Cortex;

use JayI\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use JayI\Cortex\Domains\McpServer\Services\McpServerRegistry;
use JayI\Cortex\Domains\Tool\Services\ToolRegistry;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Cortex\Domains\VirtualAgent\Services\AgentFactory;
use JayI\Cortex\Domains\VirtualAgent\Support\DbAgent;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Responses\AgentResponse;

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
