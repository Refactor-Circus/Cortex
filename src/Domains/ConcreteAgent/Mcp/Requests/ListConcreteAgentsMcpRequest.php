<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\ListConcreteAgentsAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Resources\ConcreteAgentResource;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideModel;
use RefactorCircus\Cortex\Mcp\Request;

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
