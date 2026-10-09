<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\RunConcreteAgentAction;
use RefactorCircus\Cortex\Http\Resources\AgentRunResource;

final class RunConcreteAgentMcpRequest extends ConcreteAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('run', $this->overrideOrNew());
    }

    protected function rules(): array
    {
        return [
            'agent' => ['required', 'string'],
            ...RunConcreteAgentAction::rules(),
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $response = app(RunConcreteAgentAction::class)->execute(
            $this->agentName(),
            (string) $validated['input'],
        );

        return Response::structured((new AgentRunResource($response))->resolve());
    }
}
