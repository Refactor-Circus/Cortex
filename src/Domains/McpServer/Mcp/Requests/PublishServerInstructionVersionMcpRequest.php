<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\McpServer\Mcp\Requests;

use JayI\Cortex\Domains\McpServer\Actions\PublishMcpInstructionVersionAction;
use JayI\Cortex\Domains\McpServer\Resources\McpInstructionResource;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class PublishServerInstructionVersionMcpRequest extends ServerMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('publish', $this->version());
    }

    protected function rules(): array
    {
        return [
            'server' => ['required', 'string'],
            'version' => ['required', 'integer', 'min:1'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $instruction = app(PublishMcpInstructionVersionAction::class)->execute(
            $this->instruction(),
            (int) $validated['version'],
        );

        return Response::structured((new McpInstructionResource($instruction))->resolve());
    }
}
