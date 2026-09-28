<?php

declare(strict_types=1);

namespace JayI\Cortex;

use JayI\Cortex\Agents\AgentRegistry;
use JayI\Cortex\Mcp\McpServerRegistry;
use JayI\Cortex\Models\VirtualAgent;
use JayI\Cortex\Runtime\AgentFactory;
use JayI\Cortex\Runtime\DbAgent;
use JayI\Cortex\Tools\ToolRegistry;
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
            VirtualAgent::query()->where('slug', $slug)->firstOrFail(),
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
