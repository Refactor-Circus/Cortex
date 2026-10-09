<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests\DeleteConcreteAgentOverrideRequest;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests\IndexConcreteAgentsRequest;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests\RunConcreteAgentRequest;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests\ShowConcreteAgentRequest;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests\UpdateConcreteAgentToolsRequest;

final class ConcreteAgentController
{
    public function index(IndexConcreteAgentsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function show(ShowConcreteAgentRequest $request, string $agent): JsonResponse
    {
        return $request->persist();
    }

    public function run(RunConcreteAgentRequest $request, string $agent): JsonResponse
    {
        return $request->persist();
    }

    public function tools(UpdateConcreteAgentToolsRequest $request, string $agent): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DeleteConcreteAgentOverrideRequest $request, string $agent): Response
    {
        return $request->persist();
    }
}
