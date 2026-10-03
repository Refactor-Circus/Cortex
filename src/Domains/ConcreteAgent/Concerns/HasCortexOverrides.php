<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Concerns;

use JayI\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use JayI\Cortex\Domains\ConcreteAgent\Services\ConcreteAgentOverrides;
use JayI\Cortex\Domains\ConcreteAgent\Support\Agent;
use JayI\Cortex\Domains\ConcreteAgent\Support\LockedTools;
use Stringable;

/**
 * Serve the published Cortex prompt and toolset overrides, when they exist,
 * in place of the ones the agent declares in code. Falls back to
 * defaultInstructions() and defaultTools() when nothing is overridden or the
 * agent is not registered with Cortex. An agent marked
 * {@see LockedTools} always keeps its code toolset.
 *
 * Use directly on agents that cannot extend {@see Agent}.
 */
trait HasCortexOverrides
{
    /**
     * The prompt the agent declares in code.
     */
    abstract public function defaultInstructions(): Stringable|string;

    /**
     * The toolset the agent declares in code.
     *
     * @return iterable<int, mixed>
     */
    public function defaultTools(): iterable
    {
        return [];
    }

    public function instructions(): Stringable|string
    {
        $name = app(AgentRegistry::class)->nameFor(static::class);

        $override = $name === null ? null : app(ConcreteAgentOverrides::class)->instructionsFor($name);

        return $override ?? $this->defaultInstructions();
    }

    /**
     * @return iterable<int, mixed>
     */
    public function tools(): iterable
    {
        $registry = app(AgentRegistry::class);

        $name = $registry->nameFor(static::class);

        $selected = $name === null || AgentRegistry::locksTools(static::class)
            ? null
            : app(ConcreteAgentOverrides::class)->toolsFor($name);

        $defaults = [...$this->defaultTools()];

        return $selected === null ? $defaults : $registry->resolveTools($defaults, $selected);
    }
}
