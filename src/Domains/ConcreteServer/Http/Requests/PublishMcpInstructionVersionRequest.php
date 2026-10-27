<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteServer\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\PublishMcpInstructionVersionAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Resources\McpInstructionResource;

final class PublishMcpInstructionVersionRequest extends McpInstructionRequest
{
    public function authorize(): bool
    {
        return $this->allows('publish', $this->version());
    }

    public function persist(): JsonResponse
    {
        $instruction = app(PublishMcpInstructionVersionAction::class)->execute(
            $this->instruction(),
            (int) $this->route('version'),
        );

        return (new McpInstructionResource($instruction))->response();
    }
}
