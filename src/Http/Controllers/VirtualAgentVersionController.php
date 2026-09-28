<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Controllers;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Http\Requests\IndexVirtualAgentVersionsRequest;
use JayI\Cortex\Http\Requests\PublishVirtualAgentVersionRequest;
use JayI\Cortex\Http\Requests\ShowVirtualAgentVersionRequest;
use JayI\Cortex\Http\Requests\StoreVirtualAgentVersionRequest;
use JayI\Cortex\Models\VirtualAgent;

final class VirtualAgentVersionController
{
    public function index(IndexVirtualAgentVersionsRequest $request, VirtualAgent $agent): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreVirtualAgentVersionRequest $request, VirtualAgent $agent): JsonResponse
    {
        return $request->persist();
    }

    public function show(ShowVirtualAgentVersionRequest $request, VirtualAgent $agent, int $version): JsonResponse
    {
        return $request->persist();
    }

    public function publish(PublishVirtualAgentVersionRequest $request, VirtualAgent $agent, int $version): JsonResponse
    {
        return $request->persist();
    }
}
