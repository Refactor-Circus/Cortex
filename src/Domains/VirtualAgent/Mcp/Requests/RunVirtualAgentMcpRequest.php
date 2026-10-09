<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\RunVirtualAgentAction;
use RefactorCircus\Cortex\Http\Resources\AgentRunResource;

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
