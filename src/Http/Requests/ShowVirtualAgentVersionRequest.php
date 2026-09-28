<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Actions\ShowVirtualAgentVersionAction;
use JayI\Cortex\Http\Resources\VirtualAgentVersionResource;

final class ShowVirtualAgentVersionRequest extends VirtualAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->version());
    }

    public function rules(): array
    {
        return ShowVirtualAgentVersionAction::rules();
    }

    public function persist(): JsonResponse
    {
        $version = app(ShowVirtualAgentVersionAction::class)->execute(
            $this->agent(),
            (int) $this->route('version'),
        );

        return (new VirtualAgentVersionResource($version))->response();
    }
}
