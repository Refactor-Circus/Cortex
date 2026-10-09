<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\DeleteVirtualAgentRequest;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\IndexVirtualAgentsRequest;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\ShowVirtualAgentRequest;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\StoreVirtualAgentRequest;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\UpdateVirtualAgentRequest;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;

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

    public function show(ShowVirtualAgentRequest $request, VirtualAgentModel $agent): JsonResponse
    {
        return $request->persist();
    }

    public function update(UpdateVirtualAgentRequest $request, VirtualAgentModel $agent): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DeleteVirtualAgentRequest $request, VirtualAgentModel $agent): Response
    {
        return $request->persist();
    }
}
