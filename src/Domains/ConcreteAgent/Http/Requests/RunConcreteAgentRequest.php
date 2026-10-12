<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\RunConcreteAgentAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Data\RunConcreteAgentData;
use RefactorCircus\Cortex\Http\Resources\AgentRunResource;

final class RunConcreteAgentRequest extends ConcreteAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('run', $this->overrideOrNew());
    }

    public function rules(): array
    {
        return RunConcreteAgentData::rules();
    }

    public function persist(): JsonResponse
    {
        $response = app(RunConcreteAgentAction::class)->execute(
            $this->agentName(),
            RunConcreteAgentData::fromValidated($this->validated()),
        );

        return (new AgentRunResource($response))->response();
    }
}
