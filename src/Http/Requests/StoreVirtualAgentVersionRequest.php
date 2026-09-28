<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Actions\CreateVirtualAgentVersionAction;
use JayI\Cortex\Http\Resources\VirtualAgentVersionResource;
use JayI\Cortex\Models\VirtualAgentVersion;

final class StoreVirtualAgentVersionRequest extends VirtualAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('create', VirtualAgentVersion::class, [$this->agent()]);
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
