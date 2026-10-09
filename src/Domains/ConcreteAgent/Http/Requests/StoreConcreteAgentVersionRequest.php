<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\CreateConcreteAgentVersionAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Resources\ConcreteAgentOverrideVersionResource;

final class StoreConcreteAgentVersionRequest extends ConcreteAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', ConcreteAgentOverrideVersionModel::class, [$this->overrideOrNew()]);
    }

    public function rules(): array
    {
        return CreateConcreteAgentVersionAction::rules();
    }

    public function persist(): JsonResponse
    {
        $version = app(CreateConcreteAgentVersionAction::class)->execute($this->agentName(), $this->validated());

        return (new ConcreteAgentOverrideVersionResource($version))->response()->setStatusCode(201);
    }
}
