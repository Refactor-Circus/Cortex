<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\UpdateVirtualAgentAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Resources\VirtualAgentResource;

final class UpdateVirtualAgentRequest extends VirtualAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->agent());
    }

    public function rules(): array
    {
        return UpdateVirtualAgentAction::rules();
    }

    public function persist(): JsonResponse
    {
        $agent = app(UpdateVirtualAgentAction::class)->execute($this->agent(), $this->validated());

        return (new VirtualAgentResource($agent))->response();
    }
}
