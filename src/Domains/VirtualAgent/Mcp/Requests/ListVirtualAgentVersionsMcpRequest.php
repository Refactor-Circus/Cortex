<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ListVirtualAgentVersionsAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Resources\VirtualAgentVersionResource;

final class ListVirtualAgentVersionsMcpRequest extends VirtualAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('viewAny', VirtualAgentVersionModel::class, [$this->agent()]);
    }

    protected function rules(): array
    {
        return [
            'slug' => ['required', 'string'],
            ...ListVirtualAgentVersionsAction::rules(),
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $versions = app(ListVirtualAgentVersionsAction::class)->execute(
            $this->agent(),
            isset($validated['page']) ? (int) $validated['page'] : null,
        );

        return $this->structuredCollection(VirtualAgentVersionResource::collection($versions)->resolve());
    }
}
