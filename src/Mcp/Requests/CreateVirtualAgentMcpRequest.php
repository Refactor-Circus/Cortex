<?php

declare(strict_types=1);

namespace JayI\Cortex\Mcp\Requests;

use JayI\Cortex\Actions\CreateVirtualAgentAction;
use JayI\Cortex\Http\Resources\VirtualAgentResource;
use JayI\Cortex\Mcp\Request;
use JayI\Cortex\Models\VirtualAgent;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class CreateVirtualAgentMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return $this->allows('create', VirtualAgent::class);
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
