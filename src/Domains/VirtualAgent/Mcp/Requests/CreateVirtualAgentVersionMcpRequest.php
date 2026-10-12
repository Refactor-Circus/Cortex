<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\CreateVirtualAgentVersionAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\CreateVirtualAgentVersionData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Resources\VirtualAgentVersionResource;

final class CreateVirtualAgentVersionMcpRequest extends VirtualAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('create', VirtualAgentVersionModel::class, [$this->agent()]);
    }

    protected function rules(): array
    {
        return $this->agentRules() + CreateVirtualAgentVersionData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $version = app(CreateVirtualAgentVersionAction::class)->execute(
            $this->agent(),
            CreateVirtualAgentVersionData::fromValidated($validated),
        );

        return (new VirtualAgentVersionResource($version))->toStructuredResponse();
    }
}
