<?php

declare(strict_types=1);

namespace JayI\Cortex\Mcp\Requests;

use JayI\Cortex\Actions\ListConcreteAgentVersionsAction;
use JayI\Cortex\Http\Resources\ConcreteAgentOverrideVersionResource;
use JayI\Cortex\Models\ConcreteAgentOverrideVersion;
use Laravel\Mcp\ResponseFactory;

final class ListConcreteAgentVersionsMcpRequest extends ConcreteAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('viewAny', ConcreteAgentOverrideVersion::class, [$this->override()]);
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
