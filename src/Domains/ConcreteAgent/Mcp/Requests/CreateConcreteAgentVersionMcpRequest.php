<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Mcp\Requests;

use Illuminate\Support\Arr;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\CreateConcreteAgentVersionAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Resources\ConcreteAgentOverrideVersionResource;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class CreateConcreteAgentVersionMcpRequest extends ConcreteAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('create', ConcreteAgentOverrideVersionModel::class, [$this->overrideOrNew()]);
    }

    protected function rules(): array
    {
        return [
            'agent' => ['required', 'string'],
            ...CreateConcreteAgentVersionAction::rules(),
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $version = app(CreateConcreteAgentVersionAction::class)->execute(
            $this->agentName(),
            Arr::except($validated, ['agent']),
        );

        return Response::structured((new ConcreteAgentOverrideVersionResource($version))->resolve());
    }
}
