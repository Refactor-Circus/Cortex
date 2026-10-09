<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\RunVirtualAgentAction;
use RefactorCircus\Cortex\Http\Resources\AgentRunResource;

final class RunVirtualAgentRequest extends VirtualAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('run', $this->agent());
    }

    public function rules(): array
    {
        return RunVirtualAgentAction::rules();
    }

    public function persist(): JsonResponse
    {
        $response = app(RunVirtualAgentAction::class)->execute(
            $this->agent(),
            $this->string('input')->value(),
        );

        return (new AgentRunResource($response))->response();
    }
}
