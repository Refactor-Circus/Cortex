<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Actions\ListVirtualAgentVersionsAction;
use JayI\Cortex\Http\Resources\VirtualAgentVersionResource;
use JayI\Cortex\Models\VirtualAgentVersion;

final class IndexVirtualAgentVersionsRequest extends VirtualAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('viewAny', VirtualAgentVersion::class, [$this->agent()]);
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
