<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ListVirtualAgentsAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\ListVirtualAgentsData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Resources\VirtualAgentResource;
use RefactorCircus\Cortex\Mcp\Request;

final class ListVirtualAgentsMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return $this->allows('viewAny', VirtualAgentModel::class);
    }

    protected function rules(): array
    {
        return ListVirtualAgentsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $agents = app(ListVirtualAgentsAction::class)->execute(ListVirtualAgentsData::fromValidated($validated));

        return VirtualAgentResource::collection($agents)->toStructuredResponse();
    }
}
