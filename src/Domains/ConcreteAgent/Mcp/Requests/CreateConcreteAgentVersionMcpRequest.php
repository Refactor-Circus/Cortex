<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\CreateConcreteAgentVersionAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Data\CreateConcreteAgentVersionData;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Resources\ConcreteAgentOverrideVersionResource;

final class CreateConcreteAgentVersionMcpRequest extends ConcreteAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('create', ConcreteAgentOverrideVersionModel::class, [$this->overrideOrNew()]);
    }

    protected function rules(): array
    {
        return $this->agentRules() + CreateConcreteAgentVersionData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $version = app(CreateConcreteAgentVersionAction::class)->execute(
            $this->agentName(),
            CreateConcreteAgentVersionData::fromValidated($validated),
        );

        return (new ConcreteAgentOverrideVersionResource($version))->toStructuredResponse();
    }
}
