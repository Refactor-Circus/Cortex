<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Http\Controllers;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Domains\VirtualAgent\Http\Requests\RunVirtualAgentRequest;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;

final class VirtualAgentRunController
{
    public function store(RunVirtualAgentRequest $request, VirtualAgentModel $agent): JsonResponse
    {
        return $request->persist();
    }
}
