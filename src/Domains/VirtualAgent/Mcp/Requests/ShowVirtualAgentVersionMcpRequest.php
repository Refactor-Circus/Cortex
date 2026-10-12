<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ShowVirtualAgentVersionAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Resources\VirtualAgentVersionResource;

final class ShowVirtualAgentVersionMcpRequest extends VirtualAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('view', $this->version());
    }

    protected function rules(): array
    {
        return $this->versionRules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $version = app(ShowVirtualAgentVersionAction::class)->execute(
            $this->agent(),
            (int) $validated['version'],
        );

        return (new VirtualAgentVersionResource($version))->toStructuredResponse();
    }
}
