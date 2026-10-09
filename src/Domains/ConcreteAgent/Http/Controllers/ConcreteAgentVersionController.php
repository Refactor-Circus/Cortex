<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests\IndexConcreteAgentVersionsRequest;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests\PublishConcreteAgentVersionRequest;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests\StoreConcreteAgentVersionRequest;

final class ConcreteAgentVersionController
{
    public function index(IndexConcreteAgentVersionsRequest $request, string $agent): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreConcreteAgentVersionRequest $request, string $agent): JsonResponse
    {
        return $request->persist();
    }

    public function publish(PublishConcreteAgentVersionRequest $request, string $agent, int $version): JsonResponse
    {
        return $request->persist();
    }
}
