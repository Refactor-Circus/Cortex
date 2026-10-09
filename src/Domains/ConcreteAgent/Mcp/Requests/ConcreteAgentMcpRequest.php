<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Requests;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Mcp\Request;

abstract class ConcreteAgentMcpRequest extends Request
{
    private ?ConcreteAgentOverrideModel $override = null;

    /**
     * The registered agent name from the tool input, verified against the
     * registry. Unknown names surface as the base request's not-found error.
     */
    protected function agentName(): string
    {
        $agent = (string) $this->get('agent');

        if (! app(AgentRegistry::class)->has($agent)) {
            throw (new ModelNotFoundException)->setModel(ConcreteAgentOverrideModel::class);
        }

        return $agent;
    }

    protected function override(): ConcreteAgentOverrideModel
    {
        return $this->override ??= ConcreteAgentOverrideModel::query()
            ->where('agent', $this->agentName())
            ->firstOrFail();
    }

    /**
     * The prompt version named in the input.
     */
    protected function version(): ConcreteAgentOverrideVersionModel
    {
        /** @var ConcreteAgentOverrideVersionModel */
        return $this->override()->versions()->where('version', (int) $this->get('version'))->firstOrFail();
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
