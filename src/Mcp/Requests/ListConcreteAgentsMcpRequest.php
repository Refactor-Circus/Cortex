<?php

declare(strict_types=1);

namespace JayI\Cortex\Mcp\Requests;

use JayI\Cortex\Actions\ListConcreteAgentsAction;
use JayI\Cortex\Http\Resources\ConcreteAgentResource;
use JayI\Cortex\Mcp\Request;
use JayI\Cortex\Models\ConcreteAgentOverride;
use Laravel\Mcp\ResponseFactory;

final class ListConcreteAgentsMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return $this->allows('viewAny', ConcreteAgentOverride::class);
    }

    protected function handle(array $validated): ResponseFactory
    {
        $agents = app(ListConcreteAgentsAction::class)->execute();

        return $this->structuredCollection(ConcreteAgentResource::collection($agents)->resolve());
    }
}
