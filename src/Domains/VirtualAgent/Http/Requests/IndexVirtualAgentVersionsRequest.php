<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Domains\VirtualAgent\Actions\ListVirtualAgentVersionsAction;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use JayI\Cortex\Domains\VirtualAgent\Resources\VirtualAgentVersionResource;

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
