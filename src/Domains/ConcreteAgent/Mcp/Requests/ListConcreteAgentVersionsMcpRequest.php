<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Mcp\Requests;

use JayI\Cortex\Domains\ConcreteAgent\Actions\ListConcreteAgentVersionsAction;
use JayI\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use JayI\Cortex\Domains\ConcreteAgent\Resources\ConcreteAgentOverrideVersionResource;
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
