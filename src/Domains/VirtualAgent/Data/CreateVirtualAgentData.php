<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Support\SubAgentSlugsExist;
use RefactorCircus\Keystone\Data\Data;

/**
 * Input for CreateVirtualAgentAction: the agent, its prompt (published as
 * version 1), and the tools and sub-agents it may use.
 */
final readonly class CreateVirtualAgentData extends Data
{
    /**
     * @param  array<string, int|float>|null  $settings
     * @param  list<string>  $tools
     * @param  list<string>  $subAgents
     * @param  list<string>  $concreteSubAgents
     */
    public function __construct(
        public string $name,
        public string $slug,
        public string $instructions,
        public ?string $description = null,
        public ?string $provider = null,
        public ?string $model = null,
        public ?array $settings = null,
        public array $tools = [],
        public array $subAgents = [],
        public array $concreteSubAgents = [],
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique(VirtualAgentModel::class, 'slug')],
            'description' => ['nullable', 'string'],
            'instructions' => ['required', 'string'],
            'provider' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'settings' => ['nullable', 'array:temperature,max_steps,max_tokens,top_p'],
            'settings.temperature' => ['sometimes', 'numeric', 'between:0,2'],
            'settings.max_steps' => ['sometimes', 'integer', 'min:1'],
            'settings.max_tokens' => ['sometimes', 'integer', 'min:1'],
            'settings.top_p' => ['sometimes', 'numeric', 'between:0,1'],
            'tools' => ['sometimes', 'array'],
            'tools.*' => ['string', Rule::in(app(ToolRegistry::class)->names())],
            'sub_agents' => ['sometimes', 'array'],
            'sub_agents.*' => ['string', 'distinct', new SubAgentSlugsExist],
            'concrete_sub_agents' => ['sometimes', 'array'],
            'concrete_sub_agents.*' => ['string', 'distinct', Rule::in(app(AgentRegistry::class)->names())],
        ];
    }
}
