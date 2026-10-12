<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\PublishConcreteAgentVersionAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Resources\ConcreteAgentOverrideResource;

final class PublishConcreteAgentVersionMcpRequest extends ConcreteAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('publish', $this->version());
    }

    protected function rules(): array
    {
        return $this->versionRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $override = app(PublishConcreteAgentVersionAction::class)->execute(
            $this->override(),
            (int) $validated['version'],
        );

        return (new ConcreteAgentOverrideResource($override))->toStructuredResponse();
    }
}
