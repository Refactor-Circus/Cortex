<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\VirtualAgent\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Cortex\Domains\VirtualAgent\Actions\CreateVirtualAgentAction;
use RefactorCircus\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use RefactorCircus\Cortex\Domains\VirtualAgent\Resources\VirtualAgentResource;
use RefactorCircus\Cortex\Mcp\Request;

final class CreateVirtualAgentMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return $this->allows('create', VirtualAgentModel::class);
    }

    protected function rules(): array
    {
        return CreateVirtualAgentAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $agent = app(CreateVirtualAgentAction::class)->execute($validated);

        return Response::structured((new VirtualAgentResource($agent))->resolve());
    }
}
