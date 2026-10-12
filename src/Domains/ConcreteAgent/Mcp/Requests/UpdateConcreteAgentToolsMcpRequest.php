<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\UpdateConcreteAgentToolsAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Data\UpdateConcreteAgentToolsData;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Resources\ConcreteAgentOverrideResource;

final class UpdateConcreteAgentToolsMcpRequest extends ConcreteAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->overrideOrNew());
    }

    protected function rules(): array
    {
        return $this->agentRules() + UpdateConcreteAgentToolsData::rules($this->agentName());
    }

    protected function handle(array $validated): ResponseFactory
    {
        $override = app(UpdateConcreteAgentToolsAction::class)->execute(
            $this->agentName(),
            UpdateConcreteAgentToolsData::fromValidated($validated),
        );

        return (new ConcreteAgentOverrideResource($override))->toStructuredResponse();
    }
}
