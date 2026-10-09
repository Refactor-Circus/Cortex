<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\RunVirtualAgentRequest;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;

final class VirtualAgentRunController
{
    public function store(RunVirtualAgentRequest $request, VirtualAgentModel $agent): JsonResponse
    {
        return $request->persist();
    }
}
