<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\UpdateVirtualAgentAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Data\UpdateVirtualAgentData;
use RefactorCircus\Cortex\Domains\VirtualAgent\Resources\VirtualAgentResource;

final class UpdateVirtualAgentMcpRequest extends VirtualAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->agent());
    }

    protected function rules(): array
    {
        return $this->agentRules() + UpdateVirtualAgentData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $agent = app(UpdateVirtualAgentAction::class)->execute(
            $this->agent(),
            UpdateVirtualAgentData::fromValidated($validated),
        );

        return (new VirtualAgentResource($agent))->toStructuredResponse();
    }
}
