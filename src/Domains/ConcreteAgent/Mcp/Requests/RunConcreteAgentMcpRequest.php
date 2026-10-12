<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\RunConcreteAgentAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Data\RunConcreteAgentData;
use RefactorCircus\Cortex\Http\Resources\AgentRunResource;

final class RunConcreteAgentMcpRequest extends ConcreteAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('run', $this->overrideOrNew());
    }

    protected function rules(): array
    {
        return $this->agentRules() + RunConcreteAgentData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $response = app(RunConcreteAgentAction::class)->execute(
            $this->agentName(),
            RunConcreteAgentData::fromValidated($validated),
        );

        return (new AgentRunResource($response))->toStructuredResponse();
    }
}
