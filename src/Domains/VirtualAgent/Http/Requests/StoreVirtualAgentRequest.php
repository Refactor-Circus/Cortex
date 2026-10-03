<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Domains\VirtualAgent\Actions\CreateVirtualAgentAction;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentModel;
use JayI\Cortex\Domains\VirtualAgent\Resources\VirtualAgentResource;
use JayI\Cortex\Http\Request;

final class StoreVirtualAgentRequest extends Request
{
    public function authorize(): bool
    {
        return $this->allows('create', VirtualAgentModel::class);
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
