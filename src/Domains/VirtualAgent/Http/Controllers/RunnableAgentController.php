<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\IndexRunnableAgentsRequest;

final class RunnableAgentController
{
    public function index(IndexRunnableAgentsRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
