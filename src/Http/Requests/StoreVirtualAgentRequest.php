<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Actions\CreateVirtualAgentAction;
use JayI\Cortex\Http\Request;
use JayI\Cortex\Http\Resources\VirtualAgentResource;
use JayI\Cortex\Models\VirtualAgent;

final class StoreVirtualAgentRequest extends Request
{
    public function authorize(): bool
    {
        return $this->allows('create', VirtualAgent::class);
    }

    public function rules(): array
    {
        return CreateVirtualAgentAction::rules();
    }

    public function persist(): JsonResponse
    {
        $agent = app(CreateVirtualAgentAction::class)->execute($this->validated());

        return (new VirtualAgentResource($agent))->response()->setStatusCode(201);
    }
}
