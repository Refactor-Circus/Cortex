<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests;

use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ListVirtualAgentsAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Resources\VirtualAgentResource;
use RefactorCircus\Cortex\Mcp\Request;
use Laravel\Mcp\ResponseFactory;

final class ListVirtualAgentsMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return $this->allows('viewAny', VirtualAgentModel::class);
    }

    protected function rules(): array
    {
        return ListVirtualAgentsAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $agents = app(ListVirtualAgentsAction::class)->execute(
            isset($validated['page']) ? (int) $validated['page'] : null,
        );

        return $this->structuredCollection(VirtualAgentResource::collection($agents)->resolve());
    }
}
