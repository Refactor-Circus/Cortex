<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Actions;

use Closure;
use Illuminate\Validation\Rule;
use JayI\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentToolsUpdatedActionEvent;
use JayI\Cortex\Domains\ConcreteAgent\Events\ConcreteAgentToolsUpdatingActionEvent;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use JayI\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use JayI\Cortex\Domains\Tool\Services\ToolRegistry;
use JayI\Cortex\Support\PublicationCache;

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
    public function execute(string $agent, ?array $tools): ConcreteAgentOverrideModel
    {
        ConcreteAgentToolsUpdatingActionEvent::dispatch($agent, $tools);

        $result = $this->perform($agent, $tools);

        ConcreteAgentToolsUpdatedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @param  list<string>|null  $tools
     */
    private function perform(string $agent, ?array $tools): ConcreteAgentOverrideModel
    {
        /** @var ConcreteAgentOverrideModel $override */
        $override = ConcreteAgentOverrideModel::query()->firstOrNew(['agent' => $agent]);

        $override->tools = $tools;
        $override->save();

        $this->cache->forget($this->cache->concreteAgentsKey());

        return $override->load('publishedVersion');
    }
}
