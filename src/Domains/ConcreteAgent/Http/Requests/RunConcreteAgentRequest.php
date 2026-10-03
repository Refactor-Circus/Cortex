<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Domains\ConcreteAgent\Actions\RunConcreteAgentAction;
use JayI\Cortex\Http\Resources\AgentRunResource;

final class RunConcreteAgentRequest extends ConcreteAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('run', $this->overrideOrNew());
    }

    public function rules(): array
    {
        return RunConcreteAgentAction::rules();
    }

    public function persist(): JsonResponse
    {
        $response = app(RunConcreteAgentAction::class)->execute(
            $this->agentName(),
            $this->string('input')->value(),
        );

        return (new AgentRunResource($response))->response();
    }
}
