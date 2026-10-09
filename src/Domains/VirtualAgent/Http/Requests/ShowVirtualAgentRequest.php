<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ShowVirtualAgentAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Resources\VirtualAgentResource;

final class ShowVirtualAgentRequest extends VirtualAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->agent());
    }

    public function rules(): array
    {
        return ShowVirtualAgentAction::rules();
    }

    public function persist(): JsonResponse
    {
        $agent = app(ShowVirtualAgentAction::class)->execute($this->agent());

        return (new VirtualAgentResource($agent))->response();
    }
}
