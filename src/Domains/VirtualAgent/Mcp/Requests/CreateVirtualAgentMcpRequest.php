<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Mcp\Requests;

use JayI\Cortex\Domains\VirtualAgent\Actions\CreateVirtualAgentAction;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Cortex\Domains\VirtualAgent\Resources\VirtualAgentResource;
use JayI\Cortex\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

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
