<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Mcp\Requests;

use JayI\Cortex\Domains\ConcreteAgent\Actions\ListConcreteAgentsAction;
use JayI\Cortex\Domains\ConcreteAgent\Http\Resources\ConcreteAgentResource;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use JayI\Cortex\Mcp\Request;
use Laravel\Mcp\ResponseFactory;

final class ListConcreteAgentsMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return $this->allows('viewAny', ConcreteAgentOverrideModel::class);
    }

    protected function handle(array $validated): ResponseFactory
    {
        $agents = app(ListConcreteAgentsAction::class)->execute();

        return $this->structuredCollection(ConcreteAgentResource::collection($agents)->resolve());
    }
}
