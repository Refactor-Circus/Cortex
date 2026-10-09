<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests;

use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\ShowVirtualAgentAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Resources\VirtualAgentResource;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class ShowVirtualAgentMcpRequest extends VirtualAgentMcpRequest
{
    protected function authorize(): bool
    {
        return $this->allows('view', $this->agent());
    }

    protected function rules(): array
    {
        return [
            'slug' => ['required', 'string'],
            ...ShowVirtualAgentAction::rules(),
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $agent = app(ShowVirtualAgentAction::class)->execute($this->agent());

        return Response::structured((new VirtualAgentResource($agent))->resolve());
    }
}
