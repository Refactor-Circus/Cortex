<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Mcp\Requests;

use RefactorCircus\Cortex\Domains\McpServer\Actions\ListMcpInstructionVersionsAction;
use RefactorCircus\Cortex\Domains\McpServer\Models\McpInstructionVersionModel;
use RefactorCircus\Cortex\Domains\McpServer\Resources\McpInstructionVersionResource;
use Laravel\Mcp\ResponseFactory;

final class ListServerInstructionVersionsMcpRequest extends ServerMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('viewAny', McpInstructionVersionModel::class, [$this->instruction()]);
    }

    protected function rules(): array
    {
        return [
            'server' => ['required', 'string'],
            ...ListMcpInstructionVersionsAction::rules(),
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $versions = app(ListMcpInstructionVersionsAction::class)->execute($this->instruction());

        return $this->structuredCollection(McpInstructionVersionResource::collection($versions)->resolve());
    }
}
