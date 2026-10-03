<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use JayI\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use JayI\Cortex\Domains\Tool\Services\ToolRegistry;
use JayI\Cortex\Domains\VirtualAgent\Concerns\ResolvesVirtualAgentReferences;
use JayI\Cortex\Domains\VirtualAgent\Events\VirtualAgentCreatedActionEvent;
use JayI\Cortex\Domains\VirtualAgent\Events\VirtualAgentCreatingActionEvent;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;

final class CreateVirtualAgentAction
{
    use ResolvesVirtualAgentReferences;

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:cortex_virtual_agents,slug'],
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
            'sub_agents.*' => ['string', 'distinct', Rule::exists('cortex_virtual_agents', 'slug')],
            'concrete_sub_agents' => ['sometimes', 'array'],
            'concrete_sub_agents.*' => ['string', 'distinct', Rule::in(app(AgentRegistry::class)->names())],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): VirtualAgentModel
    {
        VirtualAgentCreatingActionEvent::dispatch($data);

        $result = $this->perform($data);

        VirtualAgentCreatedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * Create the agent with its prompt as published version 1.
     *
     * @param  array<string, mixed>  $data
     */
    private function perform(array $data): VirtualAgentModel
    {
        return DB::transaction(function () use ($data): VirtualAgentModel {
            $agent = VirtualAgentModel::query()->create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
                'provider' => $data['provider'] ?? null,
                'model' => $data['model'] ?? null,
                'settings' => $data['settings'] ?? null,
                'tools' => $data['tools'] ?? [],
                'concrete_sub_agents' => $data['concrete_sub_agents'] ?? [],
            ]);

            $version = $agent->versions()->create([
                'version' => 1,
                'content' => $data['instructions'],
            ]);

            $agent->published_version_id = (string) $version->getKey();
            $agent->save();

            $agent->subAgents()->sync($this->subAgentIds($data['sub_agents'] ?? []));

            return $agent->load(['publishedVersion', 'subAgents']);
        });
    }
}
