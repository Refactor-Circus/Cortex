<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Cortex\Domains\VirtualAgent\Support\SubAgentSlugsExist;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;

/**
 * Input for UpdateVirtualAgentAction: the fields to change. Fields left out
 * are kept; the tool and sub-agent lists given replace the current ones.
 */
final readonly class UpdateVirtualAgentData extends Data
{
    /**
     * @param  array<string, int|float>|null|Missing  $settings
     * @param  list<string>|Missing  $tools
     * @param  list<string>|Missing  $subAgents
     * @param  list<string>|Missing  $concreteSubAgents
     */
    public function __construct(
        public string|Missing $name = new Missing,
        public string|null|Missing $description = new Missing,
        public string|Missing $instructions = new Missing,
        public string|null|Missing $provider = new Missing,
        public string|null|Missing $model = new Missing,
        public array|null|Missing $settings = new Missing,
        public array|Missing $tools = new Missing,
        public array|Missing $subAgents = new Missing,
        public array|Missing $concreteSubAgents = new Missing,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'instructions' => ['sometimes', 'string'],
            'provider' => ['sometimes', 'nullable', 'string', 'max:255'],
            'model' => ['sometimes', 'nullable', 'string', 'max:255'],
            'settings' => ['sometimes', 'nullable', 'array:temperature,max_steps,max_tokens,top_p'],
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
