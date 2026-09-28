<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Requests;

use JayI\Cortex\Agents\AgentRegistry;
use JayI\Cortex\Http\Request;
use JayI\Cortex\Models\ConcreteAgentOverride;
use JayI\Cortex\Models\ConcreteAgentOverrideVersion;

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

    protected function override(): ConcreteAgentOverride
    {
        $override = ConcreteAgentOverride::query()->where('agent', $this->agentName())->first();

        if ($override === null) {
            abort(404);
        }

        return $override;
    }

    /**
     * The prompt version named in the route.
     */
    protected function version(): ConcreteAgentOverrideVersion
    {
        /** @var ConcreteAgentOverrideVersion */
        return $this->override()->versions()->where('version', (int) $this->route('version'))->firstOrFail();
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
