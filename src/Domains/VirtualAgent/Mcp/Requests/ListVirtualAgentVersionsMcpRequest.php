<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Mcp\Requests;

use JayI\Cortex\Domains\VirtualAgent\Actions\ListVirtualAgentVersionsAction;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use JayI\Cortex\Domains\VirtualAgent\Resources\VirtualAgentVersionResource;
use Laravel\Mcp\ResponseFactory;

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
