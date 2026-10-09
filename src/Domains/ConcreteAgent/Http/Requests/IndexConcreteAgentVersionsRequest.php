<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\ListConcreteAgentVersionsAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Models\ConcreteAgentOverrideVersionModel;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Resources\ConcreteAgentOverrideVersionResource;

final class IndexConcreteAgentVersionsRequest extends ConcreteAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('viewAny', ConcreteAgentOverrideVersionModel::class, [$this->override()]);
    }

    public function persist(): JsonResponse
    {
        $versions = app(ListConcreteAgentVersionsAction::class)->execute($this->override());

        return ConcreteAgentOverrideVersionResource::collection($versions)->response();
    }
}
