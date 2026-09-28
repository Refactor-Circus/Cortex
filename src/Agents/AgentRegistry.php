<?php

declare(strict_types=1);

namespace JayI\Cortex\Agents;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JayI\Cortex\Agents\Attributes\LockedTools;
use JayI\Cortex\Agents\Concerns\HasCortexOverrides;
use JayI\Cortex\Exceptions\AgentNotFoundException;
use JayI\Cortex\Tools\ToolName;
use JayI\Cortex\Tools\ToolRegistry;
use Laravel\Ai\Contracts\Agent as AgentContract;
use Laravel\Ai\Contracts\HasTools;
use ReflectionClass;

/**
 * Registry of concrete (class-based) agents. Registered agents are listed in
 * Cortex, can be run and attached to virtual agents as sub-agents, and — when
 * they use {@see HasCortexOverrides} — have their prompt and toolset
 * overridden from Cortex.
 */
final class AgentRegistry
{
    /**
     * @var array<string, class-string<AgentContract>>
     */
    private array $agents = [];

    private bool $configLoaded = false;

    public function __construct(
        private readonly Container $container,
        private readonly ToolRegistry $tools,
    ) {}

    public function register(string $name, string $class): void
    {
        if (! is_a($class, AgentContract::class, true)) {
            throw new InvalidArgumentException(
                sprintf('Agent [%s] must implement %s.', $class, AgentContract::class),
            );
        }

        $this->loadConfigAgents();

        $this->agents[$name] = $class;
    }

    public function has(string $name): bool
    {
        $this->loadConfigAgents();

        return array_key_exists($name, $this->agents);
    }

    /**
     * @return class-string<AgentContract>
     */
    public function get(string $name): string
    {
        if (! $this->has($name)) {
            throw AgentNotFoundException::forName($name);
        }

        return $this->agents[$name];
    }

    /**
     * Resolve a fresh instance of the agent from the container.
     */
    public function make(string $name): AgentContract
    {
        /** @var AgentContract */
        return $this->container->make($this->get($name));
    }

    /**
     * The registered name for an agent class, or null when unregistered.
     * A class registered under several names resolves to the first one.
     */
    public function nameFor(string $class): ?string
    {
        $this->loadConfigAgents();

        $name = array_search($class, $this->agents, true);

        return $name === false ? null : $name;
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        $this->loadConfigAgents();

        return array_keys($this->agents);
    }

    /**
     * Whether the agent reads its prompt and toolset overrides from Cortex.
     */
    public function supportsOverrides(string $name): bool
    {
        return in_array(HasCortexOverrides::class, class_uses_recursive($this->get($name)), true);
    }

    /**
     * Whether a toolset override from Cortex applies to the agent: it must
     * read overrides and not be marked {@see LockedTools}.
     */
    public function supportsToolOverrides(string $name): bool
    {
        return $this->supportsOverrides($name) && ! self::locksTools($this->get($name));
    }

    /**
     * Whether the class keeps the toolset it declares in code.
     *
     * @param  class-string  $class
     */
    public static function locksTools(string $class): bool
    {
        return (new ReflectionClass($class))->getAttributes(LockedTools::class) !== [];
    }

    /**
     * The prompt the agent declares in code, before any published override
     * is applied.
     */
    public function defaultInstructions(string $name): string
    {
        $agent = $this->make($name);

        return (string) (method_exists($agent, 'defaultInstructions') && $this->supportsOverrides($name)
            ? $agent->defaultInstructions()
            : $agent->instructions());
    }

    /**
     * The names of the tools the agent declares in code, before any
     * published override is applied.
     *
     * @return list<string>
     */
    public function defaultTools(string $name): array
    {
        $agent = $this->make($name);

        if (method_exists($agent, 'defaultTools') && $this->supportsOverrides($name)) {
            return $this->toolNames($agent->defaultTools());
        }

        return $agent instanceof HasTools ? $this->toolNames($agent->tools()) : [];
    }

    /**
     * The names of the tools the agent runs with: its toolset override when
     * one is saved and the agent honours it, otherwise its code toolset.
     * Override names are reported as stored — registered Cortex tools by
     * their registered name — minus any that no longer resolve.
     *
     * @return list<string>
     */
    public function effectiveTools(string $name): array
    {
        $defaults = $this->defaultTools($name);

        $selected = $this->supportsToolOverrides($name)
            ? $this->container->make(ConcreteAgentOverrides::class)->toolsFor($name)
            : null;

        if ($selected === null) {
            return $defaults;
        }

        return array_values(array_filter(
            $selected,
            fn (string $tool): bool => in_array($tool, $defaults, true) || $this->tools->has($tool),
        ));
    }

    /**
     * Build an overridden toolset: each selected name resolves to the agent's
     * own code tool of that name, otherwise to the registered Cortex tool.
     * Names that match neither (a tool since dropped from the class or the
     * registry) are skipped rather than failing the run.
     *
     * @param  array<int, mixed>  $defaults
     * @param  list<string>  $selected
     * @return list<mixed>
     */
    public function resolveTools(array $defaults, array $selected): array
    {
        $byName = [];

        foreach ($defaults as $tool) {
            $byName[ToolName::of($tool)] ??= $tool;
        }

        $resolved = [];

        foreach ($selected as $name) {
            if (array_key_exists($name, $byName)) {
                $resolved[] = $byName[$name];
            } elseif ($this->tools->has($name)) {
                $resolved[] = $this->tools->get($name);
            }
        }

        return $resolved;
    }

    /**
     * @return list<array{name: string, class: class-string<AgentContract>, overridable: bool, tools_overridable: bool, instructions: string, tools: list<string>, default_tools: list<string>}>
     */
    public function all(): array
    {
        return array_map(function (string $name): array {
            $agent = $this->make($name);

            return [
                'name' => $name,
                'class' => $this->agents[$name],
                'overridable' => $this->supportsOverrides($name),
                'tools_overridable' => $this->supportsToolOverrides($name),
                'instructions' => (string) $agent->instructions(),
                'tools' => $this->effectiveTools($name),
                'default_tools' => $this->defaultTools($name),
            ];
        }, $this->names());
    }

    /**
     * @param  iterable<int, mixed>  $tools
     * @return list<string>
     */
    private function toolNames(iterable $tools): array
    {
        $names = [];

        foreach ($tools as $tool) {
            $names[] = ToolName::of($tool);
        }

        return array_values(array_unique($names));
    }

    private function loadConfigAgents(): void
    {
        if ($this->configLoaded) {
            return;
        }

        $this->configLoaded = true;

        /** @var array<string|int, string> $configured */
        $configured = $this->container->make('config')->get('cortex.agents', []);

        foreach ($configured as $name => $class) {
            $this->register(is_string($name) ? $name : $this->deriveName($class), $class);
        }
    }

    /**
     * Resolve a registration name for a list-style (unkeyed) config entry
     * from the class basename. Non-agent classes fall through so that
     * register() rejects them with its usual exception.
     */
    private function deriveName(string $class): string
    {
        if (! is_a($class, AgentContract::class, true)) {
            return $class;
        }

        return Str::kebab(class_basename($class));
    }
}
