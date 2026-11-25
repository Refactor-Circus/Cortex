<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ListRunnableAgentsAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Resources\AgentReferenceResource;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Mcp\Request;

final class ListRunnableAgentsMcpRequest extends Request
{
    /**
     * A caller who may list either kind of agent may ask which they can run;
     * the action keeps only the agents they may run.
     */
    protected function authorize(): bool
    {
        return $this->allows('viewAny', VirtualAgentModel::class)
            || $this->allows('viewAny', ConcreteAgentOverrideModel::class);
    }

    protected function handle(array $validated): ResponseFactory
    {
        $agents = app(ListRunnableAgentsAction::class)->execute($this->user());

        return AgentReferenceResource::collection($agents)->toStructuredResponse();
    }
}
