<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Mcp\Requests;

use JayI\Cortex\Domains\VirtualAgent\Actions\ShowVirtualAgentVersionAction;
use JayI\Cortex\Domains\VirtualAgent\Resources\VirtualAgentVersionResource;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class ShowVirtualAgentVersionMcpRequest extends VirtualAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('view', $this->version());
    }

    protected function rules(): array
    {
        return [
            'slug' => ['required', 'string'],
            'version' => ['required', 'integer', 'min:1'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $version = app(ShowVirtualAgentVersionAction::class)->execute(
            $this->agent(),
            (int) $validated['version'],
        );

        return Response::structured((new VirtualAgentVersionResource($version))->resolve());
    }
}
