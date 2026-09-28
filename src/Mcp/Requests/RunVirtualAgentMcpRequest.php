<?php

declare(strict_types=1);

namespace JayI\Cortex\Mcp\Requests;

use JayI\Cortex\Actions\RunVirtualAgentAction;
use JayI\Cortex\Http\Resources\AgentRunResource;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class RunVirtualAgentMcpRequest extends VirtualAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('run', $this->agent());
    }

    protected function rules(): array
    {
        return [
            'slug' => ['required', 'string'],
            ...RunVirtualAgentAction::rules(),
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $response = app(RunVirtualAgentAction::class)->execute(
            $this->agent(),
            (string) $validated['input'],
        );

        return Response::structured((new AgentRunResource($response))->resolve());
    }
}
