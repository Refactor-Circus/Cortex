<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Requests;

use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\ListConcreteAgentVersionsAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Resources\ConcreteAgentOverrideVersionResource;
use Laravel\Mcp\ResponseFactory;

final class ListConcreteAgentVersionsMcpRequest extends ConcreteAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('viewAny', ConcreteAgentOverrideVersionModel::class, [$this->override()]);
    }

    protected function rules(): array
    {
        return [
            'agent' => ['required', 'string'],
            ...ListConcreteAgentVersionsAction::rules(),
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $versions = app(ListConcreteAgentVersionsAction::class)->execute($this->override());

        return $this->structuredCollection(ConcreteAgentOverrideVersionResource::collection($versions)->resolve());
    }
}
