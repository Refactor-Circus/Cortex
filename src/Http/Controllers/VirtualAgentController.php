<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use JayI\Cortex\Http\Requests\DeleteVirtualAgentRequest;
use JayI\Cortex\Http\Requests\IndexVirtualAgentsRequest;
use JayI\Cortex\Http\Requests\ShowVirtualAgentRequest;
use JayI\Cortex\Http\Requests\StoreVirtualAgentRequest;
use JayI\Cortex\Http\Requests\UpdateVirtualAgentRequest;
use JayI\Cortex\Models\VirtualAgent;

final class VirtualAgentController
{
    public function index(IndexVirtualAgentsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreVirtualAgentRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function show(ShowVirtualAgentRequest $request, VirtualAgent $agent): JsonResponse
    {
        return $request->persist();
    }

    public function update(UpdateVirtualAgentRequest $request, VirtualAgent $agent): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DeleteVirtualAgentRequest $request, VirtualAgent $agent): Response
    {
        return $request->persist();
    }
}
