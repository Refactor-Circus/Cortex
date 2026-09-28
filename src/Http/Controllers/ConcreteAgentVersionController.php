<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Controllers;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Http\Requests\IndexConcreteAgentVersionsRequest;
use JayI\Cortex\Http\Requests\PublishConcreteAgentVersionRequest;
use JayI\Cortex\Http\Requests\StoreConcreteAgentVersionRequest;

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
