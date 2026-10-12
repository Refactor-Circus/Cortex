<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\UpdateConcreteAgentToolsAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Data\UpdateConcreteAgentToolsData;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Resources\ConcreteAgentOverrideResource;

final class UpdateConcreteAgentToolsRequest extends ConcreteAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->overrideOrNew());
    }

    public function rules(): array
    {
        return UpdateConcreteAgentToolsData::rules($this->agentName());
    }

    public function persist(): JsonResponse
    {
        $override = app(UpdateConcreteAgentToolsAction::class)->execute(
            $this->agentName(),
            UpdateConcreteAgentToolsData::fromValidated($this->validated()),
        );

        // PUT sets state, so it answers 200 even when it first creates the row.
        return (new ConcreteAgentOverrideResource($override))->response()->setStatusCode(200);
    }
}
