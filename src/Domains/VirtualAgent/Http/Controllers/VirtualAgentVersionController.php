<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\IndexVirtualAgentVersionsRequest;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\PublishVirtualAgentVersionRequest;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\ShowVirtualAgentVersionRequest;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\StoreVirtualAgentVersionRequest;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;

final class VirtualAgentVersionController
{
    public function index(IndexVirtualAgentVersionsRequest $request, VirtualAgentModel $agent): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreVirtualAgentVersionRequest $request, VirtualAgentModel $agent): JsonResponse
    {
        return $request->persist();
    }

    public function show(ShowVirtualAgentVersionRequest $request, VirtualAgentModel $agent, int $version): JsonResponse
    {
        return $request->persist();
    }

    public function publish(PublishVirtualAgentVersionRequest $request, VirtualAgentModel $agent, int $version): JsonResponse
    {
        return $request->persist();
    }
}
