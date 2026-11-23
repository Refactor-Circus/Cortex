<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ListRunnableAgentsAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Resources\AgentReferenceResource;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Http\Request;

final class IndexRunnableAgentsRequest extends Request
{
    /**
     * A caller who may list either kind of agent may ask which they can run;
     * the action keeps only the agents they may run.
     */
    public function authorize(): bool
    {
        return $this->allows('viewAny', VirtualAgentModel::class)
            || $this->allows('viewAny', ConcreteAgentOverrideModel::class);
    }

    public function persist(): JsonResponse
    {
        $user = $this->user();

        $agents = app(ListRunnableAgentsAction::class)->execute(
            $user instanceof Authenticatable ? $user : null,
        );

        return AgentReferenceResource::collection($agents)->response();
    }
}
