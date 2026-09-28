<?php

declare(strict_types=1);

namespace JayI\Cortex\Mcp\Requests;

use JayI\Cortex\Actions\ListVirtualAgentsAction;
use JayI\Cortex\Http\Resources\VirtualAgentResource;
use JayI\Cortex\Mcp\Request;
use JayI\Cortex\Models\VirtualAgent;
use Laravel\Mcp\ResponseFactory;

final class ListVirtualAgentsMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return $this->allows('viewAny', VirtualAgent::class);
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
