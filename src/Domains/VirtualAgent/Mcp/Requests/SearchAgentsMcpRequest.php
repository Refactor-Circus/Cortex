<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\SearchAgentsAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\SearchAgentsData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Resources\AgentReferenceResource;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Mcp\Request;

final class SearchAgentsMcpRequest extends Request
{
    /**
     * A caller who may list either kind of agent may search; the action
     * searches only the kinds they may list.
     */
    protected function authorize(): bool
    {
        return $this->allows('viewAny', VirtualAgentModel::class)
            || $this->allows('viewAny', ConcreteAgentOverrideModel::class);
    }

    protected function rules(): array
    {
        return SearchAgentsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $results = app(SearchAgentsAction::class)->execute(
            SearchAgentsData::fromValidated($validated),
            $this->user(),
        );

        return AgentReferenceResource::collection($results)->toStructuredResponse();
    }
}
