<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\VirtualAgent\Http\Requests\SearchAgentsRequest;

final class AgentSearchController
{
    public function index(SearchAgentsRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
