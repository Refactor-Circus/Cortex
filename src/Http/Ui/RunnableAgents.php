<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Ui;

use JayI\Cortex\Agents\AgentRegistry;
use JayI\Cortex\Models\ConcreteAgentOverride;
use JayI\Cortex\Models\VirtualAgent;

/**
 * The agents the run page offers, keyed `virtual:{slug}` or
 * `concrete:{name}` so one field covers both kinds.
 */
final class RunnableAgents
{
    /**
     * Every agent, whoever may run it.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::options(null, false);
    }

    /**
     * The agents a user may run: `run` on the virtual agent, or on the
     * concrete agent's override, as the API checks it.
     *
     * @return array<string, string>
     */
    public static function for(mixed $user): array
    {
        return self::options($user, true);
    }

    /**
     * Whether a user may run the agent an option names.
     */
    public static function allows(mixed $user, string $option): bool
    {
        [$kind, $key] = explode(':', $option, 2) + [1 => ''];

        if ($kind === 'virtual') {
            $agent = VirtualAgent::query()->where('slug', $key)->first();

            return $agent !== null && ScreenAccess::allowsFor($user, 'run', $agent);
        }

        return ScreenAccess::allowsFor($user, 'run', ConcreteAgentOverride::query()->firstOrNew(['agent' => $key]));
    }

    /**
     * @return array<string, string>
     */
    private static function options(mixed $user, bool $authorize): array
    {
        $virtual = VirtualAgent::query()
            ->orderBy('name')
            ->get()
            ->filter(fn (VirtualAgent $agent): bool => ! $authorize || ScreenAccess::allowsFor($user, 'run', $agent))
            ->mapWithKeys(fn (VirtualAgent $agent): array => [
                'virtual:'.$agent->slug => $agent->name.' ('.__('cortex::cortex.virtual').')',
            ])
            ->all();

        $overrides = $authorize ? ConcreteAgentOverride::query()->get()->keyBy('agent') : collect();

        $concrete = collect(app(AgentRegistry::class)->names())
            ->sort()
            ->filter(fn (string $name): bool => ! $authorize || ScreenAccess::allowsFor($user, 'run', ScreenAccess::concreteAgent($name, $overrides->get($name))))
            ->mapWithKeys(fn (string $name): array => [
                'concrete:'.$name => $name.' ('.__('cortex::cortex.concrete').')',
            ])
            ->all();

        return [...$virtual, ...$concrete];
    }
}
