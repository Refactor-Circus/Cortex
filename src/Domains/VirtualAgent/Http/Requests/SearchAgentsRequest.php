<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\SearchAgentsAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\SearchAgentsData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Resources\AgentReferenceResource;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Http\Request;

final class SearchAgentsRequest extends Request
{
    /**
     * A caller who may list either kind of agent may search; the action
     * searches only the kinds they may list.
     */
    public function authorize(): bool
    {
        return $this->allows('viewAny', VirtualAgentModel::class)
            || $this->allows('viewAny', ConcreteAgentOverrideModel::class);
    }

    public function rules(): array
    {
        return SearchAgentsData::rules();
    }

    public function persist(): JsonResponse
    {
        $user = $this->user();

        $results = app(SearchAgentsAction::class)->execute(
            SearchAgentsData::fromValidated($this->validated()),
            $user instanceof Authenticatable ? $user : null,
        );

        return AgentReferenceResource::collection($results)->response();
    }
}
