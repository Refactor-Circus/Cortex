<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ListVirtualAgentVersionsAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Resources\VirtualAgentVersionResource;

final class IndexVirtualAgentVersionsRequest extends VirtualAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('viewAny', VirtualAgentVersionModel::class, [$this->agent()]);
    }

    public function rules(): array
    {
        return ListVirtualAgentVersionsAction::rules();
    }

    public function persist(): JsonResponse
    {
        $versions = app(ListVirtualAgentVersionsAction::class)->execute($this->agent());

        return VirtualAgentVersionResource::collection($versions)->response();
    }
}
