<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\ConcreteAgent\Http\Controllers;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Domains\ConcreteAgent\Http\Requests\IndexConcreteAgentVersionsRequest;
use JayI\Cortex\Domains\ConcreteAgent\Http\Requests\PublishConcreteAgentVersionRequest;
use JayI\Cortex\Domains\ConcreteAgent\Http\Requests\StoreConcreteAgentVersionRequest;

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
