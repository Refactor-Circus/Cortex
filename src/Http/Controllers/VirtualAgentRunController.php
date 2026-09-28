<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Controllers;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Http\Requests\RunVirtualAgentRequest;
use JayI\Cortex\Models\VirtualAgent;

final class VirtualAgentRunController
{
    public function store(RunVirtualAgentRequest $request, VirtualAgent $agent): JsonResponse
    {
        return $request->persist();
    }
}
