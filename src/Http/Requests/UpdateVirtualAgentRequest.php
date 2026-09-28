<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Actions\UpdateVirtualAgentAction;
use JayI\Cortex\Http\Resources\VirtualAgentResource;

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
