<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Requests;

use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\UpdateConcreteAgentToolsAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Resources\ConcreteAgentOverrideResource;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class UpdateConcreteAgentToolsMcpRequest extends ConcreteAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->overrideOrNew());
    }

    protected function rules(): array
    {
        return [
            'agent' => ['required', 'string'],
            ...UpdateConcreteAgentToolsAction::rules($this->agentName()),
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        /** @var list<string>|null $tools */
        $tools = $validated['tools'];

        $override = app(UpdateConcreteAgentToolsAction::class)->execute($this->agentName(), $tools);

        return Response::structured((new ConcreteAgentOverrideResource($override))->resolve());
    }
}
