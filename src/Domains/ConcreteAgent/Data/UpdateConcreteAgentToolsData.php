<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Data;

use Closure;
use Illuminate\Validation\Rule;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Keystone\Data\Data;

/**
 * Input for UpdateConcreteAgentToolsAction: the agent's toolset override, or
 * null to clear it.
 */
final readonly class UpdateConcreteAgentToolsData extends Data
{
    /**
     * @param  list<string>|null  $tools
     */
    public function __construct(
        public ?array $tools,
    ) {}

    /**
     * A toolset override may pick from the agent's own code tools and every
     * registered Cortex tool. Null clears the override, which stays allowed
     * for agents with a locked toolset so a stale override can be removed.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(string $agent): array
    {
        $available = array_values(array_unique([
            ...app(AgentRegistry::class)->defaultTools($agent),
            ...app(ToolRegistry::class)->names(),
        ]));

        $locked = ! app(AgentRegistry::class)->supportsToolOverrides($agent);

        return [
            'tools' => ['present', 'nullable', 'array', function (string $attribute, mixed $value, Closure $fail) use ($locked): void {
                if ($locked && $value !== null) {
                    $fail('This agent\'s toolset cannot be overridden.');
                }
            }],
            'tools.*' => ['string', 'distinct', Rule::in($available)],
        ];
    }
}
