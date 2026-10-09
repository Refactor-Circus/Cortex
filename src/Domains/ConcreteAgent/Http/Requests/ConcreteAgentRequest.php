<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests;

use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Http\Request;

abstract class ConcreteAgentRequest extends Request
{
    /**
     * The registered agent name from the route, verified against the registry.
     */
    protected function agentName(): string
    {
        $agent = $this->route('agent');

        if (! is_string($agent) || ! app(AgentRegistry::class)->has($agent)) {
            abort(404);
        }

        return $agent;
    }

    protected function override(): ConcreteAgentOverrideModel
    {
        $override = ConcreteAgentOverrideModel::query()->where('agent', $this->agentName())->first();

        if ($override === null) {
            abort(404);
        }

        return $override;
    }

    /**
     * The prompt version named in the route.
     */
    protected function version(): ConcreteAgentOverrideVersionModel
    {
        /** @var ConcreteAgentOverrideVersionModel */
        return $this->override()->versions()->where('version', (int) $this->route('version'))->firstOrFail();
    }

    /**
     * The override for the agent, or an unsaved one when none exists yet, so
     * checks on an agent without overrides go through the same policy.
     */
    protected function overrideOrNew(): ConcreteAgentOverrideModel
    {
        return ConcreteAgentOverrideModel::query()->firstOrNew(['agent' => $this->agentName()]);
    }
}
