<?php

declare(strict_types=1);

namespace JayI\Cortex\Mcp\Requests;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use JayI\Cortex\Agents\AgentRegistry;
use JayI\Cortex\Mcp\Request;
use JayI\Cortex\Models\ConcreteAgentOverride;
use JayI\Cortex\Models\ConcreteAgentOverrideVersion;

abstract class ConcreteAgentMcpRequest extends Request
{
    private ?ConcreteAgentOverride $override = null;

    /**
     * The registered agent name from the tool input, verified against the
     * registry. Unknown names surface as the base request's not-found error.
     */
    protected function agentName(): string
    {
        $agent = (string) $this->get('agent');

        if (! app(AgentRegistry::class)->has($agent)) {
            throw (new ModelNotFoundException)->setModel(ConcreteAgentOverride::class);
        }

        return $agent;
    }

    protected function override(): ConcreteAgentOverride
    {
        return $this->override ??= ConcreteAgentOverride::query()
            ->where('agent', $this->agentName())
            ->firstOrFail();
    }

    /**
     * The prompt version named in the input.
     */
    protected function version(): ConcreteAgentOverrideVersion
    {
        /** @var ConcreteAgentOverrideVersion */
        return $this->override()->versions()->where('version', (int) $this->get('version'))->firstOrFail();
    }

    /**
     * The override for the agent, or an unsaved one when none exists yet, so
     * checks on an agent without overrides go through the same policy.
     */
    protected function overrideOrNew(): ConcreteAgentOverride
    {
        return ConcreteAgentOverride::query()->firstOrNew(['agent' => $this->agentName()]);
    }
}
