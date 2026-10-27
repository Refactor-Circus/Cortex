<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RefactorCircus\Cortex\Domains\ConcreteServer\Http\Requests\ClearConcreteServerToolsRequest;
use RefactorCircus\Cortex\Domains\ConcreteServer\Http\Requests\UpdateConcreteServerToolsRequest;

final class McpServerToolsController
{
    public function update(UpdateConcreteServerToolsRequest $request, string $server): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(ClearConcreteServerToolsRequest $request, string $server): Response
    {
        return $request->persist();
    }
}
