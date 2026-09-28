<?php

declare(strict_types=1);

namespace JayI\Cortex\Actions;

use Closure;
use Illuminate\Validation\Rule;
use JayI\Cortex\Agents\AgentRegistry;
use JayI\Cortex\Events\Action\ConcreteAgentToolsUpdatedActionEvent;
use JayI\Cortex\Events\Action\ConcreteAgentToolsUpdatingActionEvent;
use JayI\Cortex\Models\ConcreteAgentOverride;
use JayI\Cortex\Support\PublicationCache;
use JayI\Cortex\Tools\ToolRegistry;

final class UpdateConcreteAgentToolsAction
{
    public function __construct(private readonly PublicationCache $cache) {}

    /**
     * A toolset override may pick from the agent's own code tools and every
     * registered Cortex tool. Null clears the override, which stays allowed
     * for agents with a locked toolset so a stale override can be removed.
     *
     * @return array<string, mixed>
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

    /**
     * @param  list<string>|null  $tools
     */
    public function execute(string $agent, ?array $tools): ConcreteAgentOverride
    {
        ConcreteAgentToolsUpdatingActionEvent::dispatch($agent, $tools);

        $result = $this->perform($agent, $tools);

        ConcreteAgentToolsUpdatedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @param  list<string>|null  $tools
     */
    private function perform(string $agent, ?array $tools): ConcreteAgentOverride
    {
        /** @var ConcreteAgentOverride $override */
        $override = ConcreteAgentOverride::query()->firstOrNew(['agent' => $agent]);

        $override->tools = $tools;
        $override->save();

        $this->cache->forget($this->cache->concreteAgentsKey());

        return $override->load('publishedVersion');
    }
}
