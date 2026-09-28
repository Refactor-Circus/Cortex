<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use JayI\Cortex\Http\Requests\DeleteConcreteAgentOverrideRequest;
use JayI\Cortex\Http\Requests\IndexConcreteAgentsRequest;
use JayI\Cortex\Http\Requests\RunConcreteAgentRequest;
use JayI\Cortex\Http\Requests\ShowConcreteAgentRequest;
use JayI\Cortex\Http\Requests\UpdateConcreteAgentToolsRequest;

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
