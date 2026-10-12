<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\McpServer\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\McpServer\Actions\PublishMcpInstructionVersionAction;
use RefactorCircus\Cortex\Domains\McpServer\Resources\McpInstructionResource;

final class PublishServerInstructionVersionMcpRequest extends ServerMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('publish', $this->version());
    }

    protected function rules(): array
    {
        return $this->versionRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $instruction = app(PublishMcpInstructionVersionAction::class)->execute(
            $this->instruction(),
            (int) $validated['version'],
        );

        return (new McpInstructionResource($instruction))->toStructuredResponse();
    }
}
