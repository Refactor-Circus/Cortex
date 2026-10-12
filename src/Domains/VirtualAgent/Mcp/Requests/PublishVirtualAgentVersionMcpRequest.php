<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\PublishVirtualAgentVersionAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Resources\VirtualAgentResource;

final class PublishVirtualAgentVersionMcpRequest extends VirtualAgentMcpRequest
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
        $agent = app(PublishVirtualAgentVersionAction::class)->execute(
            $this->agent(),
            (int) $validated['version'],
        );

        return (new VirtualAgentResource($agent))->toStructuredResponse();
    }
}
