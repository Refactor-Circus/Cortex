<?php

declare(strict_types=1);

namespace JayI\Cortex\Mcp\Requests;

use JayI\Cortex\Actions\ListVirtualAgentVersionsAction;
use JayI\Cortex\Http\Resources\VirtualAgentVersionResource;
use JayI\Cortex\Models\VirtualAgentVersion;
use Laravel\Mcp\ResponseFactory;

final class ListVirtualAgentVersionsMcpRequest extends VirtualAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('viewAny', VirtualAgentVersion::class, [$this->agent()]);
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
