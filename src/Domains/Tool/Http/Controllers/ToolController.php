<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\Tool\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\Tool\Http\Requests\IndexToolsRequest;

final class ToolController
{
    public function index(IndexToolsRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
