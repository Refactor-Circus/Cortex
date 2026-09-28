<?php

declare(strict_types=1);

namespace JayI\Cortex\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\Cortex\Actions\ListConcreteAgentVersionsAction;
use JayI\Cortex\Http\Resources\ConcreteAgentOverrideVersionResource;
use JayI\Cortex\Models\ConcreteAgentOverrideVersion;

final class IndexConcreteAgentVersionsRequest extends ConcreteAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('viewAny', ConcreteAgentOverrideVersion::class, [$this->override()]);
    }

    public function persist(): JsonResponse
    {
        $versions = app(ListConcreteAgentVersionsAction::class)->execute($this->override());

        return ConcreteAgentOverrideVersionResource::collection($versions)->response();
    }
}
