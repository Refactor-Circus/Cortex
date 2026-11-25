<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Actions;

use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\SettingsSummaryShowingActionEvent;
use RefactorCircus\Cortex\Domains\VirtualAgent\Events\SettingsSummaryShownActionEvent;
use RefactorCircus\Keystone\Actions\Action;

final class ShowSettingsSummaryAction extends Action
{
    public function __construct(
        private readonly ToolRegistry $tools,
        private readonly AgentRegistry $agents,
        private readonly McpServerRegistry $servers,
    ) {}

    /**
     * Summarize Cortex's settings: the providers configured in
     * `cortex.providers`, how many tools, concrete agents and MCP servers
     * are registered, and whether the cache is enabled.
     *
     * @return array{providers: list<array{name: string, models: list<string>}>, tool_count: int, agent_count: int, server_count: int, cache_enabled: bool}
     */
    protected function handle(): array
    {
        SettingsSummaryShowingActionEvent::dispatch();

        $result = [
            'providers' => $this->providers(),
            'tool_count' => count($this->tools->names()),
            'agent_count' => count($this->agents->names()),
            'server_count' => count($this->servers->names()),
            'cache_enabled' => (bool) config('cortex.cache.enabled', true),
        ];

        SettingsSummaryShownActionEvent::dispatch($result);

        return $result;
    }

    /**
     * The `cortex.providers` config (provider name => model names), or none
     * when it is unset.
     *
     * @return list<array{name: string, models: list<string>}>
     */
    private function providers(): array
    {
        $providers = [];

        foreach ((array) config('cortex.providers', []) as $name => $models) {
            $providers[] = [
                'name' => (string) $name,
                'models' => array_values(array_map(strval(...), (array) $models)),
            ];
        }

        return $providers;
    }
}
