<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use JayI\Cortex\Domains\ConcreteAgent\Services\AgentRegistry;
use JayI\Cortex\Domains\Tool\Services\ToolRegistry;
use JayI\Cortex\Domains\VirtualAgent\Concerns\ResolvesVirtualAgentReferences;
use JayI\Cortex\Domains\VirtualAgent\Events\VirtualAgentUpdatedActionEvent;
use JayI\Cortex\Domains\VirtualAgent\Events\VirtualAgentUpdatingActionEvent;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;

final class UpdateVirtualAgentAction
{
    use ResolvesVirtualAgentReferences;

    public function __construct(private readonly CreateVirtualAgentVersionAction $createVersion) {}

    /**
     * @return array<string, mixed>
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
            'sub_agents.*' => ['string', 'distinct', Rule::exists('cortex_virtual_agents', 'slug')],
            'concrete_sub_agents' => ['sometimes', 'array'],
            'concrete_sub_agents.*' => ['string', 'distinct', Rule::in(app(AgentRegistry::class)->names())],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(VirtualAgentModel $agent, array $data): VirtualAgentModel
    {
        VirtualAgentUpdatingActionEvent::dispatch($agent, $data);

        $result = $this->perform($agent, $data);

        VirtualAgentUpdatedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * Changed instructions become a new published prompt version; unchanged
     * instructions leave the version history alone.
     *
     * @param  array<string, mixed>  $data
     */
    private function perform(VirtualAgentModel $agent, array $data): VirtualAgentModel
    {
        return DB::transaction(function () use ($agent, $data): VirtualAgentModel {
            $agent->fill(
                collect($data)->only(['name', 'description', 'provider', 'model', 'settings', 'tools', 'concrete_sub_agents'])->all(),
            )->save();

            if (array_key_exists('sub_agents', $data)) {
                /** @var list<string> $slugs */
                $slugs = $data['sub_agents'];

                $subAgentIds = $this->subAgentIds($slugs);

                $this->assertNoCycles($agent, $subAgentIds);

                $agent->subAgents()->sync($subAgentIds);
            }

            if (array_key_exists('instructions', $data) && $data['instructions'] !== $agent->publishedVersion?->content) {
                $this->createVersion->execute($agent, ['content' => $data['instructions'], 'publish' => true]);
            }

            return $agent->refresh()->load(['publishedVersion', 'subAgents']);
        });
    }
}
