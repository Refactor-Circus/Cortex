<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\RunVirtualAgentAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\RunVirtualAgentData;
use RefactorCircus\Cortex\Http\Resources\AgentRunResource;

final class RunVirtualAgentRequest extends VirtualAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('run', $this->agent());
    }

    public function rules(): array
    {
        return RunVirtualAgentData::rules();
    }

    public function persist(): JsonResponse
    {
        $response = app(RunVirtualAgentAction::class)->execute(
            $this->agent(),
            RunVirtualAgentData::fromValidated($this->validated()),
        );

        return (new AgentRunResource($response))->response();
    }
}
