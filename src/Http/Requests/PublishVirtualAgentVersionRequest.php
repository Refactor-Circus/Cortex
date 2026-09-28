<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Actions\PublishVirtualAgentVersionAction;
use JayI\Cortex\Http\Resources\VirtualAgentResource;

final class PublishVirtualAgentVersionRequest extends VirtualAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('publish', $this->version());
    }

    public function rules(): array
    {
        return PublishVirtualAgentVersionAction::rules();
    }

    public function persist(): JsonResponse
    {
        $agent = app(PublishVirtualAgentVersionAction::class)->execute(
            $this->agent(),
            (int) $this->route('version'),
        );

        return (new VirtualAgentResource($agent))->response();
    }
}
