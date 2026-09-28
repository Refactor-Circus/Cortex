<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Actions\CreateConcreteAgentVersionAction;
use JayI\Cortex\Http\Resources\ConcreteAgentOverrideVersionResource;
use JayI\Cortex\Models\ConcreteAgentOverrideVersion;

final class StoreConcreteAgentVersionRequest extends ConcreteAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', ConcreteAgentOverrideVersion::class, [$this->overrideOrNew()]);
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
