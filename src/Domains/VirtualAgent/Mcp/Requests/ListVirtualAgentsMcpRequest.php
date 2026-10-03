<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Mcp\Requests;

use JayI\Cortex\Domains\VirtualAgent\Actions\ListVirtualAgentsAction;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Cortex\Domains\VirtualAgent\Resources\VirtualAgentResource;
use JayI\Cortex\Mcp\Request;
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
