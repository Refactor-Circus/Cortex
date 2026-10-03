<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Mcp\Requests;

use JayI\Cortex\Domains\ConcreteAgent\Actions\PublishConcreteAgentVersionAction;
use JayI\Cortex\Domains\ConcreteAgent\Resources\ConcreteAgentOverrideResource;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class PublishConcreteAgentVersionMcpRequest extends ConcreteAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('publish', $this->version());
    }

    protected function rules(): array
    {
        return [
            'agent' => ['required', 'string'],
            'version' => ['required', 'integer', 'min:1'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $override = app(PublishConcreteAgentVersionAction::class)->execute(
            $this->override(),
            (int) $validated['version'],
        );

        return Response::structured((new ConcreteAgentOverrideResource($override))->resolve());
    }
}
