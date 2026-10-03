<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Mcp\Requests;

use JayI\Cortex\Domains\McpServer\Actions\ShowMcpInstructionAction;
use JayI\Cortex\Domains\McpServer\Resources\McpInstructionResource;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class ShowServerInstructionsMcpRequest extends ServerMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('view', $this->instruction());
    }

    protected function rules(): array
    {
        return [
            'server' => ['required', 'string'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $instruction = app(ShowMcpInstructionAction::class)->execute($this->serverName());

        return Response::structured((new McpInstructionResource($instruction))->resolve());
    }
}
