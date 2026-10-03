<?php

declare(strict_types=1);

namespace JayI\Cortex\Domains\VirtualAgent\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Domains\VirtualAgent\Actions\CreateVirtualAgentVersionAction;
use JayI\Cortex\Domains\VirtualAgent\Models\VirtualAgentVersionModel;
use JayI\Cortex\Domains\VirtualAgent\Resources\VirtualAgentVersionResource;

final class StoreVirtualAgentVersionRequest extends VirtualAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', VirtualAgentVersionModel::class, [$this->agent()]);
    }

    public function rules(): array
    {
        return CreateVirtualAgentVersionAction::rules();
    }

    public function persist(): JsonResponse
    {
        $version = app(CreateVirtualAgentVersionAction::class)->execute($this->agent(), $this->validated());

        return (new VirtualAgentVersionResource($version))->response()->setStatusCode(201);
    }
}
