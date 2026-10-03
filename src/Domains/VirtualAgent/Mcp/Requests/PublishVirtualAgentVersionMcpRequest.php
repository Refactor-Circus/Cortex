<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Mcp\Requests;

use JayI\Cortex\Domains\VirtualAgent\Actions\PublishVirtualAgentVersionAction;
use JayI\Cortex\Domains\VirtualAgent\Resources\VirtualAgentResource;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class PublishVirtualAgentVersionMcpRequest extends VirtualAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('publish', $this->version());
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
        $agent = app(PublishVirtualAgentVersionAction::class)->execute(
            $this->agent(),
            (int) $validated['version'],
        );

        return Response::structured((new VirtualAgentResource($agent))->resolve());
    }
}
