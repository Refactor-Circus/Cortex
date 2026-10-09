<?php

declare(strict_types=1);

namespace RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Actions\ShowConcreteAgentAction;
use RefactorCircus\Cortex\Domains\ConcreteAgent\Http\Resources\ConcreteAgentResource;

final class ShowConcreteAgentRequest extends ConcreteAgentRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->overrideOrNew());
    }

    public function persist(): JsonResponse
    {
        $agent = app(ShowConcreteAgentAction::class)->execute($this->agentName());

        return (new ConcreteAgentResource($agent))->response();
    }
}
