<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use JayI\Cortex\Domains\McpServer\Http\Requests\DeleteMcpInstructionRequest;
use JayI\Cortex\Domains\McpServer\Http\Requests\IndexMcpInstructionVersionsRequest;
use JayI\Cortex\Domains\McpServer\Http\Requests\PublishMcpInstructionVersionRequest;
use JayI\Cortex\Domains\McpServer\Http\Requests\ShowMcpInstructionRequest;
use JayI\Cortex\Domains\McpServer\Http\Requests\StoreMcpInstructionVersionRequest;

final class McpInstructionController
{
    public function show(ShowMcpInstructionRequest $request, string $server): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DeleteMcpInstructionRequest $request, string $server): Response
    {
        return $request->persist();
    }

    public function versions(IndexMcpInstructionVersionsRequest $request, string $server): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreMcpInstructionVersionRequest $request, string $server): JsonResponse
    {
        return $request->persist();
    }

    public function publish(PublishMcpInstructionVersionRequest $request, string $server, int $version): JsonResponse
    {
        return $request->persist();
    }
}
